# Fitur Aplikasi — SIABSEN (Sistem Absensi Face Recognition)

Aplikasi absensi sekolah berbasis pengenalan wajah (face recognition,
client-side lewat `@vladmandic/face-api`), dipakai lewat dua portal
terpisah: **portal siswa** (`/portal/...`, absen mandiri lewat HP sendiri)
dan **panel admin/wali kelas** (dasbor staf sekolah).

---

## Ditambahkan Hari Ini (2026-07-27 s.d. 2026-07-28)

Sesi kerja paling padat sejauh ini — daftar berikut dalam urutan
dikerjakan malam itu:

1. **Notifikasi "Belum Hadir"** — peringatan dini kalau siswa belum absen
   sampai jam tertentu (`Pengaturan::jam_cek_belum_hadir`), terpisah dari
   notifikasi alpha (yang baru jalan di akhir hari).
2. **WhatsApp jadi kanal prioritas notifikasi** — sebelumnya email & WA
   selalu dikirim dobel; sekarang WA dicoba duluan (kalau siswa punya
   nomor & kanal aktif), email cuma jadi cadangan kalau WA tidak bisa
   dipakai.
3. **Fix bug timezone UTC → Asia/Jakarta** — app sejak awal proyek jalan
   di UTC (default Laravel yang tidak pernah diubah), bikin semua jam
   absensi mundur 7 jam dari maksud admin. Data historis MySQL ikut
   dimigrasi.
4. **Kunci absen sebelum `jam_masuk`** — sebelumnya siswa bisa absen
   masuk jam berapa pun (termasuk tengah malam) dan tetap tercatat
   'hadir'; `Pengaturan::jam_masuk` cuma dipakai buat status
   hadir/terlambat, tidak pernah jadi batas bawah yang benar-benar
   mengunci.
5. **Jam berjalan (live clock) di dashboard siswa** — tick tiap detik,
   diformat eksplisit ke Asia/Jakarta lewat `Intl.DateTimeFormat` (bukan
   ikut timezone device) supaya tetap benar walau jam HP siswa salah
   setting.
6. **Pemilih bulan/tahun di halaman Riwayat siswa** — sebelumnya cuma
   bisa geser sebulan-sebulan lewat panah, sekarang ada dropdown
   langsung loncat ke bulan manapun.
7. **Rate limiting registrasi & lupa password** (siswa & staf) — celah
   keamanan nyata: NIS bukan rahasia di sekolah, tanpa throttle siapa
   saja bisa coba klaim akun siswa lain berkali-kali begitu app online
   publik.
8. **Retry otomatis notifikasi WA & email yang gagal** — sebelumnya
   sekali gagal kirim, orang tua hari itu tidak dapat apa-apa sama
   sekali tanpa ada percobaan ulang.
9. **Fitur Koreksi Absensi** — mekanisme formal buat siswa melaporkan
   absensi yang salah tercatat (mis. kena bug, wajah gagal terdeteksi),
   lengkap dengan approval admin/wali kelas yang langsung memperbaiki
   baris absensi dalam satu aksi.
10. **Lapisan anti-spoofing GPS** — dua pembacaan lokasi berturut-turut
    (bukan sekali), plus cek akurasi sinyal & deteksi pembacaan yang
    identik persis (indikasi app fake-GPS statis).
11. Perbaikan pendukung: setup queue worker lokal (email antrian
    sebelumnya tidak pernah benar-benar terkirim, tidak ada proses yang
    memprosesnya), checklist rencana deploy ke Railway, dan beberapa
    hardening kecil lain (`SESSION_SECURE_COOKIE`, dll).
12. **Pesan izin kamera/lokasi yang ditolak jadi jelas & actionable** — di
    halaman absen mandiri maupun daftar wajah: sebelumnya kalau siswa
    terlanjur menolak izin kamera/lokasi, cuma muncul pesan error mentah
    tanpa ada cara jelas buat lanjut (browser tidak pernah menampilkan
    ulang popup izin secara otomatis setelah ditolak). Sekarang muncul
    instruksi langkah-demi-langkah + tombol "Coba Lagi".

---

## Ditambahkan Hari Ini (2026-07-28 malam s.d. 2026-07-29)

Sesi deploy Railway pertama kali live + perbaikan bug yang ketemu dari
testing manual langsung di domain produksi:

