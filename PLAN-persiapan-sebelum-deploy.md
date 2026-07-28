# Rencana: Persiapan Besok, Sebelum Siswa Asli Mulai Pakai Railway

> Status: Bagian 2 & 3 sudah selesai + sudah di-push. Bagian 1
> (verifikasi manual) BELUM dikerjakan — baru bisa jalan setelah deploy
> awal Railway online (lihat catatan HTTPS di Bagian 1).
> Ditulis 2026-07-28 dini hari setelah sesi kerja paling padat sejauh ini
> (lihat "Ditambahkan Hari Ini" di `FITUR.md`). Bukan pengganti
> `PLAN-deploy-railway.md` — dokumen ini isinya keputusan +
> verifikasi di sekitar deploy itu, bukan langkah teknis deploy-nya
> sendiri.

## Kenapa Perlu Sesi Persiapan Terpisah

Malam ini banyak sekali logika inti yang berubah (gate jam_masuk, timezone,
prioritas notifikasi WA, anti-spoofing GPS, fitur koreksi absensi) dan
sejauh ini cuma diverifikasi lewat automated test (163 test lolos) —
**belum ada satu pun yang dicoba manual di browser sungguhan dengan HP
asli**. Rencana awalnya "verifikasi manual dulu baru deploy", tapi itu
tidak bisa dijalankan (lihat Bagian 1) — jadi gerbang pengamannya
sekarang dipindah ke: **deploy dengan data kosong dulu, verifikasi
manual di domain Railway pakai akun test, BARU siswa asli didaftarkan**.
Insiden notifikasi dobel semalam adalah alasan kenapa langkah ini tidak
boleh dilewati.

## 1. Verifikasi Manual di Browser (Dilakukan SETELAH Deploy Awal, Bukan Sebelum)

> **Update 2026-07-28**: urutan aslinya "verifikasi dulu baru deploy"
> ternyata tidak bisa dijalankan apa adanya — `APP_URL` lokal sekarang
> `http://70.70.0.18:8080` (HTTP biasa, LAN). Browser modern (termasuk di
> HP) menolak kasih akses kamera & GPS di halaman yang bukan *secure
> context* (harus HTTPS atau `localhost`) — ini aturan browser, bukan
> bug aplikasi. Jadi hampir semua item di bawah tidak akan bisa dicoba
> dari URL lokal itu sama sekali.
>
> **Diputuskan**: verifikasi manual dilakukan di domain Railway
> (otomatis HTTPS) SETELAH deploy awal (data kosong), SEBELUM
> mengumumkan/mengarahkan siswa asli ke situ. Jadi urutannya: deploy
> dulu (Bagian 2+3 di bawah, lalu `PLAN-deploy-railway.md`) → checklist
> ini dijalankan di domain Railway pakai 1-2 akun siswa test buatan
> sendiri → baru siswa asli didaftarkan & diumumkan setelah checklist
> ini lolos semua.

Automated test menjamin LOGIKA-nya benar, bukan bahwa UI-nya benar-benar
bisa dipakai. Coba manual dari HP asli (bukan cuma desktop Chrome
devtools) di domain Railway, buat 1-2 siswa test yang kamu daftarkan
sendiri (pakai email/nomor WA milikmu sendiri, BUKAN data siswa asli —
lihat insiden notifikasi dobel semalam), untuk:

- [ ] **Pesan izin kamera/lokasi ditolak** (baru dikerjakan): di
      `/portal/enroll` dan `/portal/absen`, coba sengaja tolak izin
      kamera/lokasi di browser HP, pastikan muncul instruksi jelas +
      tombol "Coba Lagi" (bukan pesan error mentah), lalu perbaiki
      izinnya manual di pengaturan browser dan pastikan tombol Coba Lagi
      benar-benar bikin alurnya lanjut normal.
- [ ] **Absen mandiri end-to-end (tanpa verifikasi lokasi dulu)**:
      database Railway kosong berarti `Pengaturan::lokasi_lat/lng/radius`
      masih `null` (verifikasi lokasi otomatis nonaktif sampai diisi
      admin) -- coba absen dasar dulu tanpa GPS aktif: daftar wajah,
      scan, liveness (kedip) kepakai, status hadir/terlambat tercatat
      benar. Baru aktifkan verifikasi lokasi via Pengaturan buat lanjut
      ke item GPS di bawah.
- [ ] **Kunci kamera sebelum jam_masuk** -- pastikan pesan "Absen masuk
      belum dibuka" muncul dengan benar di luar jam sekolah (bukan cuma
      lewat `simulasi_waktu`, tapi juga cek tampilannya wajar).
- [ ] **Dashboard siswa**: jam berjalan beneran tick tiap detik, status
      kehadiran & grafik minggu berjalan sesuai data asli.
- [ ] **Riwayat siswa**: dropdown bulan/tahun berfungsi, tombol
      "Laporkan Koreksi" muncul & submit-nya jalan (isi form, upload
      bukti opsional, cek redirect + pesan sukses).
- [ ] **Alur Koreksi Absensi penuh**: siswa lapor -> wali kelas dapat
      email -> wali kelas buka `/koreksi-absensi`, approve dengan pilih
      status -> cek baris absensi beneran berubah.
