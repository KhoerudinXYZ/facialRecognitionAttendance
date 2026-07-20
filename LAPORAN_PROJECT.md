# LAPORAN PROJECT
## Aplikasi Absensi Wajah SMK (Face Recognition Attendance System)

---

## 1. Identitas Project

| Item | Keterangan |
|---|---|
| Nama Aplikasi | Absensi Wajah SMK |
| Jenis | Aplikasi Web (Sistem Informasi Absensi Siswa) |
| Metode Absensi | Pengenalan Wajah (Face Recognition) berbasis browser |
| Framework Utama | Laravel 12 (PHP 8.2+) |
| Basis Data | MySQL |
| Status | Sudah berjalan, memiliki fitur inti lengkap & pengujian otomatis (PHPUnit) |

---

## 2. Latar Belakang

Proses absensi siswa secara manual (tanda tangan / panggil nama) di lingkungan sekolah rawan terhadap masalah seperti titip absen, kesalahan pencatatan, dan proses rekap yang memakan waktu saat pembuatan laporan bulanan. Aplikasi ini dibangun untuk mengotomatisasi pencatatan kehadiran siswa menggunakan teknologi **pengenalan wajah**, sehingga siswa cukup menghadap kamera pada perangkat kiosk dan kehadiran tercatat otomatis beserta status **hadir** atau **terlambat**.

## 3. Tujuan

1. Mempercepat dan mengotomatisasi proses pencatatan kehadiran siswa.
2. Mengurangi kecurangan absensi (titip absen) dibanding sistem manual/tanda tangan.
3. Menyediakan rekap dan laporan kehadiran yang siap diekspor (Excel/PDF) untuk kebutuhan administrasi sekolah.
4. Memberi wali kelas/admin kontrol penuh atas data kelas, siswa, dan pengaturan jam masuk/terlambat.

---

## 4. Arsitektur Sistem

Prinsip desain utama aplikasi ini adalah **pemisahan tanggung jawab** antara browser dan server:

- **Browser (sisi klien)** — melakukan seluruh proses *machine learning*: deteksi wajah, ekstraksi *descriptor* wajah (vektor 128 dimensi), dan pencocokan wajah. Menggunakan library `@vladmandic/face-api` (fork dari `face-api.js`, berbasis TensorFlow.js) yang berjalan sepenuhnya di perangkat klien (kiosk).
- **Laravel (server/backend)** — **tidak pernah** memproses gambar maupun wajah secara langsung. Server hanya menyimpan *descriptor* (data numerik) yang telah dihitung oleh browser, serta mencatat dan melaporkan data kehadiran.

**Keuntungan desain ini:** beban komputasi ML (kamera, GPU/CPU) berada di perangkat kiosk, sehingga server tidak memerlukan library ML berat maupun akses langsung ke kamera — server tetap ringan dan fokus pada penyimpanan data serta pelaporan.

### Tumpukan Teknologi (Tech Stack)

| Lapisan | Teknologi |
|---|---|
| Backend Framework | Laravel 12 (PHP ^8.2) |
| Basis Data | MySQL |
| Autentikasi | Laravel Breeze (stack Blade) |
| Frontend | Blade Template + Tailwind CSS + Alpine.js |
| Build Tool | Vite |
| Face Recognition | `@vladmandic/face-api` v1.7.15 (client-side, TensorFlow.js) |
| Export Excel | `openspout/openspout` v5.7 |
| Export PDF | `barryvdh/laravel-dompdf` v3.1 |
| Testing | PHPUnit 11, dengan smoke test khusus alur absensi |

---

## 5. Model Data (Skema Basis Data)

```
kelas (1) ──< siswa (1) ──< face_descriptors
                  │
                  └──< absensi

pengaturan   (baris tunggal — konfigurasi jam masuk sekolah)
users        (akun admin/guru, login via Breeze)
```

