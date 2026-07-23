# Bahan Slide Presentasi — Aplikasi Absensi Wajah
### Untuk dipresentasikan kepada guru-guru di sekolah

> Format: tiap `## Slide N` = satu slide di PowerPoint/Google Slides. Judul slide = judul di file ini, bullet di bawahnya = isi slide (jangan terlalu banyak teks per slide, cukup kata kunci — jelaskan detailnya secara lisan). Bagian *Catatan pembicara* tidak perlu ditulis di slide, itu untuk pegangan Anda saat presentasi.

---

## Slide 1 — Judul
**Aplikasi Absensi Siswa Berbasis Pengenalan Wajah**
- [Nama Sekolah]
- Disampaikan kepada: Dewan Guru
- [Nama presenter] — [Tanggal]

*Catatan pembicara: perkenalan singkat, tujuan presentasi hari ini.*

---

## Slide 2 — Masalah yang Kita Hadapi Sekarang
- Absensi manual rawan **titip absen / titip tanda tangan**
- Rekap bulanan **memakan waktu lama**
- Guru sering **terlambat tahu** siswa tidak masuk tanpa keterangan
- Data kehadiran untuk laporan ke orang tua/dinas kurang rapi

*Catatan pembicara: bisa tanya guru pengalaman nyata mereka soal titip absen / rekap manual.*

---

## Slide 3 — Solusi: Absensi Wajah
- Siswa absen dengan **menghadap kamera** di HP/laptop masing-masing
- Sistem otomatis mengenali wajah & mencatat **jam masuk & jam pulang**
- Status **Hadir / Terlambat** ditentukan otomatis sesuai jam yang ditetapkan sekolah
- Siswa diminta **berkedip** dulu — foto di layar HP tidak akan lolos
- Tidak perlu antre di satu titik/kiosk

---

## Slide 4 — Bagaimana Cara Kerjanya (Sederhana)
1. Siswa daftar sekali (klaim NIS + buat akun)
2. Siswa rekam wajah sekali (beberapa foto lewat kamera)
3. Setiap hari: buka halaman absen → hadapkan wajah → selesai
4. Data langsung masuk ke sistem sekolah

*Catatan pembicara: tekankan bahwa proses pengenalan wajah terjadi di HP/laptop siswa sendiri, bukan dikirim sebagai foto ke server — jadi lebih aman dan ringan.*

---

## Slide 5 — Fitur untuk Wali Kelas & Admin
- Kelola data **kelas** & **siswa** (termasuk impor Excel massal)
- **Rekap kehadiran harian**, bisa input manual untuk kondisi khusus
- **Setuju/Tolak** pengajuan izin & sakit siswa secara daring (dengan bukti)
- **Laporan periode** — tinggal unduh dalam format Excel atau PDF

---

## Slide 6 — Fitur untuk Siswa (Portal Mandiri)
- Registrasi & daftar wajah cukup sekali
- Absen mandiri dari HP masing-masing
- Bisa **ajukan izin/sakit** online (unggah bukti/surat)
- Bisa lihat **riwayat kehadiran** sendiri

---

## Slide 7 — Pengaturan Fleksibel oleh Sekolah
- Jam masuk & batas terlambat bisa diatur sendiri
- **Hari libur**: tanggal manual, atau centang sekali untuk libur mingguan (Sabtu/Minggu) — tidak perlu diulang tiap minggu
- **(Opsional)** Verifikasi lokasi GPS — absen ditolak jika siswa di luar radius area sekolah, titik & radius sekolah tinggal diklik di peta

---

## Slide 8 — Yang Terjadi Otomatis di Belakang Sistem
- Siswa yang **tidak absen sampai akhir hari** otomatis ditandai **Alpha**
- **Email ke orang tua**: otomatis saat anak tiba di sekolah (konfirmasi hadir) *dan* saat alpha — kalau email orang tua sudah didaftarkan
- **Email ke wali kelas**: otomatis saat ada siswa binaannya mengajukan izin/sakit baru
- Backup data (database + foto) berjalan otomatis setiap hari

---

## Slide 9 — Keamanan & Akuntabilitas Data
- Data yang disimpan bukan foto wajah, tapi kode angka hasil pengenalan wajah
- Login guru/staf dan login siswa **benar-benar terpisah**
- Satu siswa hanya bisa tercatat **1 kali per hari** (dicegah sistem, tidak bisa dobel)
- Setiap data absensi yang **dihapus tetap ada jejaknya** (siapa & kapan menghapus)

---

## Slide 10 — Keterbatasan yang Perlu Diketahui
- Deteksi "wajah hidup" saat ini baru sebatas **kedipan mata** — belum ada deteksi gerakan kepala, jadi belum 100% anti video/rekaman canggih
- Cukup memadai untuk lingkungan sekolah yang terawasi, tapi bukan sistem keamanan tingkat tinggi
- Kamera hanya aktif di jaringan yang aman (HTTPS) — bukan keterbatasan aplikasi, tapi standar keamanan browser

---

## Slide 11 — Manfaat bagi Sekolah
| Untuk | Manfaat |
|---|---|
| Wali Kelas | Rekap otomatis, approve izin lebih cepat |
| Guru/Admin | Laporan siap unduh, tidak perlu rekap manual |
| Siswa | Absen cepat, tidak antre |
| Orang Tua | Tahu lebih cepat kalau anaknya tidak hadir |

---

## Slide 12 — Rencana Pengembangan Selanjutnya
- Notifikasi via WhatsApp, tidak hanya email
- Deteksi "wajah hidup" yang lebih kuat (tambah gerakan kepala, bukan cuma kedipan)
- Dashboard grafik tren kehadiran per kelas (saat ini sudah ada tren 7 hari umum)

---

## Slide 13 — Penutup & Diskusi
- Sistem sudah siap digunakan untuk uji coba
- Terbuka untuk masukan dari dewan guru sebelum diterapkan penuh
- **Sesi tanya-jawab**

*Catatan pembicara: siapkan demo langsung (live) di HP/laptop kalau memungkinkan — jauh lebih meyakinkan daripada slide.*
