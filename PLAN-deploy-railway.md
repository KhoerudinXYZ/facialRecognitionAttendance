# Rencana: Deploy ke Railway

> Status: **checklist buat dikerjakan besok, belum dieksekusi.**
> Ditulis 2026-07-27 malam setelah sesi perbaikan timezone & notifikasi WA.
> Keputusan storage/data/domain sudah difinalkan 2026-07-28 (lihat
> `PLAN-persiapan-sebelum-deploy.md` bagian 2) — checklist di bawah sudah
> disesuaikan, tidak ada lagi opsi yang perlu dipilih saat eksekusi.

## Sebelum Mulai (cek di lokal dulu)

- [ ] `composer.json` cuma mensyaratkan `"php": "^8.2"`, tidak mendeklarasikan
      `ext-zip`/`ext-gd`/`ext-pdo_mysql` secara eksplisit di `require`.
      Nixpacks (build system Railway) sering auto-detect ekstensi dari
      entri `ext-*` di composer.json — tanpa itu, build bisa jalan tapi
      runtime error pas fitur yang butuh `zip` (export Excel lewat
      openspout, lihat [[php85-package-constraints]]) atau `gd` (proses
      foto wajah) dipakai. **Aman ditambah dulu**: `composer require
      ext-zip ext-gd ext-pdo_mysql --no-update` supaya Nixpacks tahu
      butuh apa saja, atau siapkan `nixpacks.toml` manual kalau build
      Railway ternyata belum otomatis mengaktifkannya.
- [ ] `.env` tidak ikut ke git (sudah aman, `.gitignore`) — semua isinya
      perlu diinput ulang manual di Railway dashboard (lihat checklist
      env var di bawah).
- [ ] `APP_DEBUG=true` di `.env` lokal — pastikan Railway env var-nya
      `false`, supaya stack trace error tidak kelihatan publik.

## Langkah Deploy

1. **Buat project Railway**, hubungkan ke repo GitHub ini.
2. **Provision MySQL addon** dari Railway (bukan SQLite) — catat
   `MYSQL_URL`/host/port/user/pass yang di-generate otomatis.
3. **Isi environment variables** di service web (checklist lengkap di
   bawah, jangan cuma asal `.env` di-paste karena beberapa nilai harus
   beda untuk production).
4. **Volume persisten buat storage** — sudah diputuskan: **Railway
   Volume** (bukan S3). `FILESYSTEM_DISK=local` dan backup
   (`spatie/laravel-backup`, disk `local`) sekarang nulis ke filesystem
   container yang ephemeral di Railway (hilang tiap redeploy/restart).
   Tambah Railway Volume, mount ke `/app/storage/app` (sesuaikan path
   sebenarnya di container), supaya foto wajah siswa & backup tidak
   hilang.
5. **Jalankan migrasi** setelah deploy pertama berhasil (`php artisan
   migrate --force` lewat Railway shell/one-off command — jangan lupa
   `--force` karena `APP_ENV` bukan `local`).
