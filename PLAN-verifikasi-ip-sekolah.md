# Verifikasi Jaringan WiFi Sekolah (IP) untuk Absen Mandiri — Audit-Only

## Context

GPS di absen mandiri (`/portal/absen`) bisa dipalsukan lewat app fake-GPS gratisan — koordinat yang dikirim device sepenuhnya dikontrol siswa, browser tidak punya cara mendeteksi mock-location dari JS. Percobaan sebelumnya menambah heuristik anti-spoofing di level GPS (exact-match dua pembacaan) sudah dicabut ([[gps-exact-match-check-removed]]) karena false-positive ke siswa asli dan tetap tidak menghentikan fake-GPS modern yang sengaja menambah jitter.

Sinyal yang lebih sulit dipalsukan orang awam: **alamat IP publik** request itu sendiri. Kalau siswa absen sambil connect WiFi sekolah, IP publik yang sampai ke server adalah IP yang di-assign ISP ke sekolah (NAT, dipakai bareng semua device di WiFi itu) — ini properti koneksi jaringan, bukan data yang dikirim app di HP, jadi tidak bisa diatur bebas lewat app fake-GPS.

**Fitur ini audit-only, sengaja TIDAK memblokir absen.** IP sekolah bisa berubah (dynamic IP), tidak semua siswa wajib connect WiFi sekolah (banyak yang realistisnya pakai data seluler), dan kalau dijadikan syarat wajib, admin lupa update IP = semua siswa jujur ikut gagal absen. Pola risikonya sama seperti insiden GPS exact-match: kontrol keras gampang salah tangkap orang jujur duluan sebelum menghentikan yang curang. Jadi scope kali ini murni: **catat cocok/tidaknya IP ke setiap baris absensi, tampilkan ke admin sebagai sinyal untuk investigasi manual** — tidak ada absen yang ditolak karena ini.

Opt-in & backward-compatible seperti fitur lokasi: kalau admin belum isi `ip_sekolah`, tidak ada perubahan perilaku sama sekali (kolom log tetap tertulis tapi bernilai `null`, bukan `false`).

## Yang sudah dikonfirmasi di codebase

- `bootstrap/app.php:21` — `$middleware->trustProxies(at: '*')` sudah dikonfigurasi untuk Railway. Artinya `$request->ip()` di controller **sudah** mengembalikan IP asli device pengirim (dibaca dari header `X-Forwarded-For` yang di-set edge proxy Railway), bukan IP internal proxy. Tidak ada pekerjaan tambahan di bagian ini.
- Satu-satunya caller `AbsensiRecorder::record()` adalah `SiswaAbsensiController::store()` (`app/Http/Controllers/SiswaAuth/SiswaAbsensiController.php:135`) — tidak ada kiosk admin terpisah yang juga menulis absensi lewat `record()`, jadi perubahan signature aman di satu titik saja.
- `AbsensiRecorder::cekLokasi()` (`app/Services/AbsensiRecorder.php:284`) dipanggil tepat sebelum tiap titik tulis (baris 109 untuk absen masuk, baris 176 untuk absen pulang) — return `null` untuk lanjut, array `['status' => 'lokasi', ...]` untuk tolak. Pengecekan IP baru **tidak** mengikuti pola return-block ini (karena tidak boleh menolak); dia cukup dihitung sekali lalu diselipkan ke `$atribut`/`update()` di titik tulis yang sama.
- Tabel `absensi` (`database/migrations/2026_07_03_100005_create_absensi_table.php`) belum punya kolom untuk data jaringan — kolom baru perlu migration additive nullable, mengikuti pola `add_liveness_verified_to_absensi_table.php` / `add_kelas_id_to_absensi_table.php`.
- `Pengaturan` model sudah punya pola pasangan field-opsional + helper `xAktif()` untuk `lokasi_*` (`lokasiAktif()`) dan `jam_cek_belum_hadir` (`cekBelumHadirAktif()`) — field IP baru mengikuti pola yang sama persis.
- `PengaturanController::updateLokasi()` (baris 60-73) adalah pola persis untuk method controller baru: validasi all-or-nothing tidak perlu di sini karena cuma 1 field, tapi pola `Pengaturan::get()->update($data)` + pesan aktif/nonaktif tetap dipakai.
- `resources/views/pengaturan/edit.blade.php:150-240` — panel "Verifikasi Lokasi Absen (GPS)" adalah rujukan visual: badge Aktif/Nonaktif, form dengan tombol aksi cepat, form kedua terpisah untuk nonaktifkan (`x-confirm-form` + hidden input kosong).
- `resources/views/absensi/index.blade.php:81-136` — tabel Kelola Absensi (admin), kolom terakhir sebelum Aksi adalah "Metode" (badge ikon+teks). Kolom baru untuk indikator IP ditaruh di sini.

## Implementasi

### 1. Migration baru: `database/migrations/2026_08_02_000000_add_ip_sekolah_to_pengaturan_table.php`

