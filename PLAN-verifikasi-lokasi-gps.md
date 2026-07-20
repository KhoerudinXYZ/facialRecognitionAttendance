# Verifikasi Lokasi GPS untuk Absen Mandiri

## Context

Siswa absen mandiri di `/portal/absen` cuma discan wajahnya sendiri lewat kamera device masing-masing (face-api.js, client-side) — tidak ada apa pun yang memverifikasi mereka benar-benar di sekolah, jadi secara teknis bisa absen dari rumah asal wajahnya kena kamera. User minta ditambah pengecekan lokasi GPS: kalau dikonfigurasi, absen ditolak kalau siswa berada di luar radius tertentu dari titik sekolah.

Fitur ini **opt-in dan backward-compatible** — kalau admin belum mengisi koordinat sekolah, semuanya berjalan persis seperti sekarang (tidak ada prompt izin lokasi sama sekali, tidak ada perubahan perilaku). Mengikuti pola `simulasi_waktu` yang sudah ada di `Pengaturan` (field opsional, panel terpisah, method controller terpisah).

Scope sengaja kecil biar bisa selesai & terverifikasi malam ini: 3 kolom nullable baru, 1 method controller + 1 route + 1 panel baru (niru persis pola panel "Simulasi Waktu"), 2 parameter baru di 1 method existing, ~30 baris JS baru. Tidak ada map UI, tidak ada package baru — haversine cuma butuh trigonometri PHP built-in.

## Yang sudah dikonfirmasi di codebase

-   `app/Services/AbsensiRecorder.php` — satu method `record(Siswa $siswa): array`, pakai `Pengaturan::get()->waktuSekarang()` buat "sekarang" (menghormati `simulasi_waktu`), branching: libur → tolak semua; belum ada absensi hari ini → tulis absen masuk; sudah masuk belum pulang → tulis absen pulang (atau tolak kalau belum waktunya); sudah masuk & pulang → tolak. Tidak ada field lokasi sama sekali hari ini.
-   `SiswaAbsensiController::store()` cuma baca `Auth::guard('siswa')->user()`, tidak baca apa pun dari `$request` (sengaja, supaya identitas tidak bisa dispoof lewat payload — lat/lng nanti aman ditambahkan karena tidak menyentuh identitas).
-   `resources/js/face-kiosk.js` — `recordAttendance(siswaId)` POST `{ siswa_id }` ke `route('siswa.absen.store')`, dipanggil dari loop deteksi wajah tiap match ketemu (cooldown 15 detik per siswa). Response cuma dicek `data.status` (`success`/`already`/`libur`) buat toast + visual state.
-   `Pengaturan` model — singleton (`Pengaturan::get()` = `firstOrCreate`), sudah punya pola `waktuSekarang()`/`sekarang()` sebagai satu sumber kebenaran dipakai bareng oleh beberapa caller.
-   Route `pengaturan.simulasi` ada di `routes/web.php:73`, di dalam `Route::middleware('role:admin')->group(...)` (baris 70-78) bareng `pengaturan.edit`/`pengaturan.update`/`hari-libur.*`.
-   `PengaturanController::updateSimulasi()` — method terpisah dari `update()` utama, form reset di blade kirim field kosong (`ConvertEmptyStringsToNull` middleware bawaan Laravel mengubahnya jadi `null` sebelum validasi — mekanisme ini sudah terbukti jalan di tombol reset simulasi yang sudah ada).
-   `tests/Feature/AbsensiRecorderTest.php` — test level service langsung ke `AbsensiRecorder::record()`, punya helper `siswa(): Siswa` bikin kelas+siswa dasar, pola `Carbon::setTestNow(...)` + `tearDown()` reset. `tests/Feature/SiswaSelfServiceTest.php` — test level HTTP end-to-end untuk flow absen mandiri.

## Implementasi

### 1. Migration baru: `database/migrations/2026_07_12_000001_add_lokasi_to_pengaturan_table.php`

Ikuti pola persis `add_mulai_pulang_to_pengaturan_table.php` / `add_simulasi_waktu_to_pengaturan_table.php` — satu migration additive, semua nullable:

