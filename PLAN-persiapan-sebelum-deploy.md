# Rencana: Persiapan Besok, Sebelum Mulai Setup Hosting Railway

> Status: **checklist buat besok pagi, belum dikerjakan.**
> Ditulis 2026-07-28 dini hari setelah sesi kerja paling padat sejauh ini
> (lihat "Ditambahkan Hari Ini" di `FITUR.md`). Ini persiapan SEBELUM
> mulai eksekusi `PLAN-deploy-railway.md` — bukan pengganti checklist itu.

## Kenapa Perlu Sesi Persiapan Terpisah

Malam ini banyak sekali logika inti yang berubah (gate jam_masuk, timezone,
prioritas notifikasi WA, anti-spoofing GPS, fitur koreksi absensi) dan
sejauh ini cuma diverifikasi lewat automated test (163 test lolos) —
**belum ada satu pun yang dicoba manual di browser sungguhan dengan HP
asli**. Deploy ke production dengan data siswa asli tanpa verifikasi
manual dulu berisiko tinggi mengulang insiden malam ini (notifikasi
dobel, dsb) tapi kali ini ke publik, bukan cuma test data lokal.

## 1. Verifikasi Manual di Browser (Prioritas Tertinggi)

Automated test menjamin LOGIKA-nya benar, bukan bahwa UI-nya benar-benar
bisa dipakai. Coba manual, idealnya dari HP asli (bukan cuma desktop
Chrome devtools), untuk:

- [ ] **Pesan izin kamera/lokasi ditolak** (baru dikerjakan): di
      `/portal/enroll` dan `/portal/absen`, coba sengaja tolak izin
      kamera/lokasi di browser HP, pastikan muncul instruksi jelas +
      tombol "Coba Lagi" (bukan pesan error mentah), lalu perbaiki
      izinnya manual di pengaturan browser dan pastikan tombol Coba Lagi
      benar-benar bikin alurnya lanjut normal.
- [ ] **Absen mandiri end-to-end**: buka `/portal/absen`, scan wajah
      sungguhan, cek liveness (kedip) kepakai, cek GPS diminta & diproses
      (kalau di lokasi asli sekolah/rumah, harus BERHASIL -- kalau perlu,
      matikan sementara verifikasi lokasi di Pengaturan buat tes ini
      biar tidak ketolak radius pas bukan di sekolah).
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
- [ ] **Notifikasi WA**: pastikan `FONNTE_KEHADIRAN_AKTIF` di keadaan
      yang diinginkan (lihat poin 4) sebelum tes supaya tidak mengulang
      insiden burst notifikasi ke banyak siswa test/dummy lagi -- test
      ke SATU siswa dengan nomor sendiri dulu seperti semalam.
- [ ] **GPS anti-spoofing**: coba sengaja pakai app fake-GPS Android
      (kalau ada) buat lihat apakah benar-benar tertolak, DAN coba absen
      normal dari lokasi asli beberapa kali buat pastikan tidak ada
      false-positive (siswa asli malah ketolak karena GPS goyang wajar
      dianggap "identik" -- harusnya tidak, tapi belum pernah dicoba di
      device fisik).

## 2. Keputusan yang Perlu Diambil (Bukan Cuma Teknis)

- [ ] **Data siswa**: mulai dari database kosong di Railway, atau bawa
      113 siswa yang ada sekarang? Kalau bawa: database ini masih
      bercampur data asli & data test/dummy yang ketemu semalam ("SMTP
      Reset Test", "Capcin Capcin", nomor WA yang dipakai berkali-kali
      di siswa berbeda) -- **perlu dibersihkan dulu** sebelum jadi data
      production, jangan langsung di-mysqldump apa adanya.
- [ ] **Storage foto siswa**: Railway Volume (lebih cepat setup, terikat
      1 region) atau S3-compatible seperti Cloudflare R2 (lebih tahan
      lama, kerjaan lebih banyak)? Ini menentukan langkah di
      `PLAN-deploy-railway.md` poin 4, perlu diputuskan sebelum mulai.
- [ ] **`FONNTE_KEHADIRAN_AKTIF`**: langsung `true` di Railway (semua
      notifikasi WA aktif penuh sejak hari pertama), atau `false` dulu
      buat rollout bertahap ulang seperti strategi awal ("hangatkan"
      nomor via alpha/koreksi dulu, baru kehadiran)?
- [ ] **Domain**: pakai subdomain default Railway dulu, atau langsung
      custom domain? Kalau custom domain, siapkan DNS-nya besok juga
      supaya tidak nunggu propagasi pas lagi proses deploy.
- [ ] **`Pengaturan::jam_cek_belum_hadir`** sekarang aktif (`09:30`) dan
      **verifikasi lokasi GPS aktif** di database lokal -- kalau data ini
      ikut terbawa ke Railway, pastikan nilainya memang yang diinginkan
      untuk production (radius & titik sekolah harus dicek ulang, bukan
      asumsi otomatis benar).

## 3. Item Teknis Kecil yang Masih Menggantung

- [ ] `composer.json` belum mendeklarasikan `ext-zip`/`ext-gd`/
      `ext-pdo_mysql` secara eksplisit (lihat `PLAN-deploy-railway.md`) --
      paling aman ditambahkan besok pagi SEBELUM push ke Railway, bukan
      ditemukan lewat build yang gagal.
- [ ] Push semua commit ke `origin/main` -- per malam ini ada belasan
      commit lokal yang belum ke-push sama sekali.
- [ ] Cek ulang `.env` production checklist di `PLAN-deploy-railway.md`
      (`APP_DEBUG=false`, `APP_TIMEZONE=Asia/Jakarta`, `APP_URL`,
      `SESSION_SECURE_COOKIE=true`, dst) -- sudah didaftar di sana,
      tinggal dieksekusi.
- [ ] Jalankan `php artisan test` sekali lagi persis sebelum mulai deploy
      (bukan cuma percaya hasil semalam) -- pastikan tidak ada state lokal
      yang kebawa nyasar ke suite.

## Urutan yang Disarankan Besok

1. Bagian 1 (verifikasi manual) dulu -- percuma lanjut ke keputusan
   deploy kalau ternyata ada yang rusak di alur inti.
2. Bagian 2 (keputusan) -- ini yang menentukan langkah konkret di
   `PLAN-deploy-railway.md`, jangan mulai deploy sebelum ini jelas.
3. Bagian 3 (item kecil) -- cepat, bisa disisipkan kapan saja sebelum
   push/deploy.
4. Baru lanjut `PLAN-deploy-railway.md`.