- [ ] **Notifikasi WA**: `FONNTE_KEHADIRAN_AKTIF` sudah `false` secara
      default di Railway (lihat Bagian 2) jadi absen normal TIDAK akan
      trigger WA kehadiran -- aman dari insiden burst semalam. Tapi
      alpha/belum-hadir/koreksi TIDAK dikunci flag itu (selalu aktif
      begitu `FONNTE_TOKEN` terisi) -- pastikan siswa test yang didaftar
      di Railway cuma pakai nomor WA milikmu sendiri sebelum menguji
      jalur-jalur itu, JANGAN pakai nomor siswa asli manapun.
- [ ] **GPS anti-spoofing**: coba sengaja pakai app fake-GPS Android
      (kalau ada) buat lihat apakah benar-benar tertolak, DAN coba absen
      normal dari lokasi asli beberapa kali buat pastikan tidak ada
      false-positive (siswa asli malah ketolak karena GPS goyang wajar
      dianggap "identik" -- harusnya tidak, tapi belum pernah dicoba di
      device fisik).

## 2. Keputusan yang Perlu Diambil (Bukan Cuma Teknis)

> **Sudah diputuskan** (2026-07-28):

- [x] **Data siswa**: **mulai dari database kosong** di Railway. 113
      siswa yang ada sekarang (campur data asli & data test/dummy —
      "SMTP Reset Test", "Capcin Capcin", dll) TIDAK ikut dibawa —
      didaftar ulang manual/import Excel di Railway nanti, jadi tidak
      perlu proses bersih-bersih data lokal sebelum deploy. Efek samping:
      `Pengaturan::jam_cek_belum_hadir` (aktif, `09:30`) dan lokasi GPS
      (aktif) di lokal juga TIDAK ikut terbawa — nilai ini perlu diisi
      ulang dari nol lewat halaman Pengaturan di Railway setelah online,
      bukan diasumsikan sudah benar.
- [x] **Storage foto siswa**: **Railway Volume**. Setup cepat, cukup
      buat pilot 1 bulan. Kalau nanti pindah region/scaling perlu S3,
      itu jadi migrasi terpisah di kemudian hari, bukan blocker sekarang.
- [x] **`FONNTE_KEHADIRAN_AKTIF`**: **`false`** saat pertama online —
      rollout bertahap ulang seperti strategi awal (alpha/koreksi dulu
      buat "menghangatkan" nomor, kehadiran menyusul manual setelah
      yakin volumenya aman).
- [x] **Domain**: **subdomain default Railway** dulu. Custom domain bisa
      menyusul kapan saja tanpa perlu deploy ulang, tidak jadi blocker
      buat online besok.

## 3. Item Teknis Kecil yang Masih Menggantung

- [x] **Selesai** — `composer.json` sekarang mendeklarasikan `ext-zip`/
      `ext-gd`/`ext-pdo_mysql` eksplisit (lewat `composer require ... --no-update`
      + `composer update --lock` buat sinkronkan lock file). Sekalian
      ketemu & diperbaiki: `composer audit` melaporkan 10 celah keamanan
      di `dompdf/dompdf` (medium — local file read, DoS) & `guzzlehttp/
      guzzle` (medium — kebocoran header lewat redirect); sudah di-update
      ke versi yang sudah diperbaiki (`dompdf` v3.1.5→v3.1.6, `guzzle`
      7.13.1→7.15.2). `composer audit` sekarang bersih. 163 test tetap
      lolos setelah update (termasuk yang benar-benar memanggil route
      export PDF, jadi dompdf baru sudah teruji).
- [x] **Selesai** — semua commit sudah di-push ke `origin/main`.
- [ ] Cek ulang `.env` production checklist di `PLAN-deploy-railway.md`
      (`APP_DEBUG=false`, `APP_TIMEZONE=Asia/Jakarta`, `APP_URL`,
      `SESSION_SECURE_COOKIE=true`, dst) -- sudah didaftar di sana,
      tinggal dieksekusi.
- [ ] Jalankan `php artisan test` sekali lagi persis sebelum mulai deploy
      (bukan cuma percaya hasil semalam) -- pastikan tidak ada state lokal
      yang kebawa nyasar ke suite.

## Urutan yang Disarankan Besok (Diperbarui)

Urutan lama ("verifikasi manual dulu baru deploy") sudah tidak berlaku
karena kendala HTTPS di atas. Urutan sekarang:

1. **Bagian 2 (keputusan)** — sudah selesai semua ✅.
2. **Bagian 3 (item teknis kecil)** — sudah selesai semua ✅.
3. **`PLAN-deploy-railway.md`** — deploy pertama ke Railway (data
   kosong). Ini AMAN dilakukan sebelum verifikasi manual justru karena
   data-nya kosong, tidak ada siswa asli yang bisa kena dampak.
4. **Bagian 1 (verifikasi manual)** — dijalankan di domain Railway yang
   baru online, pakai 1-2 akun siswa test buatan sendiri. Ini gerbang
   terakhir sebelum siswa asli didaftarkan/diumumkan.
5. Baru setelah checklist Bagian 1 lolos semua: daftarkan siswa asli
   (manual atau import Excel) dan umumkan ke sekolah.
