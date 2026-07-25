# Alur Kerja Aplikasi — Absensi Wajah SMK

Dokumen ini menjelaskan secara detail bagaimana aplikasi ini bekerja: arsitektur, alur data, dan alur pemakaian dari sisi teknis.

> **Riwayat revisi:** dokumen ini sempat menjelaskan mode "kiosk kamera bersama"
> sebagai alur inti — fitur itu **sudah dihapus** dari aplikasi. Absen wajah
> sekarang sepenuhnya **mandiri lewat HP masing-masing siswa** (lihat §5).
> Versi ini juga menambahkan modul yang belum ada di revisi sebelumnya:
> liveness (kedip mata), verifikasi lokasi GPS, pengajuan izin/sakit/pulang
> cepat, alpha checker otomatis, dan notifikasi email ke orang tua.

## 1. Gambaran Umum

Aplikasi absensi siswa berbasis **pengenalan wajah**, dengan pembagian tanggung jawab yang jelas:

- **Browser di HP siswa (JavaScript)** — melakukan semua kerja *machine learning* secara real-time: deteksi wajah, ekstraksi *descriptor* 128 dimensi, pencocokan wajah, **dan verifikasi kedipan mata (liveness)**. Menggunakan `@vladmandic/face-api` (fork `face-api.js`, berbasis TensorFlow.js) yang berjalan sepenuhnya di sisi klien, dengan backend `wasm` (bukan `webgl`) — dipilih setelah ditemukan sebagian GPU Android punya driver WebGL yang sangat lambat untuk model ini.
- **Laravel (backend)** — tidak pernah memproses gambar/wajah. Ia hanya menerima *descriptor* (array angka) yang sudah dihitung browser saat pendaftaran, dan menerima hasil pencocokan (siswa mana yang cocok) saat absen — lalu menjalankan **seluruh logika bisnis**: cek hari libur, cek sudah absen atau belum, hitung status hadir/terlambat/pulang, validasi radius lokasi, dan kirim notifikasi.

Alasan desain ini: kamera & GPU/CPU untuk face recognition dijalankan di perangkat siswa sendiri, sehingga server tidak butuh library ML berat maupun akses langsung ke kamera siapa pun.

## 2. Arsitektur & Tumpukan Teknologi

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 12 (PHP 8.2+), MySQL |
| Auth | Dua sistem terpisah — Laravel Breeze (staff) & guard kustom `siswa` |
| Frontend | Blade + Tailwind CSS |
| Build | Vite |
| Face recognition | `@vladmandic/face-api` (client-side, TensorFlow.js, backend `wasm`) |
| Queue | Database queue (`QUEUE_CONNECTION=database`) — dipakai notifikasi email |
| Mail | SMTP (Gmail), dikirim via `->queue()` supaya tidak memblokir request |
| Export Excel | `openspout/openspout` |
| Export PDF | `barryvdh/laravel-dompdf` |
| Backup | `spatie/laravel-backup`, terjadwal harian |

## 3. Model Data (Skema Database)

```
kelas (1) ──< siswa (1) ──< face_descriptors
                  │    ├──< absensi
                  │    └──< pengajuan_izin

pengaturan            (baris tunggal, konfigurasi jam & lokasi)
hari_libur            (tanggal libur manual)
notifikasi_absensi_log(audit email yang terkirim)
absensi_audit_log     (jejak penghapusan data absensi)
users                 (admin/wali kelas, login Breeze)
```

### `kelas`
`id, nama_kelas, jurusan, tingkat (X/XI/XII), wali_kelas_id (FK → users)`

### `siswa`
`id, nis, nisn, nama, jenis_kelamin (L/P), kelas_id, foto, is_active, email, password (login siswa), email_orang_tua, no_hp_orang_tua`
- `foto` disimpan di `storage/app/public/siswa` (link publik via `php artisan storage:link`).
- `email_orang_tua` dipakai untuk notifikasi email. `no_hp_orang_tua` **sudah tersedia di skema** untuk notifikasi WhatsApp, tapi kanal WA belum diimplementasikan — saat ini semua notifikasi lewat email.

### `face_descriptors`
`id, siswa_id, descriptor (JSON — array 128 float), timestamps`
- Satu siswa punya **banyak baris** (idealnya 5, lihat `SAMPLE_TARGET` di `face-enroll.js`) — tiap baris adalah satu *sampel* wajah dari sudut/ekspresi berbeda, supaya pencocokan lebih toleran.

