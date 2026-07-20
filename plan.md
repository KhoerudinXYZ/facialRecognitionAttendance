# Bulk Pindah Kelas (Bulk-Assign Siswa ke Kelas Tujuan)

## Context

Pertanyaan awal: kalau kelas "X TKJ 1" naik tingkat setahun kemudian, apakah siswanya otomatis pindah ke "XI TKJ 1"? Jawabannya: **tidak ada mekanisme sama sekali** — `siswa.kelas_id` cuma field yang bisa diubah satu-satu lewat form edit siswa, tidak ada konsep tahun ajaran di skema, tidak ada tombol "naik kelas".

Tapi kenaikan kelas di dunia nyata juga tidak serapi "seluruh isi kelas A pindah ke kelas B" — ada siswa yang tinggal kelas, kelas paralel yang diacak ulang, ganti jurusan, dst. Jadi bukan itu yang mau dibangun. Yang dibangun: **primitive yang fleksibel** — admin/wali kelas pilih sekumpulan siswa (checkbox, bisa sebagian saja) lalu pindahkan semuanya ke SATU kelas tujuan dalam satu aksi. Untuk menangani reshuffle, operasi ini tinggal diulang beberapa kali dengan pilihan siswa & kelas tujuan yang beda-beda. Kelas tujuan (mis. "XI TKJ 1") harus sudah dibuat duluan lewat CRUD Kelas yang sudah ada — **tidak perlu migration atau kolom baru sama sekali**, murni menyusun ulang controller + view + route dari infrastruktur yang sudah ada.

Fitur ini juga terhubung langsung ke pekerjaan sebelumnya di sesi ini: kolom `absensi.kelas_id` (migration `2026_07_17_000000_add_kelas_id_to_absensi_table.php`) sengaja dibuat sebagai snapshot supaya laporan per-kelas tetap akurat historisnya walau `siswa.kelas_id` berubah belakangan. Bulk-move ini persis operasi yang snapshot itu dirancang untuk lindungi — pindah kelas TIDAK BOLEH menyentuh `kelas_id` di baris `Absensi` yang sudah ada. Ini jadi test regresi paling penting di fitur ini.

## Pendekatan

### 1. `app/Policies/SiswaPolicy.php`
Tidak perlu method policy baru. Pakai kombinasi yang sudah ada, persis seperti `SiswaController::store()`:
- `authorize('create', [Siswa::class, $kelasTujuan])` — cek apakah user boleh menaruh siswa ke kelas tujuan.
- `authorize('update', $siswa)` per siswa dalam loop — cek apakah user boleh mengubah siswa itu (berbasis kelas siswa SAAT INI).

Tambah komentar singkat di atas `create()` bahwa method ini sekarang juga dipakai `bulkMove()`, bukan cuma `store()`.

### 2. `routes/web.php`
Tambah di atas `Route::resource('siswa', SiswaController::class)` (mengikuti pola `siswa.enroll`/`siswa.resetAccount`):
```php
Route::put('siswa/pindah-kelas', [SiswaController::class, 'bulkMove'])->name('siswa.bulkMove');
```
PUT dipakai karena ini state-changing action, bukan resource CRUD murni — sama seperti `siswa.resetAccount`. Tidak perlu middleware `role:` tambahan (pola siswa.* yang sudah ada full di-scope lewat policy/`visibleTo()`, bukan middleware).

### 3. `app/Http/Controllers/SiswaController.php`