```php
$table->string('ip_sekolah')->nullable()->after('lokasi_radius_meter');
```

`down()`: `dropColumn('ip_sekolah')`.

Format nilai: daftar IP dan/atau CIDR dipisah koma, mis. `36.85.12.40, 114.79.0.0/16` — mendukung lebih dari satu jalur internet sekolah tanpa perlu tabel terpisah.

### 2. Migration baru: `database/migrations/2026_08_02_000001_add_ip_request_to_absensi_table.php`

```php
$table->string('ip_request', 45)->nullable()->after('liveness_verified'); // 45 = cukup untuk IPv6
$table->boolean('ip_cocok_sekolah')->nullable()->after('ip_request');
```

`down()`: `dropColumn(['ip_request', 'ip_cocok_sekolah'])`.

`ip_cocok_sekolah` tiga-state disengaja: `null` = fitur belum dikonfigurasi admin (tidak ada dasar untuk menilai), `true`/`false` = hasil cek nyata. Ini penting supaya baris absen lama (sebelum fitur ini ada) tidak salah tampil seolah "tidak cocok" di UI admin.

### 3. `app/Models/Pengaturan.php`

- Tambah `ip_sekolah` ke `$fillable`.
- Helper baru:

```php
public function ipSekolahAktif(): bool
{
    return filled($this->ip_sekolah);
}

/**
 * null = fitur belum dikonfigurasi. true/false = hasil cocok/tidak
 * terhadap daftar ip_sekolah (dipisah koma, boleh IP tunggal atau CIDR).
 */
public function ipCocok(?string $ip): ?bool
{
    if (! $this->ipSekolahAktif() || $ip === null) {
        return null;
    }

    $daftar = array_filter(array_map('trim', explode(',', $this->ip_sekolah)));

    foreach ($daftar as $entri) {
        if (str_contains($entri, '/')) {
            if (Ip::cidrMatch($ip, $entri)) { // helper CIDR, lihat langkah 4
                return true;
            }
        } elseif ($ip === $entri) {
            return true;
        }
    }

    return false;
}
```

### 4. Helper CIDR matching

Tidak ada package IP-matching di `composer.json` saat ini. Tambah satu method statis kecil (bisa langsung di `Pengaturan` sebagai private method, tidak perlu class baru — scope-nya kecil): parse CIDR (`ip2long` + mask bitwise untuk IPv4). **IPv6 CIDR di luar scope PLAN ini** — kalau `ip_sekolah` diisi CIDR IPv6, treat sebagai tidak cocok (bukan error) karena mayoritas ISP rumah/sekolah Indonesia masih kasih IPv4 publik ke pelanggan biasa.

### 5. `app/Http/Controllers/PengaturanController.php`

Method baru `updateIpSekolah(Request $request)`, sibling dari `updateLokasi()`:

```php
public function updateIpSekolah(Request $request): RedirectResponse
{
    $data = $request->validate([
        'ip_sekolah' => ['nullable', 'string', 'max:255'],
    ]);

    Pengaturan::get()->update($data);

    return back()->with('success', filled($data['ip_sekolah'] ?? null)
        ? 'Verifikasi jaringan sekolah diaktifkan.'
        : 'Verifikasi jaringan sekolah dinonaktifkan.');
}
```

Validasi format IP/CIDR per-item sengaja **tidak** ketat (tidak pakai `regex`) — kalau admin salah ketik, akibatnya cuma "tidak pernah cocok" (fitur audit, bukan gate), bukan absen siswa gagal. Lebih aman salah-toleran daripada bikin form pengaturan sulit disimpan gara-gara validasi IP yang rewel.

### 6. `routes/web.php`

Satu baris baru di group `role:admin` yang sama (sekitar baris 90-96), sejajar `pengaturan.lokasi`:

```php
Route::put('pengaturan/ip-sekolah', [PengaturanController::class, 'updateIpSekolah'])->name('pengaturan.ip-sekolah');
```

### 7. `resources/views/pengaturan/edit.blade.php`

Panel baru "Verifikasi Jaringan Sekolah (IP)", ditaruh setelah panel lokasi GPS:

- Badge Aktif/Nonaktif pakai `ipSekolahAktif()`.
- Satu input teks `ip_sekolah` (placeholder: `mis. 36.85.12.40, 114.79.0.0/16`).
- Teks bantu statis (server-rendered, bukan AJAX): `IP Anda saat ini: {{ request()->ip() }}` — admin yang membuka halaman ini dari WiFi sekolah tinggal salin nilai itu ke kolom, tidak perlu endpoint/JS baru seperti tombol geolokasi.
- Catatan peringatan singkat di bawah form: "IP publik sekolah bisa berubah sewaktu-waktu tergantung ISP — cek berkala. Fitur ini tidak memblokir absen, cuma mencatat untuk audit."
- Form nonaktifkan terpisah (pola `x-confirm-form` + hidden input kosong, sama seperti panel lokasi).