### `absensi`
`id, siswa_id, kelas_id, tanggal, jam_masuk, jam_pulang, status (hadir/terlambat/izin/sakit/alpha), metode (face/manual), liveness_verified, keterangan, timestamps`
- **Unique constraint** `(siswa_id, tanggal)` → satu siswa hanya boleh punya **satu baris absensi per hari**, ditegakkan di level database.
- `liveness_verified` mencatat apakah absen itu lolos verifikasi kedip mata.

### `pengajuan_izin`
`id, siswa_id, tanggal, jenis (izin/sakit/pulang_cepat), keterangan, bukti (nullable), status (menunggu/disetujui/ditolak), catatan_admin, reviewed_by (FK → users), reviewed_at, timestamps`
- **Unique constraint** `(siswa_id, tanggal)` — satu siswa satu pengajuan per hari.
- Izin/sakit yang **disetujui** langsung menulis baris di `absensi` dengan status sesuai. Pulang cepat yang disetujui melonggarkan aturan jam pulang di `AbsensiRecorder` (lihat §6).

### `hari_libur`
`id, tanggal, keterangan` — dicek `HariLibur::isLibur()` sebelum absen apa pun diproses (mandiri maupun manual).

### `pengaturan`
`id, nama_sekolah, jam_masuk, batas_terlambat, mulai_pulang, simulasi_waktu (nullable, testing-only), lokasi_lat, lokasi_lng, lokasi_radius_meter, hari_libur_mingguan (array, mis. Sabtu/Minggu)`
- Selalu hanya **satu baris**, diambil/dibuat otomatis lewat `Pengaturan::get()`.
- `lokasi_*` opsional — kalau diisi, absen mandiri siswa wajib mengirim koordinat GPS dalam radius tsb (lihat §6.3).

### `notifikasi_absensi_log`
`id, siswa_id, siswa_nama, tanggal, jenis (kehadiran/alpha), kontak, pesan, status (terkirim/gagal/tidak_ada_kontak)` — audit trail tiap email yang (dicoba) dikirim.

### `absensi_audit_log`
Mencatat siapa & kapan menghapus baris `absensi` — murni untuk akuntabilitas, bukan tempat mengembalikan data (lihat `AbsensiController::audit()`).

### `users`
Kolom `role` (admin/wali_kelas) di atas tabel bawaan Breeze.

## 4. Autentikasi (Dua Sistem Terpisah)

- **Staff (admin/wali kelas)** — Laravel Breeze standar (`routes/auth.php`), guard `web`. Middleware `auth` untuk semua route inti, `role:admin` untuk yang admin-only (kelola kelas, staff, pengaturan, hari libur, audit, log notifikasi).
- **Siswa** — guard kustom `siswa` (`routes/siswa.php`, prefix `/portal`), lengkap dengan register, login, lupa password, dan profil sendiri. Sengaja **dipisah total** dari staff supaya siswa tidak bisa mengakses apa pun di sisi admin.
- Halaman root (`/`) mengarahkan otomatis: staff login → dashboard admin, siswa login → dashboard siswa, tamu → **halaman login siswa** (karena siswa jauh lebih sering akses harian daripada staff).

## 5. Alur Pendaftaran Wajah (Enrollment)

Ada dua jalur, tergantung siapa yang mendaftarkan:

**A. Oleh admin saat menambah siswa baru** — `SiswaController@enroll` (`GET siswa/{siswa}/enroll`), memuat `face-enroll.js`.

**B. Oleh siswa sendiri lewat portal** — `SiswaFaceEnrollmentController` (`GET/POST portal/enroll`), siswa yang belum enroll diarahkan otomatis ke sini.

Kedua jalur memakai mekanisme sama di `resources/js/face-common.js`:
1. `loadModels()` — unduh 3 model dari `/models` (`tiny_face_detector`, `face_landmark_68`, `face_recognition`).
2. Nyalakan webcam via `getUserMedia`.
3. Tiap klik "Ambil Sampel" → `getSingleDescriptor(video)` → vektor 128 angka float.
4. Ulangi hingga **5 sampel** (`SAMPLE_TARGET`) untuk variasi pose/ekspresi.
5. Kirim seluruh array descriptor ke `FaceEnrollmentController@store` — backend validasi tiap descriptor harus array 128 angka (`descriptors.*` size:128), simpan **satu baris per descriptor**.