```php
$table->decimal('lokasi_lat', 10, 7)->nullable()->after('simulasi_waktu');
$table->decimal('lokasi_lng', 10, 7)->nullable()->after('lokasi_lat');
$table->unsignedInteger('lokasi_radius_meter')->nullable()->after('lokasi_lng');
```

`down()`: `dropColumn(['lokasi_lat', 'lokasi_lng', 'lokasi_radius_meter'])`.

### 2. `app/Models/Pengaturan.php`

-   Tambah ke `$fillable`: `lokasi_lat`, `lokasi_lng`, `lokasi_radius_meter`.
-   Cast `lokasi_lat`/`lokasi_lng` sebagai `decimal:7`.
-   Helper baru `lokasiAktif(): bool` — true kalau ketiga field terisi bareng (all-or-nothing, supaya tidak ada state "radius ada tapi titik sekolah kosong"). Dipakai di 3 tempat: controller, view admin, `AbsensiRecorder`.

### 3. `app/Http/Controllers/PengaturanController.php`

Method baru `updateLokasi(Request $request)`, sibling dari `updateSimulasi()`: validasi `lokasi_lat`/`lokasi_lng`/`lokasi_radius_meter` masing-masing `nullable` + `required_with` ke dua lainnya (all-or-nothing) + range check (`between:-90,90`, `between:-180,180`, `integer min:10 max:5000`), lalu `Pengaturan::get()->update($data)`.

### 4. `routes/web.php`

Satu baris baru di dalam group `role:admin` yang sudah ada (baris 70-78), sejajar `pengaturan.simulasi`:

```php
Route::put('pengaturan/lokasi', [PengaturanController::class, 'updateLokasi'])->name('pengaturan.lokasi');
```

### 5. `resources/views/pengaturan/edit.blade.php`

Panel baru "Verifikasi Lokasi Absen (GPS)" — kartu putih/gray biasa (bukan kuning seperti panel Simulasi Waktu, karena kuning di app ini berarti "testing/sementara" dan ini fitur produksi beneran):

-   Badge aktif/nonaktif (hijau/gray) pakai `lokasiAktif()`.
-   3 input angka: `lokasi_lat`, `lokasi_lng`, `lokasi_radius_meter`.
-   Tombol "Gunakan Lokasi Saat Ini" — `navigator.geolocation.getCurrentPosition()` inline `<script>` (pola sama seperti theme-toggle script yang sudah ada di layout, tidak perlu entry Vite baru) buat auto-isi lat/lng dari device admin yang lagi berdiri di sekolah.
-   Form kedua "Nonaktifkan Verifikasi Lokasi" — kirim 3 field kosong ke route yang sama (pola reset simulasi waktu).

### 6. `resources/views/siswa-auth/absen.blade.php`

Tambah satu data-attribute di `#kiosk-app`: `data-lokasi-aktif="{{ $pengaturan->lokasiAktif() ? '1' : '0' }}"` — supaya JS tahu dari awal apakah perlu minta izin lokasi sama sekali (kalau tidak aktif, tidak ada prompt izin lokasi muncul sama sekali — ini kunci backward-compatibility di sisi client).

### 7. `resources/js/face-kiosk.js`

-   State baru: `lokasiAktif` (dari dataset), `currentPosition` (cache `{lat,lng}` sekali per sesi halaman, bukan `watchPosition`), `geoStatus` (`off`/`pending`/`ok`/`denied`/`unsupported`).
-   `requestLocation()` dipanggil sekali di `init()`, paralel dengan `startCamera()` (bukan berurutan) — kalau `!lokasiAktif` langsung return (no-op total). Kalau ditolak/tidak didukung, tampilkan pesan status persisten ("Izin lokasi ditolak...").
-   Gate di `loop()`: trigger `recordAttendance()` cuma kalau `!lokasiAktif || geoStatus === 'ok'` — selama lokasi masih `pending`, deteksi wajah tetap jalan tapi tidak POST dulu.
-   Payload `recordAttendance()` nambah `lat`/`lng` kalau `currentPosition` ada.
-   Response handling: tambah `'lokasi'` ke kondisi existing yang sudah menangani `'already'`/`'libur'` (toast info + balik ke idle) — supaya status baru ini tidak jatuh tanpa feedback sama sekali (bug kalau lupa ditambahkan).