### 8. `resources/views/siswa-auth/absen.blade.php` + `app/Http/Controllers/SiswaAuth/SiswaAbsensiController.php`

Tidak perlu perubahan di Blade/JS sisi siswa sama sekali — IP tidak dikirim dari client (tidak bisa dipercaya kalaupun dikirim; `$request->ip()` sudah otomatis tersedia dari koneksi TCP itu sendiri). Di `SiswaAbsensiController::store()`, ambil `$request->ip()` dan teruskan sebagai parameter baru ke `record()`:

```php
return response()->json($recorder->record(
    $siswa,
    $data['lat'] ?? null,
    $data['lng'] ?? null,
    true,
    $data['accuracy'] ?? null,
    $request->ip(),
));
```

### 9. `app/Services/AbsensiRecorder.php`

- Signature `record()` tambah `?string $ip = null` di akhir parameter (backward compatible, satu-satunya caller sudah diupdate di langkah 8).
- Di dua titik tulis (baris ~116 untuk `$atribut` absen masuk, baris ~180 untuk `update()` absen pulang), tambah:

```php
'ip_request' => $ip,
'ip_cocok_sekolah' => Pengaturan::get()->ipCocok($ip), // dipanggil lagi karena $pengaturan sudah in-scope, cukup $pengaturan->ipCocok($ip)
```

(pakai `$pengaturan` yang sudah ada di scope method, bukan `Pengaturan::get()` ulang — konsisten dengan gaya method ini).

- **Tidak** menyentuh `cekLokasi()` — IP check ini murni tulis log, tidak ada branch yang me-return status penolakan.

### 10. `resources/views/absensi/index.blade.php`

Tambah kolom kecil setelah "Metode" (baris ~88): badge ikon berdasarkan `$a->ip_cocok_sekolah`:

- `true` → tidak perlu badge mencolok (kondisi normal/diharapkan), atau badge hijau tipis "WiFi Sekolah".
- `false` → badge kuning/orange "Jaringan Lain" — sinyal untuk dicek admin, bukan error.
- `null` → tidak tampil apa-apa (fitur belum dikonfigurasi, atau baris data lama).

### 11. Test baru

- `tests/Feature/PengaturanTest.php` (atau file setara yang sudah ada untuk `updateLokasi`) — test `updateIpSekolah()`: simpan IP tunggal, simpan CIDR, reset ke kosong.
- Unit test kecil untuk `Pengaturan::ipCocok()`: IP persis cocok → true; IP di dalam CIDR → true; IP di luar keduanya → false; `ip_sekolah` kosong → null (bukan false); `$ip` null (mis. gagal deteksi IP, kasus langka) → null.
- `tests/Feature/SiswaSelfServiceTest.php` — 1 test HTTP end-to-end: set `ip_sekolah`, POST absen mandiri dengan `REMOTE_ADDR` yang **tidak** cocok → **absen tetap sukses** (status `success`), dan row `Absensi` yang tertulis punya `ip_cocok_sekolah === false`. Ini test paling penting di seluruh PLAN — membuktikan fitur ini benar-benar tidak pernah memblokir.

## Yang TIDAK diubah

- Tidak ada penolakan/blocking absen berdasarkan IP — ini murni logging untuk audit manual oleh admin.
- Tidak ada endpoint/JS baru di sisi siswa — IP diambil dari koneksi request itu sendiri di server, bukan dikirim dari client.
- Tidak ada dukungan CIDR IPv6 (di luar scope; treat sebagai "tidak cocok" kalau diisi).
- Tidak ada package baru — CIDR matching IPv4 cukup pakai `ip2long`/bitwise PHP built-in.
- `AbsensiController::manual()` (input manual admin/wali kelas) tidak tersentuh — ini murni soal absen mandiri lewat `/portal/absen`.

## Verifikasi

1. `php artisan migrate` lalu `php artisan test` — pastikan semua test lama + baru lulus.
2. Isi `ip_sekolah` lewat panel admin dengan IP publik device penguji saat ini (cek lewat `request()->ip()` yang ditampilkan di panel).
3. Absen mandiri dari device yang sama (IP cocok) → sukses, cek row `Absensi` → `ip_cocok_sekolah = true`.
4. Ganti jaringan (mis. matikan WiFi, pindah ke data seluler / hotspot HP lain) → absen mandiri lagi (siswa lain / hari beda supaya tidak kena gate "sudah absen") → **tetap sukses**, cek row → `ip_cocok_sekolah = false`. Ini pembuktian utama bahwa fitur tidak memblokir.
5. Cek tabel Kelola Absensi (`/absensi`) → badge indikator jaringan muncul sesuai kondisi di atas.
6. Reset `ip_sekolah` ke kosong lewat tombol nonaktifkan → absen mandiri lagi → row baru punya `ip_cocok_sekolah = null` (bukan `false`).
7. Bersihkan data uji (`Pengaturan::ip_sekolah` direset ke null, absensi uji dihapus).
