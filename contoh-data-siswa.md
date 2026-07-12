# Contoh Data Import 40 Siswa

Data contoh untuk diuji-coba pada fitur **Import Excel** (`/siswa/import`). Kolom mengikuti persis format template yang diunduh dari tombol **"Unduh template Excel"**: `NIS, NISN, Nama, Jenis Kelamin (L/P), Kelas, Aktif (Y/N)`.

Kelas **`X RPL 1`** dipakai karena sudah tersedia otomatis dari `DatabaseSeeder` (lihat `database/seeders/DatabaseSeeder.php`) — jadi data ini bisa langsung diimport tanpa perlu membuat kelas baru dulu.

> **Catatan**: file `.md` ini hanya untuk referensi/isi ulang manual. Fitur import butuh file **`.xlsx`** — salin tabel di bawah ke Excel/Google Sheets (atau isi ulang ke `template-import-siswa.xlsx` yang diunduh dari aplikasi), lalu simpan sebagai `.xlsx` sebelum diupload.

| NIS | NISN | Nama | Jenis Kelamin (L/P) | Kelas | Aktif (Y/N) |
|---|---|---|---|---|---|
| 2026001 | 0081234501 | Ahmad Fauzan | L | X RPL 1 | Y |
| 2026002 | 0081234502 | Siti Nurhaliza | P | X RPL 1 | Y |
| 2026003 | 0081234503 | Bagus Prasetyo | L | X RPL 1 | Y |
| 2026004 | 0081234504 | Dewi Lestari | P | X RPL 1 | Y |
| 2026005 | 0081234505 | Muhammad Rizki | L | X RPL 1 | Y |
| 2026006 | 0081234506 | Ayu Wulandari | P | X RPL 1 | Y |
| 2026007 | 0081234507 | Dimas Saputra | L | X RPL 1 | Y |
| 2026008 | 0081234508 | Putri Ramadhani | P | X RPL 1 | Y |
| 2026009 | 0081234509 | Fajar Nugroho | L | X RPL 1 | Y |
| 2026010 | 0081234510 | Indah Permata | P | X RPL 1 | Y |
| 2026011 | 0081234511 | Yusuf Maulana | L | X RPL 1 | Y |
| 2026012 | 0081234512 | Rina Marlina | P | X RPL 1 | Y |
| 2026013 | 0081234513 | Rangga Pratama | L | X RPL 1 | Y |
| 2026014 | 0081234514 | Nabila Zahra | P | X RPL 1 | Y |
| 2026015 | 0081234515 | Eko Santoso | L | X RPL 1 | Y |
| 2026016 | 0081234516 | Winda Sari | P | X RPL 1 | Y |
| 2026017 | 0081234517 | Andika Putra | L | X RPL 1 | Y |
| 2026018 | 0081234518 | Sari Wahyuni | P | X RPL 1 | Y |
| 2026019 | 0081234519 | Bayu Firmansyah | L | X RPL 1 | Y |
| 2026020 | 0081234520 | Citra Kirana | P | X RPL 1 | Y |
| 2026021 | 0081234521 | Doni Kurniawan | L | X RPL 1 | Y |
| 2026022 | 0081234522 | Eka Putri | P | X RPL 1 | Y |
| 2026023 | 0081234523 | Faisal Rahman | L | X RPL 1 | Y |
| 2026024 | 0081234524 | Gita Anggraini | P | X RPL 1 | Y |
| 2026025 | 0081234525 | Hendra Gunawan | L | X RPL 1 | Y |
| 2026026 | 0081234526 | Intan Permatasari | P | X RPL 1 | Y |
| 2026027 | 0081234527 | Joko Susilo | L | X RPL 1 | Y |
| 2026028 | 0081234528 | Kartika Sari | P | X RPL 1 | Y |
| 2026029 | 0081234529 | Lukman Hakim | L | X RPL 1 | Y |
| 2026030 | 0081234530 | Mega Wati | P | X RPL 1 | Y |
| 2026031 | 0081234531 | Nur Hidayat | L | X RPL 1 | Y |
| 2026032 | 0081234532 | Oktavia Rahmawati | P | X RPL 1 | Y |
| 2026033 | 0081234533 | Panji Setiawan | L | X RPL 1 | Y |
| 2026034 | 0081234534 | Qonita Salsabila | P | X RPL 1 | Y |
| 2026035 | 0081234535 | Rizal Effendi | L | X RPL 1 | Y |
| 2026036 | 0081234536 | Sinta Bella | P | X RPL 1 | Y |
| 2026037 | 0081234537 | Taufik Hidayat | L | X RPL 1 | Y |
| 2026038 | 0081234538 | Umi Kalsum | P | X RPL 1 | Y |
| 2026039 | 0081234539 | Wahyu Ramadhan | L | X RPL 1 | Y |
| 2026040 | 0081234540 | Yulia Safitri | P | X RPL 1 | Y |

## Cara pakai cepat

1. Pastikan sudah `php artisan migrate:fresh --seed` (agar kelas `X RPL 1` tersedia).
2. Buka `/siswa/import`, klik **"Unduh template Excel"**.
3. Isi 40 baris di atas ke file template tersebut (mulai baris ke-2, baris pertama tetap header).
4. Simpan, lalu upload kembali lewat form import.
5. Hasil yang diharapkan: **40 siswa berhasil diimport**, 0 baris error (karena semua NIS unik dan kelas `X RPL 1` sudah ada).

### Skenario uji baris bermasalah (opsional)

Untuk menguji penanganan error, tambahkan baris seperti ini ke file sebelum upload:

| NIS | NISN | Nama | Jenis Kelamin (L/P) | Kelas | Aktif (Y/N) |
|---|---|---|---|---|---|
| 2026001 | 0081234599 | Duplikat NIS | L | X RPL 1 | Y |
| 2026099 | 0081234598 | Kelas Salah | P | XI TIDAK ADA | Y |
|  | 0081234597 | Tanpa NIS | L | X RPL 1 | Y |

Ketiga baris ini akan **dilewati** (bukan menggagalkan import) dan muncul di daftar error setelah upload: NIS duplikat, kelas tidak ditemukan, dan NIS kosong.
