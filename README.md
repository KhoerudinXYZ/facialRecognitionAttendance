# Absensi Wajah SMK

Aplikasi absensi siswa SMK berbasis **pengenalan wajah** (face recognition). Deteksi & pencocokan wajah berjalan **di browser** menggunakan [`@vladmandic/face-api`](https://github.com/vladmandic/face-api) (fork face-api.js), sementara Laravel menyimpan *face descriptor* dan mencatat kehadiran.

## Fitur

- **Manajemen Kelas & Siswa** (CRUD) + pendaftaran (enroll) wajah lewat webcam.
- **Kiosk Absensi** — satu perangkat berkamera; siswa cukup menghadap kamera, kehadiran tercatat otomatis (status **hadir/terlambat** berdasarkan jam masuk).
- **Rekap harian** dengan filter tanggal & kelas, serta input **manual** (izin/sakit/alpha).
- **Laporan** periode + **export Excel (.xlsx)** dan **PDF**.
- **Login admin/guru** (Laravel Breeze).

## Teknologi

- Laravel 12 (PHP 8.2+), MySQL
- Tailwind CSS + Alpine.js (Breeze, stack Blade)
- Vite + `@vladmandic/face-api`
- `openspout/openspout` (Excel), `barryvdh/laravel-dompdf` (PDF)

## Prasyarat PHP

Aktifkan ekstensi berikut di `php.ini`: `zip`, `gd`, `pdo_mysql`, `pdo_sqlite`, `sqlite3` (sqlite hanya untuk menjalankan test).

## Instalasi

```bash
composer install
npm install

# konfigurasi database di .env (default: mysql, DB absensi_face)
# buat database:  CREATE DATABASE absensi_face;

php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link

# Salin model face-api ke public/models (sekali saja)
# (otomatis tersalin dari node_modules; ulangi jika folder public/models hilang)
```

Model weight face-api ada di `public/models/` (tiny_face_detector, face_landmark_68, face_recognition). Bila hilang, salin ulang dari `node_modules/@vladmandic/face-api/model/`.

## Menjalankan

```bash
# terminal 1
php artisan serve
# terminal 2
npm run dev        # atau `npm run build` untuk produksi
```

Buka `http://localhost:8000`.

> **Kamera** hanya aktif di `localhost` atau HTTPS. Jika diakses via IP jaringan, wajib HTTPS.

### Akun default (dari seeder)

- Email: `admin@smk.test`
- Password: `password`

## Alur pemakaian

1. **Kelas** → tambah kelas/rombel.
2. **Siswa** → tambah siswa → **Daftar Wajah** → rekam 5 sampel → simpan.
3. **Kiosk Absen** → arahkan wajah ke kamera → kehadiran tercatat otomatis (1× per hari per siswa).
4. **Rekap** / **Laporan** → lihat, input manual, export Excel/PDF.
5. **Pengaturan** → atur jam masuk & batas terlambat.

## Catatan

- face-api.js tidak memiliki *anti-spoofing* bawaan (foto di layar bisa mengelabui). Cukup untuk lingkungan sekolah terawasi; tambahan verifikasi kedip/gerak kepala dapat menjadi pengembangan lanjutan.
- Ambang kecocokan wajah: jarak Euclidean `0.5` (`resources/js/face-kiosk.js`, konstanta `MATCH_THRESHOLD`).

## Test

```bash
php artisan test --filter=AbsensiSmokeTest
```
