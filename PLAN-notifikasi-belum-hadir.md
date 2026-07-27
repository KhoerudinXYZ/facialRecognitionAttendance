# Rencana: Notifikasi "Belum Hadir" (Peringatan Pagi)

> Status: **sudah diimplementasi** (2026-07-27) — `AbsensiBelumHadirChecker`,
> command `absensi:cek-belum-hadir` (cron tiap 10 menit, 07:00-11:00),
> field `Pengaturan::jam_cek_belum_hadir` di halaman Pengaturan, jenis
> `belum_hadir` di log & halaman Notifikasi. Lihat jawaban pertanyaan
> terbuka di bagian bawah untuk keputusan yang diambil.

## Latar Belakang

Saat ini ada 2 dari 3 notifikasi kehadiran yang diminta:

| Trigger | Status | Lokasi kode |
|---|---|---|
| Siswa hadir (absen masuk) | ✅ Sudah ada | `AbsensiRecorder::notifikasiKehadiran()` |
| Waktu pulang & siswa alpha | ✅ Sudah ada (cron `hourly`, cek `mulai_pulang`) | `AbsensiAlphaChecker` |
| **Belum hadir di jam tertentu (mis. 09:30)** | ❌ **Belum ada — ini yang perlu dibangun** | — |

## Requirement yang Sudah Disepakati

1. **Jam cek bisa diatur admin**, konsisten dengan `jam_masuk` / `batas_terlambat` / `mulai_pulang` yang sudah ada di halaman Pengaturan. Bukan angka tetap di kode.
2. **Siswa dengan pengajuan izin/sakit berstatus "menunggu" hari itu dikecualikan** dari cek ini — sama seperti pola yang sudah dipakai `AbsensiAlphaChecker` (`whereDoesntHave('pengajuanIzin', ... where status menunggu)`), supaya orang tua yang anaknya sudah mengajukan izin tidak dapat notifikasi membingungkan.
3. **Kirim maksimal sekali per hari per siswa** — begitu terkirim, tidak diulang lagi hari itu meski cron jalan berkali-kali dan siswa masih belum hadir.

## Desain Teknis (Usulan, Untuk Didiskusikan Lagi Saat Implementasi)

### 1. Field pengaturan baru

Tambah kolom baru di tabel `pengaturan`, mis. `jam_cek_belum_hadir` (format `H:i`, sama seperti `batas_terlambat`), plus toggle aktif/nonaktif kalau sekolah tidak mau pakai fitur ini (opsional — perlu dikonfirmasi apakah perlu on/off terpisah atau selalu aktif begitu ada nilainya).

### 2. Command & jadwal terpisah dari Alpha Checker

Bikin service baru (mis. `AbsensiBelumHadirChecker`), **bukan digabung ke `AbsensiAlphaChecker`** — beda tujuan: yang satu peringatan dini (siswa mungkin masih dalam perjalanan), yang satu status resmi akhir hari.

Query kandidat: siswa aktif, belum punya baris absensi hari ini, tidak sedang punya pengajuan izin "menunggu" hari ini, dan **belum pernah dikirimi notifikasi jenis `belum_hadir` untuk tanggal ini** (dedup, lihat poin berikutnya).

### 3. Dedup "sekali per hari per siswa"

Cara paling konsisten dengan pola yang sudah ada: sebelum kirim, cek `NotifikasiAbsensiLog::where('siswa_id', ...)->whereDate('tanggal', $today)->where('jenis', 'belum_hadir')->exists()` — kalau sudah ada baris, skip. Sama seperti bagaimana `notifikasi_absensi_log` sudah dipakai buat audit/histori jenis `kehadiran` dan `alpha`.

### 4. Presisi jadwal cron

`AbsensiAlphaChecker` saat ini di-cron `hourly` (`routes/console.php`) — cukup untuk alpha karena "telat sampai 1 jam" tidak terlalu masalah untuk status akhir hari. Tapi untuk **peringatan dini**, keterlambatan sampai 1 jam mengurangi gunanya (orang tua baru tahu jam 10:30 kalau baru dicek jam segitu, padahal maksudnya dikasih tahu dari jam 09:30). Perlu cron lebih rapat di jam-jam kritis, misal tiap 5-10 menit di rentang pagi (`between('07:00','11:00')` atau sesuai kebutuhan), bukan `hourly` sepanjang hari seperti alpha checker.

### 5. Isi pesan (draf, belum final)

> "Yth. Orang Tua/Wali dari {nama}, kami informasikan ananda belum hadir di
> sekolah hingga pukul {jam_cek}. Mohon konfirmasi ke pihak sekolah. Terima kasih."

### 6. Interaksi dengan notifikasi lain

- Kalau siswa akhirnya datang **setelah** notifikasi "belum hadir" terkirim → notifikasi "hadir" (yang sudah ada) tetap terkirim seperti biasa, tidak disupres. Orang tua akan terima 2 email di hari itu (belum hadir jam 09:30, lalu hadir/terlambat begitu benar-benar datang) — dianggap wajar, bukan duplikat, karena beda informasi.
- Kalau siswa tetap tidak datang sampai akhir hari → notifikasi alpha (yang sudah ada) tetap terkirim terpisah di jam pulang, sesuai jadwal yang sudah berjalan.

## Pertanyaan Terbuka — Jawaban yang Diambil Saat Implementasi (2026-07-27)

- [x] Tidak ada toggle terpisah — cukup dianggap aktif begitu `jam_cek_belum_hadir` diisi, persis pola `lokasiAktif()` (`Pengaturan::cekBelumHadirAktif()`).
- [x] Email + WhatsApp langsung dari awal, bukan email dulu — volume notifikasi ini kecil (cuma siswa yang belum hadir), jadi mengikuti pola `alpha` (WA aktif begitu `FONNTE_TOKEN` terisi), BUKAN dikunci di belakang `FONNTE_KEHADIRAN_AKTIF` seperti `kehadiran` yang volumenya tinggi/burst.
- [x] Otomatis muncul di `notifikasi/index.blade.php` lewat tabel `notifikasi_absensi_log` yang sama; ditambahkan badge amber terpisah untuk jenis `belum_hadir` (sebelumnya cuma ada kehadiran/alpha).

Detail implementasi: `AbsensiBelumHadirChecker` (tidak menulis baris `absensi`, beda dari `AbsensiAlphaChecker`), command `absensi:cek-belum-hadir` dijadwalkan `everyTenMinutes()->between('07:00','11:00')` di `routes/console.php`. Sekalian ditemukan & diperbaiki celah test isolation: `phpunit.xml` sebelumnya tidak meng-override `FONNTE_TOKEN`/`FONNTE_KEHADIRAN_AKTIF`, jadi test yang tidak eksplisit `config()` override mewarisi kredensial asli dari `.env` dan bisa memicu HTTP request sungguhan ke Fonnte.
