# Absensi Wajah SMK

Aplikasi absensi siswa SMK berbasis **pengenalan wajah** (face recognition). Deteksi & pencocokan wajah berjalan **di browser** menggunakan [`@vladmandic/face-api`](https://github.com/vladmandic/face-api) (fork face-api.js), sementara Laravel menyimpan *face descriptor* dan mencatat kehadiran.

## Fitur

- **Manajemen Kelas & Siswa** (CRUD) + impor Excel.
- **Portal siswa mandiri** (`/portal`) — siswa registrasi sendiri dengan klaim NIS, daftar wajah lewat webcam, lalu **absen mandiri**: cukup menghadap kamera di HP/laptop masing-masing, kehadiran tercatat otomatis (status **hadir/terlambat** berdasarkan jam masuk).
- **Verifikasi lokasi GPS** (opsional) — kalau titik sekolah & radius dikonfigurasi, absen mandiri ditolak bila siswa di luar radius.
- **Hari libur**: tanggal manual (rentang) + **libur mingguan otomatis** (mis. Sabtu & Minggu dicentang sekali, berlaku tiap minggu tanpa perlu ditambah satu-satu).
- **Alpha otomatis + notifikasi email orang tua** — siswa yang tidak absen sampai akhir hari (dan bukan hari libur) ditandai alpha otomatis, orang tua/wali diberi tahu lewat email kalau alamatnya terdaftar.
- **Audit trail** — riwayat absensi yang dihapus tetap tercatat (siapa, kapan, data apa) walau barisnya sendiri sudah hilang dari rekap.
- **Backup database otomatis** (`spatie/laravel-backup`) — dump database + foto siswa terjadwal tiap hari, lihat `DEPLOYMENT.md`.
- **Rekap harian** dengan filter tanggal & kelas, serta input **manual** (izin/sakit/alpha) oleh admin/wali kelas.
- **Laporan** periode + **export Excel (.xlsx)** dan **PDF**.
- **Login admin/wali kelas** (Laravel Breeze) terpisah dari portal siswa.

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

**Admin/wali kelas:**
1. **Kelas** → tambah kelas/rombel.
2. **Siswa** → tambah siswa satu-satu atau **impor Excel** (NIS, nama, kelas, opsional email/no. WA orang tua).
3. **Pengaturan** → atur jam masuk, batas terlambat, mulai pulang, opsional lokasi GPS sekolah.
4. **Hari Libur** → tambah tanggal libur manual, dan/atau centang hari libur mingguan (Sabtu/Minggu).
5. **Rekap** / **Laporan** → lihat kehadiran, input manual (izin/sakit/alpha), export Excel/PDF.

**Siswa (mandiri, lewat `/portal`):**
1. **Registrasi** → klaim NIS yang sudah didaftarkan admin, buat username & password sendiri.
2. **Daftar Wajah** → rekam beberapa sampel wajah lewat webcam/HP.
3. **Absen** → buka halaman absen, arahkan wajah ke kamera → kehadiran tercatat otomatis (1× masuk, 1× pulang per hari). Kalau lokasi GPS diaktifkan admin, browser akan minta izin lokasi dan menolak absen di luar radius sekolah.
4. **Riwayat** → lihat rekap kehadiran & hari libur bulanan sendiri.

Siswa yang sampai akhir hari belum absen (dan bukan hari libur) otomatis ditandai **alpha**; kalau email orang tua terdaftar, notifikasi terkirim otomatis (lihat `AbsensiAlphaChecker`, dijadwalkan tiap malam — butuh cron di server, lihat `DEPLOYMENT.md`).

## Catatan

- face-api.js tidak memiliki *anti-spoofing* bawaan (foto di layar bisa mengelabui). Cukup untuk lingkungan sekolah terawasi; tambahan verifikasi kedip/gerak kepala dapat menjadi pengembangan lanjutan.
- Ambang kecocokan wajah: jarak Euclidean `0.5` (`resources/js/face-kiosk.js`, konstanta `MATCH_THRESHOLD`).
- Notifikasi email orang tua saat ini terkirim lewat `MAIL_MAILER` biasa (bukan WhatsApp) — lihat `DEPLOYMENT.md` untuk mengisi kredensial SMTP asli.

## Test

```bash
php artisan test --filter=AbsensiSmokeTest
```
