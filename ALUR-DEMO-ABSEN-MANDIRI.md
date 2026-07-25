# Alur Absen Mandiri (Bahan Demo & Presentasi)

> Diagram alur absen mandiri siswa lewat HP — dari kamera menyala sampai
> tercatat di database, termasuk seluruh pengecekan yang berjalan di
> belakang layar (lokasi, waktu, dan verifikasi kedipan).
>
> **Besok:** demo ke pengajar. **Minggu depan:** presentasi ke guru.

## Diagram Alur Lengkap (Client → Server)

```mermaid
flowchart TD
    start(["Siswa buka halaman<br/>Absen Mandiri"]) --> loadmodel["Muat model AI di HP +<br/>minta izin kamera & lokasi"]
    loadmodel --> camon["Kamera aktif,<br/>arahkan wajah"]
    camon --> detect{"Wajah terdeteksi<br/>& dikenali sistem?"}
    detect -- "Tidak dikenal" --> camon
    detect -- "Ya" --> geocheck{"Lokasi aktif?<br/>Dalam radius sekolah?"}
    geocheck -- "Belum diizinkan /<br/>di luar radius" --> geomsg["Tampilkan status<br/>izin lokasi"]
    geomsg --> camon
    geocheck -- "OK / tidak diwajibkan" --> distcheck{"Wajah cukup dekat<br/>ke kamera?"}
    distcheck -- "Terlalu jauh" --> distmsg["Minta siswa mendekat"]
    distmsg --> camon
    distcheck -- "Cukup dekat" --> blink{"Kedipan mata<br/>terdeteksi?"}
    blink -- "Belum" --> blinkmsg["Tampilkan pesan<br/>'kedipkan mata'"]
    blinkmsg --> camon
    blink -- "Ya, liveness OK" --> send["Kirim ke server:<br/>identitas + waktu + lokasi"]

    send --> libur{"Hari ini terdaftar<br/>sebagai libur?"}
    libur -- "Ya" --> tolaklibur(["Tolak:<br/>Hari libur"])
    libur -- "Tidak" --> sudah{"Sudah ada catatan<br/>absen hari ini?"}
    sudah -- "Izin / Sakit<br/>disetujui" --> tolaksudah(["Tolak:<br/>sudah izin/sakit"])
    sudah -- "Belum / alpha" --> waktu{"Cek jam sekarang vs<br/>jam masuk & batas telat"}
    waktu -- "Sebelum<br/>batas telat" --> hadir["Catat status:<br/>HADIR"]
    waktu -- "Lewat batas telat,<br/>sebelum jam pulang" --> terlambat["Catat status:<br/>TERLAMBAT"]
    waktu -- "Sudah lewat jam pulang,<br/>belum absen masuk" --> tolakwaktu(["Tolak:<br/>jam absen ditutup"])
    sudah -- "Sudah absen masuk,<br/>belum pulang" --> pulang["Catat:<br/>ABSEN PULANG"]

    hadir --> simpan[("Simpan ke<br/>database")]
    terlambat --> simpan
    pulang --> simpan
    simpan --> notif["Antre notifikasi email<br/>ke orang tua (background,<br/>tidak menunda respons)"]
    notif --> sukses(["Tampilkan sukses ke siswa<br/>+ redirect ke dashboard"])

    classDef success fill:#dcfce7,stroke:#16a34a,color:#14532d,stroke-width:2px;
    classDef danger fill:#fee2e2,stroke:#dc2626,color:#7f1d1d,stroke-width:2px;
    classDef warn fill:#fef3c7,stroke:#d97706,color:#78350f,stroke-width:2px;
    class sukses success
    class tolaklibur,tolaksudah,tolakwaktu danger
    class terlambat warn
```

**Legenda:** kotak putih = proses/pengecekan, hijau = berhasil tercatat,
kuning = tercatat dengan catatan (terlambat), merah = ditolak/tidak tercatat.

Baris atas (sampai "Kirim ke server") berjalan di HP siswa secara real-time,
100% di browser — tidak butuh koneksi ke server sampai satu langkah terakhir.
Baris bawah berjalan di server, dieksekusi dalam satu request yang sama
(lihat `app/Services/AbsensiRecorder.php`).

## Poin Kunci untuk Disampaikan

1. Pengenalan wajah & hitung jarak dilakukan **langsung di HP** (on-device), bukan dikirim ke server — lebih cepat & lebih hemat data.
2. Kedipan mata dipakai sebagai **verifikasi hidup** (liveness) — mencegah absen pakai foto statis di layar/kertas.
3. Lokasi GPS opsional bisa diaktifkan admin untuk membatasi absen hanya dari radius sekolah.
4. Status hadir/terlambat/pulang dihitung otomatis dari jam pengaturan sekolah, tidak perlu input manual.
5. Notifikasi email ke orang tua dikirim di belakang layar (queue) — tidak membuat siswa menunggu.

## Checklist Sebelum Demo

- [ ] Izin kamera & lokasi browser sudah diberikan di HP yang dipakai demo
- [ ] GPS / Location Service HP menyala (kalau fitur lokasi aktif)
- [ ] Wajah demo sudah terdaftar di sistem (sampel wajah tidak kosong)
- [ ] Pencahayaan cukup terang, wajah menghadap lurus ke kamera
- [ ] Server & queue worker (email) berjalan sebelum sesi dimulai — `php artisan queue:work` atau `composer run dev`

## Catatan Teknis

Seluruh tahap deteksi & verifikasi di HP berjalan dalam hitungan ratusan
milidetik setelah dioptimasi khusus untuk perangkat kelas bawah (backend
tfjs dipaksa ke `wasm` alih-alih `webgl` yang terbukti sangat lambat di
sebagian GPU Android) — proses yang sebelumnya sempat memakan waktu
beberapa detik di beberapa HP kini berjalan mendekati real-time.

Liveness (kedip mata) bersifat *best-effort*: sinyal EAR (Eye Aspect
Ratio) yang dipakai untuk mendeteksi kedipan terbukti sensitivitasnya
bervariasi antar perangkat/kamera. Untuk deployment yang butuh anti-spoof
lebih kuat, lihat catatan di `resources/js/face-liveness.js`.