1. **Email production pindah dari SMTP ke Resend (HTTP API)** — Mailtrap
   (testing) lalu Gmail SMTP ternyata sama-sama macet ~30 detik lalu
   fatal error di Railway: platform ini memblokir semua trafik SMTP
   keluar (kebijakan anti-abuse PaaS), bukan masalah konfigurasi
   per-provider. Pindah ke Resend (kirim lewat HTTPS, bukan port SMTP)
   menyelesaikannya. Verifikasi domain di Resend masih pending — sebelum
   itu selesai, email cuma bisa terkirim ke satu alamat sandbox
   (alamat akun Resend itu sendiri), belum ke email orang tua sungguhan.
2. **Fix login siswa gagal setelah reset password** — form login
   berlabel "Username / NIS" tapi backend cuma cek kolom `username`
   (dibuat sekali saat registrasi, gampang lupa), tidak pernah fallback
   ke NIS. Sekarang bisa login pakai NIS atau username.
3. **Ikon "terlambat" vs "izin/sakit" dibedakan** di grafik "Perjalanan
   Minggu Ini" (dashboard siswa) — huruf "i" kecil nyaris identik
   bentuknya dengan tanda seru di font bold ukuran kecil, bikin dua
   status kelihatan sama walau beda warna. Diganti jadi huruf S (sakit)
   dan I (izin).
4. **Ikon menu "Izin" di navbar siswa diganti** — sebelumnya
   `alert-circle` (lingkaran+seru), bentuknya sama persis dengan ikon
   status "terlambat" di dashboard, cuma beda warna. Diganti jadi
   `clipboard-list` (ikon formulir) yang juga lebih pas secara makna.
5. **Kalender Tahunan "Pilih Tanggal Merah"** di halaman Hari Libur —
   sebelumnya admin cuma bisa tambah libur lewat rentang tanggal
   berurutan (dari-sampai), merepotkan untuk tanggal-tanggal libur
   nasional yang tersebar sepanjang tahun. Sekarang ada tampilan
   kalender 12 bulan per tahun, admin tinggal klik tanggal mana saja
   yang mau ditandai libur (termasuk yang tersebar, bukan cuma
   berurutan), lalu simpan sekaligus dalam satu submit.

---

## Portal Siswa (`/portal/...`)

- **Registrasi mandiri** — klaim akun lewat NIS (nomor induk siswa yang
  sudah diinput admin), pilih username/password sendiri.
- **Login / logout**, reset password lewat email orang tua (dua jalur:
  reset mandiri buat siswa yang tidak bisa ke sekolah, atau admin/wali
  kelas reset langsung dari panel buat siswa yang sedang di sekolah).
- **Absen mandiri** — scan wajah lewat kamera HP sendiri (bukan kiosk
  bersama), dengan:
  - Deteksi wajah & pencocokan client-side (face descriptor per siswa,
    cuma descriptor milik siswa yang login yang dikirim ke HP-nya).
  - **Liveness detection** (deteksi kedip mata, `BlinkTracker`) — cegah
    foto statis dipakai buat menipu kamera.
  - **Verifikasi lokasi GPS** (opsional, admin yang aktifkan) — harus
    dalam radius tertentu dari titik sekolah, dengan lapisan
    anti-spoofing (lihat bagian "Ditambahkan Hari Ini").
  - **Audit jaringan WiFi sekolah (IP)** (opsional, admin yang aktifkan)
    — IP publik tiap request absen mandiri dicatat & dicocokkan ke
    daftar IP/CIDR sekolah. Murni sinyal audit, tidak pernah menolak
    absen (beda total dari verifikasi GPS) — IP sekolah bisa berubah
    sewaktu-waktu dan tidak semua siswa wajib pakai WiFi sekolah.
  - Absen masuk (status hadir/terlambat otomatis dari jam) dan absen
    pulang (kamera terkunci sampai jam pulang, kecuali ada izin pulang
    cepat yang disetujui).
  - Terkunci otomatis di luar jam absen (sebelum jam masuk, setelah jam
    absen ditutup, hari libur, atau siswa berstatus izin/sakit hari itu).
- **Dashboard** — identitas siswa, jam berjalan real-time, status
  kehadiran hari ini (siklus masuk → pulang), grafik minggu berjalan,
  statistik kehadiran bulan ini.
- **Riwayat absensi** — rekap bulanan (hadir/terlambat/izin-sakit),
  detail per hari termasuk hari libur, bisa loncat ke bulan manapun.
- **Pengajuan izin/sakit/pulang cepat** — upload bukti (opsional untuk
  izin/sakit), menunggu approval wali kelas/admin.
