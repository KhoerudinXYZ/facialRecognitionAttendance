# Alur Kerja Aplikasi — Absensi Wajah SMK

Dokumen ini menjelaskan secara detail bagaimana aplikasi ini bekerja: arsitektur, alur data, dan alur pemakaian dari sisi teknis.

## 1. Gambaran Umum

Aplikasi absensi siswa berbasis **pengenalan wajah**, dengan pembagian tanggung jawab yang jelas:

- **Browser (JavaScript)** — melakukan semua kerja *machine learning*: deteksi wajah, ekstraksi *descriptor* 128 dimensi, dan pencocokan wajah. Menggunakan library `@vladmandic/face-api` (fork `face-api.js`, berbasis TensorFlow.js) yang berjalan sepenuhnya di sisi klien.
- **Laravel (backend)** — tidak pernah memproses gambar/wajah. Ia hanya menyimpan *descriptor* (array angka) yang sudah dihitung oleh browser, dan mencatat/melaporkan kehadiran.

Alasan desain ini: kamera & GPU/CPU untuk face recognition dijalankan di perangkat kiosk, sehingga server tidak butuh library ML berat maupun akses langsung ke kamera.

## 2. Arsitektur & Tumpukan Teknologi

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 12 (PHP 8.2+), MySQL |
| Auth | Laravel Breeze (Blade stack) |
| Frontend | Blade + Tailwind CSS + Alpine.js |
| Build | Vite |
| Face recognition | `@vladmandic/face-api` (client-side, TensorFlow.js) |
| Export Excel | `openspout/openspout` |
| Export PDF | `barryvdh/laravel-dompdf` |

## 3. Model Data (Skema Database)

```
kelas (1) ──< siswa (1) ──< face_descriptors
                  │
                  └──< absensi

pengaturan  (baris tunggal, konfigurasi jam masuk)
users       (admin/guru, login Breeze)
```

### `kelas`
`id, nama_kelas, jurusan, tingkat (X/XI/XII), wali_kelas`

### `siswa`
`id, nis, nisn, nama, jenis_kelamin (L/P), kelas_id, foto, is_active`
- `foto` disimpan di `storage/app/public/siswa` (link publik via `php artisan storage:link`).

### `face_descriptors`
`id, siswa_id, descriptor (JSON — array 128 float), timestamps`
- Satu siswa punya **banyak baris** (idealnya 5, lihat `SAMPLE_TARGET` di `face-enroll.js`) — tiap baris adalah satu *sampel* wajah dari sudut/ekspresi berbeda, supaya pencocokan lebih toleran.

### `absensi`
`id, siswa_id, tanggal, jam_masuk, status (hadir/terlambat/izin/sakit/alpha), metode (face/manual), keterangan, timestamps`
- **Unique constraint** `(siswa_id, tanggal)` → satu siswa hanya boleh punya **satu baris absensi per hari**, ditegakkan di level database.

### `pengaturan`
`id, nama_sekolah, jam_masuk, batas_terlambat`
- Selalu hanya **satu baris**; diambil/dibuat otomatis lewat `Pengaturan::get()` (firstOrCreate), default `jam_masuk=07:00`, `batas_terlambat=07:15`.

### `users`
Tambahan kolom `role` (admin/guru) di atas tabel bawaan Breeze.

## 4. Alur Pendaftaran Wajah (Enrollment)

File terkait: `SiswaController`, `FaceEnrollmentController`, `resources/js/face-enroll.js`, `resources/js/face-common.js`.

1. Admin membuka **Siswa → Tambah Siswa** (`SiswaController@store`) — mengisi NIS, nama, kelas, dll. Setelah tersimpan, otomatis diarahkan ke halaman **Daftar Wajah** (`route('siswa.enroll', $siswa)`).
2. Halaman enroll (`GET siswa/{siswa}/enroll` → `SiswaController@enroll`) memuat `face-enroll.js`, yang:
   a. Memanggil `loadModels()` — mengunduh 3 model dari `/models` (`tiny_face_detector`, `face_landmark_68`, `face_recognition`) via `faceapi.nets.*.loadFromUri()`.
   b. Menyalakan webcam via `getUserMedia` (`startCamera`).
   c. Setiap klik **"Ambil Sampel"** memanggil `getSingleDescriptor(video)` → `faceapi.detectSingleFace().withFaceLandmarks().withFaceDescriptor()`, menghasilkan **vektor 128 angka float** yang merepresentasikan wajah pada frame saat itu.
   d. Proses diulang hingga **5 sampel** terkumpul (`SAMPLE_TARGET`), agar variasi pose/ekspresi meningkatkan akurasi pencocokan nanti.