| Tabel | Kolom Utama | Keterangan |
|---|---|---|
| **kelas** | nama_kelas, jurusan, tingkat (X/XI/XII), wali_kelas | Data rombongan belajar |
| **siswa** | nis, nisn, nama, jenis_kelamin, kelas_id, foto, is_active | Data induk siswa |
| **face_descriptors** | siswa_id, descriptor (JSON, 128 angka) | Satu siswa memiliki ±5 baris (sampel wajah dari sudut/ekspresi berbeda) agar pencocokan lebih akurat |
| **absensi** | siswa_id, tanggal, jam_masuk, status, metode, keterangan | Status: hadir/terlambat/izin/sakit/alpha; Metode: face/manual. Constraint unik `(siswa_id, tanggal)` — 1 siswa hanya 1 catatan per hari |
| **pengaturan** | nama_sekolah, jam_masuk, batas_terlambat | Selalu 1 baris; default jam masuk 07:00, batas terlambat 07:15 |
| **users** | + kolom `role` (admin/guru) | Tabel bawaan Breeze + tambahan peran |

---

## 6. Fitur Utama

1. **Manajemen Kelas** — tambah, ubah, hapus data kelas/rombel.
2. **Manajemen Siswa** — CRUD data siswa, termasuk **import massal** siswa (form import + unduh template).
3. **Pendaftaran Wajah (Face Enrollment)** — admin merekam 5 sampel wajah siswa lewat webcam untuk meningkatkan akurasi pengenalan, dengan opsi hapus & daftar ulang.
4. **Kiosk Absensi** — mode layar penuh di satu perangkat berkamera; siswa cukup menghadap kamera, sistem otomatis mendeteksi & mencocokkan wajah, lalu mencatat kehadiran (status hadir/terlambat berdasarkan jam masuk yang dikonfigurasi).
5. **Rekap Kehadiran** — tampilan harian dengan filter tanggal & kelas; siswa yang belum absen tetap tampil agar mudah ditindaklanjuti; mendukung **input manual** (izin/sakit/alpha) dan penghapusan data absensi (reset).
6. **Laporan Periode** — filter rentang tanggal & kelas, dengan **export Excel (.xlsx)** (stream langsung tanpa menyimpan file di server) dan **export PDF** (A4 landscape).
7. **Pengaturan** — admin dapat mengatur nama sekolah, jam masuk, dan batas waktu terlambat.
8. **Autentikasi & Otorisasi** — login admin/guru menggunakan Laravel Breeze; seluruh halaman inti dilindungi middleware `auth`.

---

## 7. Alur Kerja Sistem

### 7.1 Pendaftaran Wajah (Enrollment)
1. Admin menambah data siswa baru → otomatis diarahkan ke halaman **Daftar Wajah**.
2. Halaman enroll memuat model face-api (`tiny_face_detector`, `face_landmark_68`, `face_recognition`) dan menyalakan webcam.
3. Setiap klik "Ambil Sampel" menghasilkan 1 vektor deskriptor wajah (128 angka).
4. Setelah 5 sampel terkumpul, data dikirim ke server dan disimpan sebagai 5 baris di tabel `face_descriptors`.

### 7.2 Absensi via Kiosk (Alur Inti)
1. Saat halaman kiosk dibuka, server mengirim seluruh descriptor siswa aktif ke browser.
2. Browser membangun `FaceMatcher` (pencocokan berbasis jarak Euclidean, ambang `0.5`).
3. Loop deteksi berjalan terus-menerus: wajah yang terdeteksi dicocokkan dengan data tersimpan — kotak **hijau** (dikenali) atau **merah** (tidak dikenal) ditampilkan sebagai umpan balik visual.
4. Jika wajah dikenali dan tidak dalam masa cooldown (15 detik), browser mengirim `siswa_id` ke server.
5. Server memvalidasi: jika siswa sudah absen hari itu → tidak duplikat; jika belum, status ditentukan (hadir/terlambat) berdasarkan `batas_terlambat`, lalu disimpan.