> Backend tidak pernah melihat gambar — hanya angka hasil ekstraksi model di browser.

## 6. Alur Absen Mandiri Siswa (Inti Aplikasi)

File terkait: `SiswaAbsensiController@create/@store`, `app/Services/AbsensiRecorder.php`, `resources/js/face-kiosk.js`, `resources/js/face-liveness.js`, `resources/js/face-common.js`.

> Diagram lengkap langkah-demi-langkah: lihat **ALUR-DEMO-ABSEN-MANDIRI.md**.

### 6.1 Memuat halaman absen

`GET portal/absen` → `SiswaAbsensiController@create`:
- Mengambil descriptor wajah **siswa yang sedang login itu sendiri** beserta seluruh siswa lain yang aktif (untuk pencocokan/anti-tertukar), dikirim ke browser sebagai JSON (`data-labeled`).
- Mengirim status `lokasiAktif` (dari `Pengaturan::lokasiAktif()`) dan status kunci kamera (kalau di luar jam operasional).

### 6.2 Loop pengenalan real-time (di browser)

`face-kiosk.js` setelah model dimuat & backend `wasm` diaktifkan:
1. `buildMatcher()` → `faceapi.FaceMatcher` berbasis jarak Euclidean (`MATCH_THRESHOLD = 0.5`).
2. Warm-up sekali di layar loading — supaya biaya kompilasi shader/kernel pertama kali tidak jatuh ke saat siswa sedang mencoba absen.
3. Loop `requestAnimationFrame`: deteksi wajah ringan tiap ~100ms (posisi kotak), deteksi berat (landmark + descriptor) tiap ~250ms **hanya kalau ada wajah terdeteksi** — desain dua-kecepatan ini menghindari membebani device terus-menerus.
4. Kotak digambar di kanvas: hijau (dikenali), merah (tidak dikenal), abu-abu (baru mendeteksi).
5. Kalau wajah cocok: cek **jarak ke kamera cukup dekat** (`MIN_FACE_WIDTH_RATIO`) — supaya landmark mata cukup detail untuk liveness — lalu cek **lokasi GPS** (kalau aktif), baru lanjut ke **verifikasi kedip mata** (`BlinkTracker` di `face-liveness.js`, berbasis Eye Aspect Ratio/EAR, adaptif per sesi terhadap baseline mata terbuka orang itu sendiri).
6. Begitu kedip terverifikasi (atau, tergantung konfigurasi terkini, setelah menunggu tanpa hasil), browser mengirim `POST portal/absen` dengan `siswa_id`, `liveness_verified`, dan koordinat lokasi (kalau ada).

### 6.3 Mencatat kehadiran (backend, `AbsensiRecorder::record()`)

Dipakai bersama oleh absen mandiri siswa — satu-satunya jalur pencatatan absen wajah di aplikasi ini saat ini:

1. **Cek hari libur** (`HariLibur::isLibur()`) → tolak kalau libur.
2. **Cek sudah ada baris absensi hari ini?** Kalau statusnya izin/sakit (disetujui manual) → tolak, tidak boleh ketiban hasil scan wajah.
3. **Cek jam sekarang vs `mulai_pulang`**: kalau belum ada absen masuk dan sudah lewat `mulai_pulang` → tolak (jam absen ditutup), *kecuali* baris itu berstatus `alpha` (ditulis otomatis oleh Alpha Checker) — itu tetap boleh ditimpa jadi hadir/terlambat kalau siswanya ternyata datang.
4. **Cek lokasi** (kalau `Pengaturan::lokasiAktif()`) — hitung jarak haversine ke titik sekolah, tolak kalau di luar radius atau koordinat tidak terkirim.
5. **Belum ada absen masuk** → bandingkan jam sekarang vs `batas_terlambat` → status `hadir` atau `terlambat`. Simpan, lalu kirim **notifikasi email kehadiran** ke orang tua (di-*queue*, tidak memblokir response).
6. **Sudah ada absen masuk, belum pulang** → kalau sebelum `mulai_pulang`, cek dulu apakah ada **pengajuan pulang cepat yang disetujui** hari itu; kalau tidak ada, tolak. Kalau lolos, catat `jam_pulang`.