- **Koreksi Absensi** — lapor kalau ada baris absensi yang salah
  tercatat, minta diperbaiki ke status yang benar, dengan alasan & bukti
  opsional.
- **Daftar wajah sendiri** (`enroll`) — rekam beberapa sampel wajah buat
  dipakai proses pengenalan.
- **Profil & ganti password**.

## Panel Admin / Wali Kelas

Akses dibedakan by role: **admin** (akses penuh) vs **wali kelas**
(dibatasi ke kelas binaannya sendiri di hampir semua fitur di bawah).

- **Data Master**: CRUD Kelas, CRUD Siswa (termasuk import massal lewat
  Excel, pindah kelas massal, enroll/kelola wajah siswa dari sisi
  admin).
- **Absensi**: rekap harian (filter tanggal & kelas), input/koreksi
  manual (hadir/terlambat/izin/sakit/alpha), audit log absensi yang
  dihapus (siapa hapus, kapan, data aslinya apa), badge jaringan
  ("WiFi Sekolah" / "Jaringan Lain") per baris absen mandiri kalau
  verifikasi IP sekolah aktif.
- **Pengajuan Izin/Sakit** — approve/reject pengajuan siswa, approve
  otomatis menulis baris absensi yang sesuai.
- **Koreksi Absensi** — approve/reject laporan koreksi siswa; approve
  langsung memilih status akhir (default sesuai permintaan siswa, bisa
  diubah admin) dan memperbarui baris absensi dalam satu aksi.
- **Laporan** — export rekap ke Excel & PDF, dengan scoping kelas binaan
  buat wali kelas.
- **Hari Libur** — tanggal libur manual, bisa lewat rentang tanggal
  sekaligus (dari-sampai) atau lewat kalender tahunan (klik
  tanggal-tanggal tersebar, simpan sekaligus), plus hari libur mingguan
  otomatis berulang (mis. Sabtu-Minggu).
- **Pengaturan** — jam masuk/batas terlambat/mulai pulang/jam cek belum
  hadir, verifikasi lokasi GPS (titik sekolah + radius, dengan peta
  interaktif), verifikasi jaringan sekolah (daftar IP/CIDR buat audit,
  lihat bagian Keamanan), simulasi waktu (testing, disembunyikan dari UI
  produksi), libur mingguan.
- **Notifikasi Orang Tua** — riwayat semua notifikasi kehadiran/
  alpha/belum-hadir yang terkirim ke orang tua (kanal, status
  terkirim/gagal/tidak ada kontak).
- **Kelola Staff** — CRUD akun admin/wali kelas, assign kelas binaan.

## Otomatisasi & Background Jobs

- **`AbsensiAlphaChecker`** — tandai alpha siswa yang belum absen sampai
  lewat jam pulang, kirim notifikasi orang tua.
- **`AbsensiBelumHadirChecker`** — peringatan dini kalau siswa belum
  absen sampai jam yang ditentukan admin.
- **Notifikasi WhatsApp (Fonnte)** — kanal prioritas, dengan retry
  otomatis kalau gagal; email jadi cadangan.
- **Email antrian** (`QUEUE_CONNECTION=database`) — dikirim async lewat
  queue worker, dengan retry & backoff kalau gagal sesaat. Transport
  produksi lewat Resend (HTTP API), bukan SMTP mentah — Railway
  memblokir trafik SMTP keluar.
- **Backup terjadwal** (`spatie/laravel-backup`) — dump database +
  storage foto siswa, dibersihkan & dipantau otomatis.
- Semua di atas dijadwalkan lewat `routes/console.php`
  (`Schedule::command(...)`), dijalankan via Windows Task Scheduler di
  lokal (`schedule-run.bat` + `queue-work.bat`).

## Keamanan & Kontrol Akses

- Role-based access (admin vs wali kelas) lewat Policy, scoping
  konsisten ke kelas binaan di semua fitur yang relevan.
- Rate limiting di endpoint registrasi & lupa password (siswa & staf).
- Verifikasi lokasi GPS dengan lapisan anti-spoofing (akurasi minimum,
  deteksi pembacaan statis).
- Audit jaringan WiFi sekolah (IP) — sinyal tambahan lawan fake-GPS,
  murni pencatatan (badge di Kelola Absensi), tidak pernah menolak
  absen.
- Liveness detection (anti foto statis) di proses absen wajah.
- Audit log buat absensi yang dihapus admin.