> **Poin penting keamanan data:** logika penentuan status dan pencegahan duplikasi absensi sepenuhnya berada di server — browser hanya mengirim hasil identifikasi, sehingga tetap aman meskipun proses deteksi berjalan puluhan kali per detik.

### 7.3 Rekap, Input Manual & Laporan
- Rekap harian menggabungkan data siswa aktif dengan data absensi tanggal terkait (siswa yang belum absen tetap tampil).
- Guru/admin dapat menandai kehadiran secara manual untuk kasus offline (sistem wajah tidak dapat digunakan).
- Laporan periode dapat diekspor ke Excel maupun PDF sesuai filter tanggal dan kelas.

### Diagram Alur End-to-End

```
Admin ──► Tambah Kelas ──► Tambah Siswa ──► Enroll Wajah (5 sampel)
                                                     │
                                                     ▼
                                          tabel face_descriptors
                                                     │
                              Kiosk Absen dibuka (browser muat semua descriptor)
                                                     │
                                                     ▼
                                Loop kamera: deteksi & cocokkan wajah
                                (Euclidean distance < 0.5) → dapat siswa_id
                                                     │
                                                     ▼
                    Server: cek sudah absen? → tentukan status hadir/terlambat
                                                     │
                                                     ▼
                                        tabel absensi (1×/hari/siswa)
                                                     │
                                                     ▼
                                    Rekap & Laporan ──► Export Excel/PDF
```

---

## 8. Keamanan & Keterbatasan

| Aspek | Keterangan |
|---|---|
| **Anti-spoofing** | Belum ada — face-api.js tidak dapat membedakan wajah asli dengan foto/layar. Cukup memadai untuk lingkungan terawasi (kiosk sekolah), belum untuk kebutuhan keamanan tinggi. |
| **Data biometrik** | Yang disimpan adalah vektor angka (descriptor), bukan foto wajah — namun tetap tergolong data pribadi/biometrik sensitif dan perlu perlakuan khusus sesuai kaidah perlindungan data. |
| **Kebutuhan HTTPS** | Kamera (`getUserMedia`) hanya aktif di `localhost` atau melalui HTTPS — kebijakan standar browser modern. |
| **Ambang kecocokan** | `MATCH_THRESHOLD = 0.5` adalah nilai trade-off antara *false-accept* dan *false-reject*, dapat disesuaikan sesuai kebutuhan lapangan. |
| **Duplikasi absensi** | Dicegah berlapis: constraint unik di database + pengecekan logika di server. |

---

## 9. Instalasi & Menjalankan (Ringkasan Teknis)

```bash
composer install
npm install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link

# Jalankan (2 terminal)
php artisan serve
npm run dev
```

Akun default (dari seeder): `admin@smk.test` / `password`

---

## 10. Kesimpulan

Aplikasi Absensi Wajah SMK berhasil mengimplementasikan sistem absensi otomatis berbasis pengenalan wajah dengan arsitektur yang efisien — seluruh pemrosesan wajah dilakukan di sisi klien (browser), sementara server berfokus pada penyimpanan data dan pelaporan. Desain ini membuat sistem ringan dijalankan tanpa memerlukan infrastruktur server yang berat, sekaligus tetap menjaga integritas data melalui validasi dan constraint di level backend.

## 11. Saran Pengembangan Lanjutan

1. Menambahkan mekanisme **anti-spoofing** (deteksi kedipan mata/gerakan kepala) untuk mencegah kecurangan menggunakan foto.
2. Notifikasi otomatis ke orang tua/wali (WhatsApp/Email) saat siswa tercatat hadir/terlambat/alpha.
3. Dashboard statistik kehadiran (grafik tren kehadiran per kelas/periode).
4. Mode **multi-kiosk** dengan sinkronisasi data absensi secara real-time antar perangkat.
5. Audit log untuk perubahan data absensi manual (siapa mengubah, kapan, dan alasannya).

---

*Laporan ini disusun berdasarkan hasil eksplorasi kode sumber, dokumentasi teknis (`README.md`, `ALUR_KERJA.md`), dan struktur basis data project.*