**Poin penting:** seluruh logika "sudah absen atau belum", "hadir vs terlambat", dan "boleh pulang cepat atau tidak" ada di server — browser hanya mengirim hasil pencocokan wajah + liveness, jadi tetap konsisten meski deteksi berjalan berkali-kali per detik.

## 7. Alur Pengajuan Izin / Sakit / Pulang Cepat

File terkait: `SiswaPengajuanIzinController` (siswa), `PengajuanIzinController` (admin/wali kelas).

1. Siswa mengisi form (`GET/POST portal/izin`) — jenis (izin/sakit/pulang cepat), keterangan, dan bukti (opsional untuk pulang cepat, wajib untuk izin/sakit tergantung validasi terkini).
2. Tersimpan sebagai `pengajuan_izin` berstatus `menunggu`. Wali kelas/admin dapat **notifikasi email** otomatis.
3. Admin/wali kelas meninjau di `GET pengajuan-izin` → approve/reject.
   - **Izin/sakit disetujui** → langsung menulis baris `absensi` berstatus sesuai untuk tanggal itu.
   - **Pulang cepat disetujui** → tidak langsung menulis absensi, tapi melonggarkan pengecekan jam di `AbsensiRecorder` saat siswa itu absen pulang lebih awal (lihat §6.3 poin 6).

## 8. Alpha Checker & Notifikasi Otomatis

File terkait: `app/Services/AbsensiAlphaChecker.php`, dijadwalkan di `routes/console.php`.

- Berjalan **tiap jam** (`hourly`, rentang `12:00`–`22:00`), tapi **tidak memproses apa pun** sampai jam sekarang ≥ `Pengaturan::mulai_pulang` — titik yang sama dengan penutupan absen masuk di `AbsensiRecorder`, supaya siswa yang benar-benar tidak hadir langsung resmi *alpha* di hari yang sama.
- Siswa aktif yang sampai saat itu belum punya baris absensi hari ini, dan tidak sedang punya pengajuan izin berstatus `menunggu`, ditandai `alpha` — lalu (kalau email orang tua terdaftar) dikirimi notifikasi.
- Butuh **cron server** (`* * * * * php artisan schedule:run`) supaya benar-benar jalan otomatis; kalau tidak ada, jalankan manual: `php artisan absensi:cek-alpha`.
- Pengiriman email **belum konsisten** soal queue: hanya notifikasi kehadiran (`SiswaHadirMail`, di `AbsensiRecorder`) yang memakai `Mail::queue()` — butuh **queue worker berjalan** (`php artisan queue:work` atau `composer run dev`) supaya benar-benar terkirim, bukan cuma menumpuk di tabel `jobs`. Notifikasi alpha (`SiswaAlphaMail`) dan izin baru (`PengajuanIzinBaruMail`) masih memakai `->send()` sinkron — dikirim langsung dalam request/command yang sama, jadi tidak butuh queue worker, tapi juga berarti request/command itu ikut menunggu proses kirim email selesai.

## 9. Alur Rekap & Absensi Manual

`GET absensi` → `AbsensiController@index`:
- Menampilkan semua siswa aktif (bisa difilter per kelas & tanggal), digabung dengan data absensi tanggal tsb — siswa yang belum absen tetap muncul di rekap (dengan `absensi = null`).

`POST absensi/manual` → `AbsensiController@manual`:
- Untuk kasus offline: admin/wali kelas menandai siswa **izin/sakit/alpha/hadir/terlambat** secara manual.
- Menggunakan `updateOrCreate` berdasarkan `(siswa_id, tanggal)` — kalau baris sudah ada (misal dari absen wajah), akan **ditimpa** oleh input manual (`metode = 'manual'`).

`DELETE absensi/{absensi}` → `AbsensiController@destroy`:
- Menghapus satu baris absensi (reset, biar siswa bisa absen ulang hari itu) — tercatat di `absensi_audit_log`.

`GET absensi/audit` (admin saja) → `AbsensiController@audit`:
- Daftar riwayat absensi yang pernah dihapus, murni untuk akuntabilitas/oversight.

## 10. Alur Laporan & Export

`GET laporan` → `LaporanController@index`:
- Filter berdasarkan rentang tanggal (`dari`–`sampai`, default: awal bulan s/d hari ini) dan kelas.