### 8. `app/Http/Controllers/SiswaAuth/SiswaAbsensiController.php`

`store()` tambah validasi `lat`/`lng` (`nullable`, `numeric`, range check), diteruskan sebagai parameter baru ke `$recorder->record($siswa, $lat, $lng)`. Tidak menyentuh mekanisme identitas-dari-guard yang sudah ada.

### 9. `app/Services/AbsensiRecorder.php`

-   Signature: `record(Siswa $siswa, ?float $lat = null, ?float $lng = null): array` (satu-satunya caller adalah `SiswaAbsensiController::store()`, jadi backward compatible).
-   Helper private `jarakMeter()` — haversine, murni matematika, tidak perlu package.
-   Helper private `cekLokasi()` — return `null` kalau boleh lanjut (lokasi tidak aktif, atau di dalam radius), return array `['status' => 'lokasi', 'message' => ..., 'nama' => ...]` kalau ditolak (lokasi aktif tapi lat/lng tidak terkirim, atau di luar radius).
-   Dipanggil **tepat sebelum tiap titik tulis** (sebelum `Absensi::create()` untuk masuk, sebelum `$existing->update(['jam_pulang'...])` untuk pulang) — **bukan** di awal method. Urutan ini penting: cek libur dan cek "sudah absen"/"belum waktunya pulang" harus tetap menang duluan, supaya siswa yang memang sudah kelar absen dapat pesan yang benar ("sudah absen"), bukan pesan lokasi yang membingungkan padahal bukan itu masalahnya.

### 10. Test baru

-   `tests/Feature/AbsensiRecorderTest.php` — 4 test baru mengikuti pola `siswa()` + `Carbon::setTestNow()` yang sudah ada: (a) lokasi tidak dikonfigurasi → absen normal walau lat/lng dikirim asal; (b) dikonfigurasi + dalam radius → sukses; (c) dikonfigurasi + di luar radius → status `lokasi`, tidak ada row `Absensi` tertulis; (d) dikonfigurasi + lat/lng tidak dikirim → status `lokasi`.
-   `tests/Feature/SiswaSelfServiceTest.php` — 1 test HTTP end-to-end: absen mandiri dengan koordinat di luar radius → response JSON `status: lokasi`, tidak ada row `Absensi` tertulis.

## Yang TIDAK diubah

-   `AbsensiController::manual()` (input manual oleh admin/wali kelas) — ini murni soal flow scan-mandiri siswa.
-   Tidak ada map UI/geofence editor — input angka lat/lng/radius biasa sudah cukup untuk kebutuhan ini.
-   Tidak ada package baru (JS maupun PHP) — haversine dan Geolocation API browser sudah cukup.

## Verifikasi

1. `php artisan migrate` lalu `php artisan test` — pastikan semua test lama + baru lulus.
2. `php artisan view:cache` lalu `view:clear` — pastikan Blade yang diubah compile tanpa error.
3. Server sementara + Playwright (pakai `context.grantPermissions(['geolocation'])` + `context.setGeolocation({...})` — Playwright bisa mock GPS langsung, tidak perlu device asli):
    - Screenshot panel "Verifikasi Lokasi Absen" di halaman Pengaturan, light & dark, sebelum & sesudah diisi (badge harus berubah).
    - Set koordinat sekolah + radius kecil (mis. 100m) lewat form admin.
    - Siswa uji dengan `setGeolocation` persis di titik sekolah → absen sukses seperti biasa (regresi happy-path).
    - Siswa uji dengan `setGeolocation` ~1km dari sekolah → toast info "di luar radius", tidak ada row `Absensi` baru.
    - Tanpa `grantPermissions` (izin ditolak) → pesan status suruh aktifkan izin lokasi, tidak ada POST yang terkirim tanpa koordinat.
    - Reset lokasi ke kosong lewat tombol "Nonaktifkan" → ulangi absen mandiri **tanpa** grant izin lokasi sama sekali → sukses normal, dan pastikan tidak ada prompt izin lokasi/keys `lat`/`lng` muncul di request (network tab) — ini bukti utama backward-compatibility.
4. Bersihkan data uji (siswa/kelas/Pengaturan lokasi direset ke null), matikan server sementara.