5a. **Buat akun admin pertama** — `migrate --force` saja TIDAK membuat
    user apa pun, jadi tanpa langkah ini tidak ada cara login sama sekali
    ke Railway yang baru online. Jalankan `php artisan db:seed --force`
    (lewat Railway shell/one-off command yang sama) — ini membuat
    `admin@smk.test` / `password` DAN 3 kelas contoh + wali kelas dummy
    (`budi@smk.test`, `siti@smk.test`, `andi@smk.test`, password sama).
    Segera setelah itu, SEBELUM mengumumkan ke siapa pun:
    - Hapus 3 kelas contoh & 3 akun wali kelas dummy tadi (lewat halaman
      admin Kelas/Staff, atau `php artisan tinker` kalau lebih cepat) —
      bukan data sekolah asli, jangan sampai ke-import siswa asli ke
      kelas dummy ini.
    - Login sebagai `admin@smk.test`, langsung ganti password lewat
      halaman profil. Kredensial default ini publik di riwayat git
      (lihat `DEPLOYMENT.md` #7), jangan biarkan aktif di production.
6. **Data siswa**: sudah diputuskan **mulai dari database kosong** —
   tidak ada import/mysqldump dari lokal. Daftar siswa asli manual atau
   lewat import Excel (`siswa.import`) langsung di Railway setelah
   online. `Pengaturan` (jam masuk/pulang, lokasi GPS, dst) juga perlu
   diisi ulang dari nol lewat halaman Pengaturan — jangan asumsikan
   nilai dari lokal ikut terbawa.
7. **Setup scheduler** (lihat bagian terpisah di bawah — ini pengganti
   Windows Task Scheduler yang dipakai di lokal).
8. **Setup queue worker** (juga di bawah — pengganti `QUEUE_CONNECTION=
   database` yang butuh proses `queue:work` jalan terus).
9. **Verifikasi setelah live**:
   - Buka domain, pastikan halaman kiosk & portal siswa jalan.
   - Cek `php artisan tinker` di Railway shell: `config('app.timezone')`
     harus `Asia/Jakarta`, `Carbon::now()` harus cocok jam WIB asli.
   - Test kirim satu notifikasi kehadiran manual, cek masuk ke WA/email
     yang benar. `FONNTE_KEHADIRAN_AKTIF` sengaja `false` dulu (lihat
     checklist env var) — kehadiran belum akan kirim WA sampai diaktifkan
     manual nanti setelah nomor "hangat".
   - Diamkan beberapa menit, cek `notifikasi_absensi_log` bertambah
     sendiri (bukti scheduler jalan) tanpa perlu trigger manual.

## Checklist Environment Variables (Railway)

Isi manual di Railway dashboard, jangan copy `APP_DEBUG`/`APP_URL` apa
adanya dari `.env` lokal:

| Variable | Nilai buat Railway |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | subdomain default Railway dulu (sudah diputuskan, custom domain menyusul kapan saja tanpa perlu deploy ulang) |
| `APP_TIMEZONE` | `Asia/Jakarta` — **jangan sampai lupa**, ini env var baru dari perbaikan tadi malam, tanpa ini Railway balik ke bug UTC yang sama |
| `APP_KEY` | generate baru (`php artisan key:generate --show`) atau pakai yang sama dari lokal kalau mau kontinuitas session — tidak ada kolom terenkripsi di app ini yang bergantung ke key lama, jadi aman generate baru |
| `DB_*` | dari kredensial MySQL addon Railway (biasanya auto-inject sebagai `MYSQL_*`, sesuaikan nama var ke `DB_HOST`/`DB_PORT`/dst yang dibaca `config/database.php`) |
| `MAIL_MAILER`, `MAIL_HOST`, dst | kredensial SMTP asli (jangan `log`, supaya notifikasi kehadiran/alpha/belum-hadir beneran terkirim) |
| `FONNTE_TOKEN` | token asli |
| `FONNTE_KEHADIRAN_AKTIF` | `false` — sudah diputuskan rollout bertahap ulang di lingkungan baru, sama seperti strategi awal |
| `QUEUE_CONNECTION` | `database` (tetap, asal queue worker jalan — lihat bawah) |
| `FILESYSTEM_DISK` | `local` — sudah diputuskan pakai Railway Volume, bukan `s3` |
| `SESSION_SECURE_COOKIE` | `true` — lokal sengaja `false` karena dev server HTTP polos, Railway selalu HTTPS, tanpa ini cookie sesi bisa terkirim tidak terenkripsi |

## Scheduler (pengganti Windows Task Scheduler)

Lokal pakai Windows Task Scheduler + `schedule-run.bat` buat manggil
`php artisan schedule:run` tiap menit. Railway tidak otomatis
menyediakan cron untuk service web biasa. Dua opsi:

- **Kalau plan Railway mendukung Cron Job service**: buat service baru
  di project yang sama, tipe Cron, command `php artisan schedule:run`,
  jadwal `* * * * *` (tiap menit) — paling dekat dengan cara kerja
  cronjob Linux asli yang diasumsikan `Schedule::command(...)->hourly()/
  ->between(...)` di `routes/console.php`.
- **Kalau tidak ada fitur Cron Job**: buat service worker terpisah
  (long-running, bukan cron) yang menjalankan `php artisan
  schedule:work` — ini command bawaan Laravel yang loop sendiri di
  dalam proses (mengecek tiap menit) tanpa butuh cron eksternal sama
  sekali, cocok kalau Railway cuma punya service tipe "selalu nyala".

## Queue Worker (pengganti proses manual di lokal)

Email notifikasi kehadiran dikirim lewat `Mail::queue()` (lihat
`AbsensiRecorder::notifikasiKehadiran()`), butuh proses `php artisan
queue:work --tries=3` jalan terus supaya job di tabel `jobs` benar-benar
terkirim, bukan cuma didaftarkan lalu diam selamanya. Ini HARUS jadi
service terpisah (tipe worker/long-running) di Railway, bukan bagian
dari service web yang nge-handle HTTP request — beda proses.

## Setelah Live — Yang Perlu Diingat

- Backup jadwal (`backup:clean`/`backup:run`/`backup:monitor`, sudah
  ada di `routes/console.php`) ikut jalan otomatis kalau scheduler di
  atas sudah benar — tapi hasil backup-nya nulis ke disk `local`, jadi
  poin Volume di atas juga menentukan apakah backup ini beneran aman
  atau ikut hilang tiap redeploy.
- `simulasi_waktu` di halaman Pengaturan sebaiknya dipastikan `null`
  sebelum siswa asli mulai pakai domain baru (fitur testing, jangan
  sampai ke-bawa aktif ke production).