`GET laporan/excel` → `exportExcel()`:
- Query sama, ditulis langsung sebagai **stream XLSX** (`openspout`) tanpa menyimpan file di server.

`GET laporan/pdf` → `exportPdf()`:
- Merender view `laporan.pdf` melalui `barryvdh/laravel-dompdf`, kertas A4 landscape, lalu diunduh.

## 11. Pengaturan (Admin)

`GET/PUT pengaturan` → `PengaturanController`, terbagi beberapa sub-form:
- **Umum**: `nama_sekolah`, `jam_masuk`, `batas_terlambat`, `mulai_pulang`.
- **Lokasi** (`PUT pengaturan/lokasi`): titik koordinat sekolah (klik di peta) & radius meter — mengaktifkan verifikasi GPS di absen mandiri.
- **Hari libur mingguan** (`PUT pengaturan/libur-mingguan`): centang sekali untuk Sabtu/Minggu, tidak perlu diulang tiap minggu — beda dengan `hari_libur` (tanggal spesifik, dikelola terpisah di `GET hari-libur`).
- **Simulasi waktu**: field testing-only untuk menguji alur berbasis waktu tanpa menunggu jam sungguhan.

## 12. Ringkasan Alur End-to-End

```
Admin: tambah kelas → tambah siswa ──┐
                                       │
Siswa: register akun sendiri ─────────┤
                                       ▼
                         Enroll wajah (5 sampel, browser)
                                       │
                                       ▼
                          face_descriptors (128-d × N)
                                       │
        Siswa buka /portal/absen (browser muat descriptor)
                                       ▼
     Loop kamera: deteksi → cocokkan → dekat kamera? →
     lokasi OK? → kedip terverifikasi? → kirim siswa_id
                                       │
                                       ▼
     AbsensiRecorder: libur? sudah absen? jam vs batas
     telat/mulai pulang? lokasi radius? → simpan status
     hadir/terlambat/pulang → antre email ke orang tua
                                       │
                    ┌──────────────────┴───────────────────┐
                    ▼                                        ▼
         Rekap / Laporan (Excel/PDF)          Alpha Checker (jam pulang, siswa
                                                yang tidak absen → tandai alpha
                                                → antre email ke orang tua)

Siswa juga bisa: ajukan izin/sakit/pulang cepat → wali kelas
approve/reject → (kalau disetujui) tercatat otomatis di absensi
```

## 13. Catatan Keamanan & Keterbatasan

- **Anti-spoofing sebatas kedipan mata (liveness)**: `face-liveness.js` mendeteksi kedip lewat Eye Aspect Ratio (EAR) adaptif per sesi. Ini **best-effort**, bukan jaminan kuat — sensitivitas EAR terbukti bervariasi cukup jauh antar device/kamera dalam pengujian, sehingga threshold-nya sengaja dilonggarkan demi keandalan lintas-device. Cukup untuk mencegah spoofing paling sederhana (foto statis di layar/kertas), bukan untuk kasus keamanan tinggi.
- **Notifikasi WhatsApp belum aktif**: skema database sudah menyediakan `no_hp_orang_tua`, tapi kanal pengiriman WA belum diimplementasikan — semua notifikasi saat ini lewat email (`Mail::queue()`, butuh queue worker berjalan).
- **Data descriptor bukan gambar**: yang tersimpan di database adalah vektor matematis (128 float), bukan foto wajah — namun tetap data biometrik sensitif dan sebaiknya diperlakukan sebagai data pribadi.
- **Kamera butuh HTTPS** (kecuali di `localhost`), sesuai kebijakan browser modern untuk `getUserMedia`.
- **Ambang kecocokan wajah** (`MATCH_THRESHOLD = 0.5`, jarak Euclidean) adalah trade-off antara false-accept dan false-reject; bisa disetel di `resources/js/face-kiosk.js`.
- **Backend tfjs dipaksa `wasm`**: dipilih setelah ditemukan sebagian GPU Android punya driver WebGL yang sangat lambat untuk model ini (bisa 900ms–10 detik per deteksi). `wasm` konsisten cepat (~100–200ms) di semua device yang diuji, tapi kalau CDN wasm (`jsdelivr`) tidak terjangkau (jaringan sekolah ketat), sistem otomatis fallback diam-diam ke `webgl`.