3. Klik **"Simpan Wajah"** mengirim seluruh array descriptor via `fetch POST` ke `route('face.store')` (`FaceEnrollmentController@store`).
4. Backend memvalidasi: setiap descriptor harus array 128 angka numerik (`descriptors.*` size:128), lalu menyimpan **satu baris per descriptor** ke tabel `face_descriptors`.
5. `siswa.enroll` juga menyediakan tombol **hapus** yang memanggil `DELETE siswa/{siswa}/face` (`FaceEnrollmentController@destroy`) — menghapus semua descriptor siswa itu untuk pendaftaran ulang.

> Backend tidak pernah melihat gambar — hanya angka hasil ekstraksi model di browser.

## 5. Alur Absensi via Kiosk (Inti Aplikasi)

File terkait: `AbsensiController@kiosk` & `@store`, `resources/js/face-kiosk.js`.

### 5.1 Memuat halaman kiosk

`GET absensi/kiosk` → `AbsensiController@kiosk`:
- Mengambil **semua siswa aktif yang sudah punya minimal 1 face descriptor**.
- Untuk tiap siswa, mengumpulkan seluruh descriptor miliknya menjadi list.
- Mengirim data ini ke view sebagai JSON (`data-labeled` di elemen `#kiosk-app`) — artinya **seluruh descriptor semua siswa dikirim ke browser sekaligus** saat halaman kiosk dibuka.

### 5.2 Loop pengenalan real-time (di browser)

`face-kiosk.js` setelah model & kamera siap:

1. `buildMatcher()` mengubah data JSON tadi menjadi `faceapi.FaceMatcher` — struktur pencocokan berbasis **jarak Euclidean** dengan ambang (`MATCH_THRESHOLD = 0.5`).
2. Loop `requestAnimationFrame` berjalan terus-menerus selama kamera aktif:
   - `faceapi.detectAllFaces(video).withFaceLandmarks().withFaceDescriptors()` — deteksi **semua wajah** yang tampak di frame video saat itu.
   - Untuk tiap wajah terdeteksi, `faceMatcher.findBestMatch(descriptor)` mencari siswa dengan descriptor tersimpan **paling dekat** (jarak Euclidean terkecil). Jika jarak > `0.5`, hasilnya `unknown`.
   - Kotak kotak (bounding box) digambar di kanvas overlay: **hijau** jika dikenali, **merah** jika tidak dikenal — sebagai umpan balik visual real-time.
3. Jika wajah dikenali (`isMatch`), dan **belum dalam masa cooldown** 15 detik (`COOLDOWN_MS`) untuk siswa tsb, browser memanggil `recordAttendance(siswaId)`.

### 5.3 Mencatat kehadiran (backend)

`POST absensi/kiosk` → `AbsensiController@store`:
1. Validasi `siswa_id` ada di tabel `siswa`.
2. Cek apakah siswa **sudah absen hari ini** (`WHERE siswa_id AND tanggal = today`). Jika sudah → balas `status: already`, tidak membuat baris baru (mencegah duplikat, walau constraint DB juga menjaga ini).
3. Jika belum, ambil `Pengaturan::get()->batas_terlambat`, bandingkan dengan jam sekarang:
   - Jika sekarang **> batas terlambat** → status `terlambat`.
   - Jika tidak → status `hadir`.
4. Simpan baris baru di `absensi` dengan `metode = 'face'`.
5. Browser menampilkan toast hijau (sukses) atau kuning (sudah absen sebelumnya) berdasarkan respons.

**Poin penting:** logika "sudah absen atau belum" dan "hadir vs terlambat" sepenuhnya di server — browser hanya mengirim `siswa_id` hasil pencocokan wajah, jadi meski loop deteksi berjalan puluhan kali per detik, setiap siswa maksimal tercatat sekali per hari.

## 6. Alur Rekap & Absensi Manual

`GET absensi` → `AbsensiController@index`:
- Menampilkan semua siswa aktif (bisa difilter per kelas & tanggal), digabung (left-join secara manual di PHP) dengan data absensi tanggal tsb — siswa yang belum absen tetap muncul di rekap (dengan `absensi = null`).

