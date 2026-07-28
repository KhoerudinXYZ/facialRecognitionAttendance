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
