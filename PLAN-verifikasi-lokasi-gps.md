# Kenapa Website Absensi Tidak Bisa Mendeteksi "Fake GPS" Secara Pasti

Setiap HP (Android/iPhone) punya sistem keamanan bawaan: **website di browser (Chrome, dll) tidak pernah diberi izin untuk tahu dari mana angka lokasi itu berasal** — apakah dari chip GPS asli, atau disuntik oleh aplikasi lain. Website cuma dikasih "hasil akhirnya" saja (angka lat/lng), tanpa keterangan asal-usulnya.

Ini bukan kelemahan sistem absensi kita — ini pagar keamanan yang sama berlaku untuk **semua** website di dunia, termasuk Google Maps, Gojek, aplikasi bank, dll kalau dibuka lewat browser. HP sengaja menutup akses itu supaya website sembarangan tidak bisa mengintai data sensitif pengguna.

Info "dari mana lokasi ini berasal" (asli atau palsu) **hanya** dibuka untuk **aplikasi native** yang di-install langsung dari Play Store (bukan website) dan diberi izin khusus. Kalau sekolah ingin label "FAKE GPS" yang pasti dan otomatis, satu-satunya jalan adalah membangun **aplikasi Android tersendiri** — itu proyek baru, bukan sekadar penyesuaian di website yang sudah ada.

## Yang sudah tersedia sekarang sebagai penggantinya

- **Verifikasi wajah + liveness (deteksi kedip mata) real-time** — ini tetap mengharuskan siswa yang bersangkutan hadir langsung di depan kamera saat absen. Titip absen tetap tidak bisa, terlepas dari GPS-nya asli atau palsu.
- **Radius GPS** — absen ditolak kalau lokasi yang dikirim di luar radius sekolah.
- **2 sinyal audit tambahan** (menu Administrasi → Audit Lokasi) untuk direview manual wali kelas/admin: riwayat percobaan absen yang ditolak lokasi, dan siswa berbeda yang kebetulan absen dengan koordinat GPS yang nyaris identik di hari yang sama.

Kombinasi ini menutup celah yang paling mungkin disalahgunakan (titip absen ke teman), meski tidak bisa memberi label pasti "ini fake GPS" seperti yang diminta.

## Kalau sekolah tetap ngotot minta label pasti

Satu-satunya jalan teknis adalah membangun aplikasi Android native terpisah yang memakai `Location.isFromMockProvider()` — API yang cuma dibuka OS untuk aplikasi native berizin khusus, bukan website. Ini scope pengembangan baru (bukan penyesuaian sistem yang sudah ada), dan bahkan itu pun masih ada celah (root/Xposed/modifikasi tingkat lanjut kadang bisa menyembunyikan flag itu juga) — hanya jauh lebih sulit dibanding sekadar install app fake-GPS gratisan.
