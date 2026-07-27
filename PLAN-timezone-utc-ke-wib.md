# Rencana: Perbaikan Timezone (UTC → Asia/Jakarta)

> Status: **sudah diimplementasi** (2026-07-27 malam). `config/app.php`
> sekarang baca `env('APP_TIMEZONE', 'UTC')`, `.env`/`.env.example` diisi
> `APP_TIMEZONE=Asia/Jakarta`. Data historis MySQL sudah dimigrasi +7 jam
> lewat `database/migrations/2026_07_27_150000_shift_data_historis_utc_ke_wib.php`
> (dengan backup penuh sebelumnya via `php artisan backup:run --only-db`).
> `AbsensiController::manual()` juga diperbaiki supaya pakai
> `Pengaturan::sekarang()`, bukan `Carbon::now()` langsung.
>
> Satu baris SENGAJA tidak dimigrasi: `absensi` id=170 (siswa_id 66,
> tanggal 2026-07-15) — jam_masuk=jam_pulang=17:20:00, pola data test lama;
> menggeser tanggal-nya akan tabrakan `unique(siswa_id, tanggal)` dengan
> baris lain siswa yang sama di 2026-07-16. Dibiarkan apa adanya.

## Latar Belakang / Akar Masalah

`config/app.php` masih pakai default bawaan Laravel: `'timezone' => 'UTC'`.
Sekolah ini di WIB (Asia/Jakarta, UTC+7). Server dev (Windows, Laragon) jam
aslinya sudah benar WIB, tapi `Carbon::now()` di dalam PHP mengembalikan jam
UTC — **selisih 7 jam di belakang** jam asli.

Ditemukan lewat kejadian nyata: jam asli 19:20 WIB, tapi `Pengaturan::
waktuSekarang()` (yang notabene sudah benar pakai `Carbon::now()` sesuai
desain) membaca `12:20`. Akibatnya `mulai_pulang` yang diisi admin sebagai
"15:00" (maksudnya jam 3 sore WIB) baru dianggap tercapai jam 22:00 WIB asli
— telat 7 jam dari maksud admin.

**Ini bukan bug baru** — `'timezone' => 'UTC'` adalah nilai default yang
tidak pernah diubah sejak commit pertama, jadi seluruh riwayat data absensi
sejak awal proyek konsisten memakai jam yang sama-sama mundur 7 jam. Tidak
ada data yang "acak" atau tidak konsisten satu sama lain — cuma label jam
dinding aslinya yang salah.

## Kolom & Tabel yang Terdampak

Semua kolom `time`/`timestamp`/`dateTime` yang diisi dari `Carbon::now()`
(langsung atau lewat `Pengaturan::waktuSekarang()`) ikut mundur 7 jam dari
jam dinding asli:

| Tabel | Kolom | Diisi dari |
|---|---|---|
| `absensi` | `jam_masuk`, `jam_pulang` | `AbsensiRecorder` (via `waktuSekarang()`), **dan** `AbsensiController::manual()` (langsung `Carbon::now()`, tidak lewat `Pengaturan` — inkonsistensi terpisah yang juga perlu diperiksa) |
| `absensi` | `tanggal` | Berpotensi salah **tanggal** (bukan cuma jam) buat siswa yang absen dini hari WIB (00:00-06:59), karena di UTC itu masih tanggal kemarin — lihat "Kasus Tepi" di bawah |
| `absensi`, `pengaturan`, `siswa`, `kelas`, `face_descriptors`, `hari_libur`, `notifikasi_absensi_log`, `pengajuan_izin`, `absensi_audit_log` | `created_at`/`updated_at` (Eloquent `timestamps()`) | Semua write lewat Eloquent |
| `absensi_audit_log` | `jam_masuk`, `jam_pulang` (snapshot) | Disalin dari `absensi` saat audit |
| `pengajuan_izin` | `reviewed_at` | Kapan admin approve/reject |
| `pengaturan` | `jam_masuk`, `batas_terlambat`, `mulai_pulang`, `jam_cek_belum_hadir`, `simulasi_waktu` | **Tidak terdampak isinya** (ini murni nilai yang diketik admin, bukan hasil `now()`) — tapi jadi rujukan pembanding yang salah dibandingkan `Carbon::now()` yang mundur 7 jam, itulah sumber bug perilaku (alpha/belum-hadir/lock kamera telat 7 jam) |

Jadwal cron di `routes/console.php` (`Schedule::command(...)->between('12:00','22:00')` dst.) juga dievaluasi pakai jam App (bukan jam OS Windows), jadi ikut mundur 7 jam dari maksud aslinya.

## Kasus Tepi: Tanggal Bisa Salah, Bukan Cuma Jam

