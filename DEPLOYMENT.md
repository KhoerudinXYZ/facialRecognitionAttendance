# Deployment Checklist

Catatan ini murni soal **environment/infrastruktur saat deploy ke server sungguhan** — tidak ada yang perlu diubah di `.env` lokal (Laragon) untuk pengembangan sehari-hari.

## 1. Wajib diubah di `.env` produksi

| Variabel | Lokal (sekarang) | Produksi |
|---|---|---|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` — **penting**: kalau tetap `true`, error apa pun akan menampilkan stack trace lengkap + path file ke siapa saja yang mengaksesnya. |
| `APP_URL` | `http://localhost` | Domain asli, dengan `https://` |
| `SESSION_SECURE_COOKIE` | (kosong) | `true` — begitu situs sudah HTTPS, supaya cookie sesi tidak pernah terkirim lewat HTTP biasa |
| `DB_PASSWORD` | kosong (root tanpa password) | Password asli. Idealnya juga bikin user DB khusus (bukan `root`) yang cuma punya akses ke database ini. |
| `MAIL_MAILER` | `log` | Kredensial SMTP asli — notifikasi alpha ke orang tua (`AbsensiAlphaChecker`) baru benar-benar terkirim setelah ini diisi. |

## 2. Wajib HTTPS

Seluruh fitur inti aplikasi ini (kiosk absen, absen mandiri, daftar wajah) bergantung pada `navigator.mediaDevices.getUserMedia` untuk akses kamera. Browser modern **hanya mengizinkan ini di secure context** — HTTPS, atau hostname `localhost` persis. Domain produksi apa pun selain `localhost` **wajib** punya sertifikat TLS asli (mis. Let's Encrypt), kalau tidak kamera tidak akan bisa diakses sama sekali dan seluruh alur absensi berhenti total.

## 3. Document root & file sensitif

Pastikan web server (Apache/Nginx) menunjuk document root ke folder **`public/`**, bukan root repo. Kalau salah arah, `.git/`, `.env`, dan seluruh source `app/` jadi bisa diunduh langsung lewat URL. Cek juga:

- `.env` tidak pernah ikut ter-commit (sudah di-`.gitignore`, tapi cek ulang di server kalau ada proses deploy manual/copy-paste).
- `storage/` dan `bootstrap/cache/` writable oleh user web server (`chmod -R 775` atau sesuaikan owner).

## 4. Reverse proxy (kalau ada)

Kalau nanti web app ini ada di belakang reverse proxy/load balancer (Cloudflare, Nginx di depan PHP-FPM, dst.), tambahkan `trustProxies()` di `bootstrap/app.php` supaya Laravel membaca header `X-Forwarded-*` dengan benar — tanpa ini, deteksi HTTPS dan IP klien bisa salah (gejala umum: redirect loop, `SESSION_SECURE_COOKIE` terlihat gagal padahal koneksi sudah HTTPS).

## 5. Cron untuk fitur alpha otomatis

`AbsensiAlphaChecker` (nandai siswa alpha + kirim notifikasi email tiap malam) dijadwalkan lewat Laravel Scheduler (`routes/console.php`, `dailyAt('23:00')`). Ini **butuh cron asli di server**:

```
* * * * * cd /path/ke/project && php artisan schedule:run >> /dev/null 2>&1
```

Tanpa cron ini, `absensi:cek-alpha` tidak pernah jalan otomatis — bisa dijalankan manual (`php artisan absensi:cek-alpha`) sebagai sementara, tapi tidak akan konsisten tiap hari.

## 6. Setelah deploy pertama kali

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

- **Ganti akun admin default** (`admin@smk.test` / `password` dari seeder) — kredensial ini publik di README/riwayat git, jangan biarkan aktif di produksi.
- Pastikan `public/models/` (weight face-api) ikut ter-deploy — file besar, kadang tidak ke-copy oleh proses deploy yang mengecualikan file besar/binary.
- Cek `php artisan schedule:list` untuk konfirmasi jadwal `absensi:cek-alpha` terbaca dengan benar di server.

## 7. Sanity check cepat setelah live

1. Buka situs via HTTPS, coba `/portal/absen` — pastikan kamera benar-benar menyala (bukti secure context jalan).
2. Login sebagai admin baru (bukan akun default), cek `/pengaturan` bisa diakses.
3. Coba proses absen mandiri end-to-end sekali, cek muncul di rekap.
4. Kalau lokasi GPS & email orang tua sudah dikonfigurasi, cek satu siklus `php artisan absensi:cek-alpha` manual dulu sebelum mengandalkan cron sepenuhnya.
