# Rencana Redesign UI — Dashboard Absensi Siswa (Mobile)

## Konteks

Pihak sekolah menilai tampilan dashboard siswa (halaman beranda absensi wajah) masih terlihat "basic". Setelah dianalisis, akar masalahnya bukan dominasi warna putih semata, melainkan:

1. Styling mengikuti default template UI library tanpa identitas khas (card putih + shadow tipis + satu warna aksen indigo).
2. Warna status (badge "Wajah Terdaftar" / "Belum Absen Hari Ini") memakai pastel bersaturasi rendah — informasi paling penting di layar malah paling lemah secara visual.
3. Timeline kehadiran mingguan (Sen–Min) sama sekali tidak mengkodekan data — semua kotak abu-abu polos, padahal seharusnya ini elemen paling informatif.
4. Avatar memakai ilustrasi kartun generik, padahal sistem sudah punya foto wajah asli siswa dari proses enrollment.
5. Tipografi hanya satu suara — semua teks memakai bobot dan famili yang sama, tidak ada hierarki yang jelas antara informasi penting (nama, status) dan label pendukung.
6. Tombol kamera absen (elemen paling unik dari aplikasi ini) diberi treatment tombol FAB generik, padahal ini seharusnya jadi titik fokus/signature dari seluruh halaman.

Tujuan redesign: bukan menambah dekorasi, tapi membuat warna dan tipografi benar-benar **mengkodekan informasi**, serta memberi halaman satu elemen ciri khas yang mengakar pada konteks absensi wajah sekolah.

---

## 1. Palet Warna

Ganti dari "satu aksen indigo + pastel lemah" menjadi palet dengan peran yang jelas per warna:

| Nama | Hex (indikatif) | Peran |
|---|---|---|
| `--bg-base` | `#F4F6FB` (bukan putih polos) | Background halaman, beri kedalaman lewat tint lembut |
| `--surface` | `#FFFFFF` | Warna card, kontras dengan background bertint |
| `--ink` | `#1C2434` | Teks utama (nama, angka penting) |
| `--ink-muted` | `#6B7385` | Label, keterangan sekunder |
| `--accent` | `#4F46E5` (indigo, dipertahankan sebagai identitas brand) | Elemen interaktif utama (tombol absen, highlight aktif) |
| `--status-hadir` | `#16A34A` (hijau solid, bukan pastel) | Status hadir/tepat waktu |
| `--status-telat` | `#D97706` (oranye solid) | Status telat |
| `--status-alpha` | `#DC2626` (merah solid) | Status alpha/tidak hadir |
| `--status-izin` | `#8B5CF6` (ungu berbeda dari accent) | Status izin/sakit |

**Prinsip:** warna status harus solid dan cukup gelap untuk kontras teks putih di atasnya — bukan pastel yang nyaris menyatu dengan background.

## 2. Tipografi

- **Display/Judul (nama siswa, angka statistik besar):** font dengan bobot lebih tegas dan sedikit karakter — misalnya **Lexend** atau **Sora** (geometris, ramah dibaca di layar kecil, tidak generik seperti Inter default).
- **Body/Label:** **Inter** atau **Plus Jakarta Sans** untuk teks pendukung, ukuran kecil (12–13px), warna `--ink-muted`.
- **Angka statistik (jam masuk/pulang, jumlah hadir):** gunakan varian tabular-nums, bobot semi-bold, ukuran diperbesar dari desain saat ini — ini data yang paling ingin dilihat siswa dalam sekejap.
- Hierarki: Nama siswa > Status badge > Angka statistik > Label kecil. Saat ini semua terasa setara — perlu dibedakan jelas lewat ukuran + bobot, bukan cuma ukuran.

## 3. Elemen per Komponen

### Header profil
- Ganti avatar ilustrasi kartun generik → **foto wajah asli siswa** (sudah tersedia dari data enrollment) dibingkai bulat dengan cincin warna sesuai kelas/jurusan. Jika foto tidak tersedia, fallback ke **inisial nama dengan warna solid per kelas** (bukan ilustrasi generik).

### Badge status
- "Wajah Terdaftar" dan "Belum Absen Hari Ini" → gunakan `--status-hadir` / `--status-alpha` solid dengan teks putih, bukan pastel dengan teks berwarna. Kontras naik drastis, terbaca sekilas.

### Stepper Masuk → Absen → Pulang
- Ini bagus secara konsep (proses nyata, bukan angka dekoratif), pertahankan.
- Tombol kamera di tengah: jadikan **signature element** halaman ini — sedikit lebih besar dari elemen lain, beri micro-animation pulsing/scanning saat menunggu wajah terdeteksi (bukan lingkaran statis). Ini elemen yang paling mencerminkan "absensi berbasis wajah", sayang jika ditreatment sama seperti FAB generik.

### Timeline kehadiran mingguan (Sen–Min)
- **Prioritas tertinggi untuk diperbaiki** — saat ini paling banyak membuang informasi.
- Tiap kotak hari diberi warna solid sesuai status hari itu: hijau (hadir), oranye (telat), merah (alpha), ungu (izin), abu-abu muda (belum terjadi/hari mendatang).
- Hari yang sedang dipilih (highlight biru saat ini) tetap dipertahankan sebagai indikator "sedang dilihat", ditambahkan sebagai ring/border di atas warna status, bukan menggantikannya.

### Statistik Kehadiran Bulan Ini
- Tiga kartu (hadir/telat/alpha) sudah menggunakan tint warna — pertahankan arah ini, tapi naikkan saturasi agar konsisten dengan warna status di timeline mingguan (saat ini terlihat lebih pudar dari yang dibutuhkan untuk kontras baik).

### Background halaman
- Ganti dari putih polos → `--bg-base` bertint lembut. Card tetap putih (`--surface`) sehingga ada kontras/kedalaman antara background dan card, alih-alih semuanya menyatu putih-di-atas-putih.

### Bottom navigation
- Pertahankan struktur (Beranda/Absen/Riwayat/Izin/Wajah), cukup terapkan warna aksen baru pada state aktif dan pastikan ikon konsisten (semua outline atau semua filled — saat ini campur gaya).

## 4. Yang Sengaja Tidak Diubah

- Struktur informasi/layout keseluruhan (header → status → stepper → timeline → statistik → nav) sudah logis dan sesuai kebutuhan pengguna — tidak perlu dirombak, cukup di-treatment ulang secara visual.
- Bottom navigation 5 menu dipertahankan apa adanya.

## 5. Urutan Pengerjaan yang Disarankan

1. Definisikan token warna & tipografi di satu tempat (CSS variables/Tailwind config) — semua komponen menarik dari sini, bukan hardcode warna per file.
2. Perbaiki badge status & timeline mingguan dulu (dampak visual & informasi paling besar, effort paling kecil).
3. Ganti avatar generik → foto asli/inisial berwarna.
4. Sesuaikan tipografi (import font, terapkan hierarki baru pada nama, angka, label).
5. Tambahkan micro-animation pada tombol absen (elemen signature).
6. Review akhir: screenshot mode terang & gelap, pastikan kontras warna status tetap terbaca di keduanya.

## 6. Catatan

Perubahan ini murni di lapisan visual/CSS dan komponen tampilan — tidak menyentuh struktur data, endpoint, atau logika absensi yang sudah berjalan.