**`bulkMove(Request $request): RedirectResponse`** (baru, taruh setelah `resetAccount()`):
- Validasi: `siswa_ids` (`required|array|min:1`), `siswa_ids.*` (`integer|exists:siswa,id`), `kelas_id` (`required`, `Rule::in(Kelas::visibleTo($request->user())->pluck('id'))` — pola persis sama dengan `validateData()`, jangan panggil `validateData()` itu sendiri karena isinya validasi field lain yang tidak relevan).
- `$kelasTujuan = Kelas::findOrFail($data['kelas_id']); $this->authorize('create', [Siswa::class, $kelasTujuan]);` — sama seperti `store()`.
- Fetch kandidat TANPA `visibleTo()` scope: `Siswa::whereIn('id', $data['siswa_ids'])->get()` — supaya cek `authorize('update', $siswa)` di dalam loop itu betulan jadi gerbang (bukan dead code yang tidak pernah gagal karena query-nya sendiri sudah menyaring duluan).
- Loop di dalam `DB::transaction()`: kalau `Gate::forUser($user)->allows('update', $siswa)` → `$siswa->update(['kelas_id' => $kelasTujuan->id]); $moved++;`, kalau tidak → `$skipped++` (silently skip, konsisten dengan pola `Rule::in` di tempat lain — bukan aborsi seluruh batch).
- Flash pesan gabungkan `$moved`/`$skipped`, contoh: `"{$moved} siswa dipindahkan ke {$kelasTujuan->nama_kelas}."` + `" {$skipped} siswa dilewati (di luar akses Anda)."` kalau `$skipped > 0`.
- Redirect ke `route('siswa.index', ['kelas_id' => $kelasTujuan->id])` — BUKAN filter lama atau reset kosong. Admin langsung lihat roster kelas tujuan termasuk siswa yang baru dipindah, konfirmasi visual bahwa aksinya berhasil.

**`index()` — ubah pagination:**
```php
$perPage = $request->filled('kelas_id') ? 200 : 15;
$siswa = $query->orderBy('nama')->paginate($perPage)->withQueryString();
```
Alasan (taruh sebagai komentar Indonesia di situ): checkbox "pilih semua" untuk bulk-move butuh SELURUH roster kelas tampil dalam satu halaman biar tidak ribet dengan state Alpine lintas-halaman (app ini bukan SPA, tiap navigasi = full reload, jadi selection akan hilang kalau pindah halaman). 200 jauh di atas ukuran kelas realistis manapun. Ini SENGAJA cuma berlaku saat filter `kelas_id` aktif — browsing tanpa filter kelas tetap 15/halaman seperti biasa, dan bulk-select di situ cuma berlaku untuk siswa di halaman yang sedang tampil (batasan v1 yang wajar, karena alur normalnya memang filter dulu ke kelas sumber sebelum bulk-move).

### 4. `resources/views/components/confirm-form.blade.php`
Tambah satu slot opsional baru `fields`, dirender tepat sebelum tombol Batal/Konfirmasi:
```php
@props([..., 'fields' => null])
...
<form ...>
    @csrf
    @if (strtoupper($method) !== 'POST') @method($method) @endif
    {{ $fields }}
    <x-secondary-button ...>Batal</x-secondary-button>
    <x-danger-button ...>{{ $confirmLabel }}</x-danger-button>
</form>
```
Slot kosong = render string kosong, jadi 7 pemanggil lama (kelas/staff/siswa/hari-libur/absensi/enroll/show) tidak terpengaruh sama sekali — tidak ada yang perlu diubah di file lain.

### 5. `resources/views/siswa/index.blade.php`
- Bungkus tabel + action bar dalam `x-data="{ selected: [], targetKelasId: '' }"`.
- Kolom checkbox baru di paling kiri: header "pilih semua" (pakai `@json($siswa->pluck('id'))` buat daftar ID di halaman saat ini, toggle isi `selected`), per-baris `<input type="checkbox" value="{{ $s->id }}" x-model="selected">`.
- Bar aksi bulk, `x-show="selected.length > 0"` + `x-cloak`, taruh di antara form filter dan tabel: tampilkan jumlah terpilih, `<select x-model="targetKelasId">` isi dari `$kelasList` (sudah ada, sudah ter-scope `visibleTo()`, tidak perlu data baru dari controller), lalu `<x-confirm-form>` dengan slot `fields` diisi hidden input `siswa_ids[]` (loop `x-for` dari `selected`) + `kelas_id` (`targetKelasId`). Tombol trigger dibungkus `<template x-if="targetKelasId">` supaya tidak bisa diklik sebelum kelas tujuan dipilih.
- `colspan` baris kosong naik dari 7 ke 8 (nambah 1 kolom checkbox).
- Komentar singkat kenapa `selected`/`targetKelasId` tetap kebaca di dalam slot `fields` milik `<x-confirm-form>` walau komponen itu punya `x-data="{open:false}"` sendiri — Alpine scope menumpuk lewat DOM, bukan lewat batas slot Blade, jadi tidak konflik/tidak perlu di-drill manual.