`POST absensi/manual` → `AbsensiController@manual`:
- Untuk kasus offline: guru/admin menandai siswa **izin/sakit/alpha/hadir/terlambat** secara manual.
- Menggunakan `updateOrCreate` berdasarkan `(siswa_id, tanggal)` — jika baris sudah ada (misal dari absen wajah), akan **ditimpa** oleh input manual (`metode = 'manual'`).

`DELETE absensi/{absensi}` → `AbsensiController@destroy`:
- Menghapus satu baris absensi (reset, biar siswa bisa absen ulang hari itu).

## 7. Alur Laporan & Export

`GET laporan` → `LaporanController@index`:
- Filter berdasarkan rentang tanggal (`dari`–`sampai`, default: awal bulan s/d hari ini) dan kelas.

`GET laporan/excel` → `exportExcel()`:
- Query sama, ditulis langsung sebagai **stream XLSX** (`openspout`) tanpa menyimpan file di server (`response()->streamDownload`).

`GET laporan/pdf` → `exportPdf()`:
- Merender view `laporan.pdf` melalui `barryvdh/laravel-dompdf`, kertas A4 landscape, lalu diunduh.

## 8. Pengaturan

`GET/PUT pengaturan` → `PengaturanController`:
- Admin mengatur `nama_sekolah`, `jam_masuk`, dan `batas_terlambat` (format `H:i`).
- Nilai `batas_terlambat` inilah yang dipakai `AbsensiController@store` untuk menentukan status `hadir` vs `terlambat`.

## 9. Autentikasi

Menggunakan **Laravel Breeze** standar (`routes/auth.php`): login, register, reset password, verifikasi email, konfirmasi password. Semua route inti aplikasi (`kelas`, `siswa`, `absensi`, `laporan`, `pengaturan`, `profile`) dibungkus middleware `auth` — wajib login sebagai admin/guru.

## 10. Ringkasan Alur End-to-End

```
┌─────────────┐   1. Tambah kelas       ┌──────────────┐
│   Admin     │ ───────────────────────▶│ tabel kelas  │
└─────────────┘                          └──────────────┘
      │ 2. Tambah siswa
      ▼
┌──────────────┐  3. Rekam 5 sampel wajah (browser, face-api.js)
│ Enroll wajah │ ─────────────────────────────────────────────┐
└──────────────┘                                               ▼
                                                    ┌────────────────────┐
                                                    │ face_descriptors   │
                                                    │ (128-d vector × N) │
                                                    └────────────────────┘
                                                               │
      4. Buka Kiosk Absen (browser memuat SEMUA descriptor)   │
      ┌────────────────────────────────────────────────────────┘
      ▼
┌───────────────────────────────────────────┐
│ Loop kamera: deteksi wajah → cocokkan      │
│ (Euclidean distance < 0.5) → dapat siswa_id│
└───────────────────────────────────────────┘
      │ 5. POST siswa_id ke server
      ▼
┌───────────────────────────────────────────┐
│ Server: sudah absen hari ini? cek jam vs   │
│ batas_terlambat → simpan status hadir/     │
│ terlambat ke tabel absensi (1×/hari)       │
└───────────────────────────────────────────┘
      │
      ▼
┌─────────────┐   6. Rekap / Laporan   ┌──────────────────────┐
│ tabel absensi│ ─────────────────────▶│ Export Excel / PDF   │
└─────────────┘                         └──────────────────────┘
```

## 11. Catatan Keamanan & Keterbatasan

- **Tidak ada anti-spoofing**: face-api.js tidak mendeteksi apakah objek di depan kamera adalah wajah asli atau foto/layar. Cukup untuk lingkungan terawasi (kiosk sekolah), bukan untuk kasus keamanan tinggi.
- **Data descriptor bukan gambar**: yang tersimpan di database adalah vektor matematis (128 float), bukan foto wajah — namun tetap merupakan data biometrik yang sensitif dan sebaiknya diperlakukan sebagai data pribadi.
- **Kamera butuh HTTPS** (kecuali di `localhost`), sesuai kebijakan browser modern untuk `getUserMedia`.
- **Ambang kecocokan** (`MATCH_THRESHOLD = 0.5`) adalah trade-off antara false-accept dan false-reject; bisa disetel di `resources/js/face-kiosk.js`.
