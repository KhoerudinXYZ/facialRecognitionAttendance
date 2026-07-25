# Rencana: Notifikasi "Belum Hadir" (Peringatan Pagi)

> Status: **requirement/spec dulu, belum diimplementasi.**
> Ditulis untuk dikerjakan setelah demo, bukan malam sebelum demo.

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

## Pertanyaan Terbuka (Perlu Dijawab Sebelum/Saat Implementasi)

- [ ] Apakah field `jam_cek_belum_hadir` perlu toggle aktif/nonaktif terpisah, atau cukup dianggap "aktif" begitu ada nilai jamnya di Pengaturan?
- [ ] Kanal pengiriman: email dulu (mengikuti pola yang sudah ada), lalu WhatsApp menyusul begitu kanal WA sudah siap (sesuai diskusi kerangka notifikasi WA sebelumnya)?
- [ ] Perlu view/menampilkan histori notifikasi jenis `belum_hadir` ini juga di halaman Log Notifikasi admin (`notifikasi/index.blade.php`) — kemungkinan otomatis muncul kalau memakai tabel `notifikasi_absensi_log` yang sama, tinggal pastikan filter/label jenisnya ditangani di view.