### Urutan pengerjaan
1. `SiswaPolicy.php` (komentar saja) →
2. `routes/web.php` (route baru) →
3. `SiswaController.php` (`bulkMove()` + ubah `perPage` di `index()`) →
4. `confirm-form.blade.php` (slot `fields`) →
5. `siswa/index.blade.php` (checkbox, bar aksi, wiring) →
6. Test.

## Test (`tests/Feature/SiswaBulkMoveTest.php`, baru)
Ikuti pola helper `admin()`/`kelas()`/`waliKelas()`/`siswa()` yang sudah ada di `PengajuanIzinTest.php`, panggil endpoint via `$this->put('/siswa/pindah-kelas', [...])` (bukan manipulasi model langsung):

- `test_admin_bisa_pindah_kelas_massal` — admin pindahkan beberapa siswa dari kelas A ke kelas B, assert redirect + `kelas_id` masing-masing di DB.
- `test_wali_kelas_bisa_pindah_siswa_binaan_ke_kelas_binaan_lain` — wali kelas yang bina 2 kelas, pindahkan siswanya sendiri antar kelas binaannya.
- `test_wali_kelas_tidak_bisa_pindahkan_siswa_kelas_lain` — `siswa_ids` diselipi ID siswa milik wali kelas lain → siswa itu TIDAK berubah `kelas_id`-nya (silently skipped), response tetap redirect sukses (bukan gagal total), flash message menyebut jumlah yang dilewati.
- `test_wali_kelas_tidak_bisa_pindahkan_ke_kelas_yang_bukan_binaannya` — `kelas_id` tujuan bukan milik wali kelas yang login → `assertSessionHasErrors('kelas_id')` (gagal di validasi `Rule::in`, bukan authorize).
- `test_siswa_ids_kosong_ditolak` — `siswa_ids` kosong/tidak ada → `assertSessionHasErrors('siswa_ids')`.
- **`test_absensi_kelas_id_snapshot_tidak_berubah_setelah_siswa_dipindah`** (paling penting) — siswa di kelas A, ada baris `Absensi` dengan `kelas_id` = A, bulk-move siswa itu ke kelas B → `siswa.kelas_id` jadi B, TAPI baris `absensi.kelas_id` yang sudah ada tetap A. Ini yang membuktikan fitur ini tidak merusak kerja snapshot sebelumnya.

## Catatan cakupan (sengaja TIDAK dikerjakan)
- Tidak ada konsep tahun ajaran / academic year di skema.
- Tidak ada wizard otomatis "naik kelas" yang menebak pemetaan kelas lama → kelas baru.
- Tidak menyentuh `SiswaController::import()` — itu jalur terpisah (assign kelas saat CREATE siswa baru dari Excel), bulk-move ini untuk siswa yang SUDAH ada.
- Perubahan `perPage` di `index()` cuma aktif saat filter `kelas_id` dipakai — browsing tanpa filter tetap 15/halaman seperti sekarang.

## Verifikasi
1. `php artisan test --filter=SiswaBulkMoveTest` — semua skenario di atas hijau.
2. `php artisan test` penuh — pastikan tidak ada regresi (khususnya `LaporanControllerTest` yang sudah menguji snapshot `absensi.kelas_id` dari sisi laporan).
3. Verifikasi visual pakai skill `verify` (Playwright, seperti yang dipakai untuk modal konfirmasi sebelumnya): login admin, buka `/siswa`, filter ke satu kelas, centang beberapa siswa, pilih kelas tujuan, konfirmasi lewat modal, pastikan redirect ke roster kelas tujuan menampilkan siswa yang baru pindah. Cek juga tampilan dark mode dan kondisi "belum pilih kelas tujuan" (tombol konfirmasi tidak muncul/disabled).