Untuk siswa yang absen antara **00:00–06:59 WIB**, jam UTC-nya masih di
**17:00–23:59 tanggal SEBELUMNYA**. Kalau `Pengaturan::waktuSekarang()
->copy()->startOfDay()` dipakai buat menentukan "hari ini", siswa yang absen
jam 00:30 WIB bisa tercatat di kolom `tanggal` = kemarin, bukan hari ini —
constraint unique `(siswa_id, tanggal)` bisa bentrok kalau siswa itu memang
sudah absen "kemarin"-nya (menurut UTC) di jam normal. Sejauh ini kemungkinan
belum kejadian karena jam masuk sekolah wajar (07:00 ke atas WIB = 00:00 UTC
ke atas, masih tanggal yang sama), tapi perlu dicek eksplisit kalau ada
fitur baru yang menyentuh jam dini hari (mis. shift malam, kegiatan sekolah
larut).

## Rencana Perbaikan (Dua Bagian, Harus Bareng)

### Bagian 1 — Kode/Config (aman, tidak mengubah data)

1. Set `APP_TIMEZONE=Asia/Jakarta` di `.env` (dan `.env.example`), biarkan
   `config/app.php` tetap baca dari `env('APP_TIMEZONE', 'UTC')` (default
   Laravel sudah begini, cuma env-nya yang belum diisi).
2. Perbaiki `AbsensiController::manual()` (baris ~121) supaya pakai
   `Pengaturan::sekarang()` / lewat `waktuSekarang()`, bukan `Carbon::now()`
   langsung — sekalian menghormati `simulasi_waktu` seperti alur lain,
   konsisten dengan catatan di `Pengaturan::waktuSekarang()` yang sudah bilang
   "semua pengecekan hari ini HARUS lewat sini".
3. Cek ulang semua `Schedule::command(...)->between(...)` di
   `routes/console.php` — setelah timezone app benar, jam-jam di situ
   (`12:00-22:00`, `07:00-11:00`) otomatis jadi jam WIB asli, tidak perlu
   diubah nilainya, cuma perlu diverifikasi ulang lewat testing manual/cron
   nyata.

### Bagian 2 — Migrasi Data Historis (butuh hati-hati + backup)

Begitu Bagian 1 jalan, catatan BARU otomatis benar (WIB asli), tapi catatan
LAMA tetap berisi jam yang mundur 7 jam — dataset jadi "terbelah" tanpa
penanda mana yang lama/baru kecuali dari `created_at` sebelum vs sesudah
tanggal deploy fix ini.

Opsi (perlu didiskusikan, belum diputuskan):

- **(a) Migrasi sekali jalan**: tambah +7 jam ke semua kolom `time`/
  `timestamp`/`dateTime` yang kena (tabel & kolom di atas) untuk baris yang
  `created_at < tanggal_deploy_fix`, termasuk menangani kasus tepi tanggal
  (kalau jam hasil +7 lewat tengah malam, `tanggal`/tanggal kalender ikut
  +1 hari). Paling "benar" tapi paling berisiko — butuh backup penuh +
  dry-run di salinan database dulu, dan idealnya dikerjakan di luar jam
  aktif sekolah.
- **(b) Biarkan data lama apa adanya**, cuma catat di README/memory bahwa
  absensi sebelum tanggal fix ini punya offset -7 jam di kolom jam (tapi
  tanggalnya kemungkinan besar tetap benar kecuali kasus tepi dini hari di
  atas). Lebih aman, tapi laporan/riwayat lama tetap salah kalau ada yang
  butuh jam presisi dari sebelum tanggal fix.

## Pertanyaan Terbuka (Perlu Dijawab Sebelum Implementasi)

- [ ] Opsi migrasi data historis: (a) migrasi penuh dengan downtime terjadwal, atau (b) biarkan data lama apa adanya dan hanya perbaiki ke depan?
- [ ] Kalau pilih (a): kapan jendela downtime yang aman (di luar jam sekolah aktif)? Perlu backup `mysqldump` penuh sebelum eksekusi — lihat [[php85-package-constraints]] soal binary MySQL yang dipakai.
- [ ] Apakah ada laporan/export (Laporan Excel/PDF) yang sudah dikirim ke pihak lain (kepala sekolah, dinas, dst.) berdasarkan jam yang salah ini, yang perlu diklarifikasi terpisah dari perbaikan sistemnya?
- [ ] `AbsensiController::manual()` yang pakai `Carbon::now()` langsung — apakah ini disengaja (misalnya supaya *tidak* ikut `simulasi_waktu` saat admin input manual) atau memang inkonsistensi yang harus diseragamkan sekalian?
