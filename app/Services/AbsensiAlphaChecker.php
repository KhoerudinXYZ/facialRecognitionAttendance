<?php

namespace App\Services;

use App\Mail\SiswaAlphaMail;
use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\NotifikasiAbsensiLog;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Dijadwalkan berkala (lihat perintah absensi:cek-alpha & jadwalnya di
 * routes/console.php), tapi tidak benar-benar memproses apa pun sampai
 * Pengaturan::mulai_pulang hari itu — sama persis dengan batas yang dipakai
 * AbsensiRecorder buat menutup absen masuk (lihat komentar di
 * AbsensiRecorder::record()), supaya begitu absen masuk ditutup, siswa yang
 * memang tidak hadir langsung resmi alpha di jam yang sama, bukan nge-gantung
 * beberapa jam dulu. Begitu lewat mulai_pulang, siswa aktif yang sampai saat
 * ini belum punya baris absensi hari ini ditandai alpha, lalu dikirimi
 * notifikasi WhatsApp/Fonnte (kalau FONNTE_TOKEN diisi & no_hp_orang_tua
 * terdaftar) sebagai kanal prioritas, dan email cuma jadi cadangan kalau
 * WA tidak bisa dipakai buat siswa itu — lihat WhatsAppNotifier.
 */
class AbsensiAlphaChecker
{
    /**
     * Return jumlah siswa yang baru ditandai alpha (0 kalau hari libur atau
     * belum lewat mulai_pulang).
     */
    public function jalankan(): int
    {
        $pengaturan = Pengaturan::get();
        $now = $pengaturan->waktuSekarang();
        $today = $now->copy()->startOfDay();

        if (HariLibur::isLibur($today)) {
            return 0;
        }

        $bolehJalan = Carbon::parse($today->toDateString() . ' ' . $pengaturan->mulai_pulang);

        if ($now->lessThan($bolehJalan)) {
            return 0;
        }

        // Siswa dengan pengajuan izin/sakit yang masih menunggu persetujuan
        // hari ini tidak ditandai alpha — kalau sampai disetujui, baris
        // absensi-nya ditulis oleh PengajuanIzinController::approve() sendiri
        // (lihat whereDoesntHave 'absensi' di atas), dan kalau ditolak,
        // siswa memang seharusnya kena alpha seperti biasa.
        $siswaBelumAbsen = Siswa::where('is_active', true)
            ->whereDoesntHave('absensi', fn ($q) => $q->whereDate('tanggal', $today))
            ->whereDoesntHave('pengajuanIzin', fn ($q) => $q->whereDate('tanggal', $today)->where('status', 'menunggu'))
            ->get();

        foreach ($siswaBelumAbsen as $siswa) {
            Absensi::create([
                'siswa_id' => $siswa->id,
                'kelas_id' => $siswa->kelas_id,
                'tanggal' => $today,
                'status' => 'alpha',
                'metode' => 'manual',
            ]);

            $this->notifikasi($siswa, $today);
            $this->jedaAntarKirim();
        }

        // Beda dari $siswaBelumAbsen di atas: siswa di sini SUDAH resmi
        // alpha (baris absensi-nya sudah ada dari run sebelumnya -- bukan
        // yang baru saja ditandai barusan di loop atas, makanya dikecualikan
        // lewat $siswaBelumAbsen->pluck('id'), supaya siswa yang baru gagal
        // di loop atas tidak langsung dicoba ulang lagi di run yang sama),
        // jadi tidak lolos whereDoesntHave('absensi') lagi -- perlu query
        // terpisah supaya notifikasi yang gagal (mis. Fonnte disconnect)
        // dicoba ulang di run berikutnya begitu channel-nya pulih, alih-alih
        // cuma satu kali kesempatan lalu diam selamanya. Mencakup SEMUA
        // tanggal yang belum pernah berhasil (bukan cuma hari ini) --
        // kalau Fonnte mati sampai lewat tengah malam, kegagalan kemarin
        // tidak boleh ditinggalkan selamanya cuma karena hari sudah ganti.
        foreach ($this->siswaAlphaPerluDiulang($today, $siswaBelumAbsen->pluck('id')) as $item) {
            $this->notifikasi($item->siswa, $item->tanggal);
            $this->jedaAntarKirim();
        }

        return $siswaBelumAbsen->count();
    }

    /**
     * Jeda antar pengiriman WA -- lihat services.fonnte.jeda_kirim_ms.
     * Cuma relevan kalau WA memang aktif (token diisi); kalau tidak, tidak
     * ada percobaan HTTP sama sekali jadi tidak ada apa pun buat dijaga
     * jaraknya. jeda_kirim_ms 0 (default di test) skip total.
     */
    private function jedaAntarKirim(): void
    {
        $ms = (int) config('services.fonnte.jeda_kirim_ms', 0);

        if ($ms > 0 && config('services.fonnte.token')) {
            usleep($ms * 1000);
        }
    }

    /**
     * Pasangan (siswa, tanggal) yang notifikasi 'alpha'-nya belum pernah
     * sukses lewat kanal manapun -- MENCAKUP SEMUA TANGGAL, bukan cuma
     * hari ini, supaya kegagalan yang belum sempat pulih sebelum tengah
     * malam (mis. device Fonnte disconnect semalaman) tetap dicoba ulang
     * di hari-hari berikutnya, bukan ditinggalkan diam-diam selamanya.
     * Dikelompokkan per (siswa_id, tanggal) -- bukan per siswa saja --
     * karena satu siswa bisa alpha di beberapa tanggal berbeda dan tiap
     * tanggal itu kasus terpisah (gagal di tanggal A tidak boleh bikin
     * tanggal B ikut-ikutan dianggap belum selesai, atau sebaliknya).
     * $idBaruDitandai dikecualikan supaya siswa yang baru saja gagal di
     * loop $siswaBelumAbsen pada run YANG SAMA (hari ini) tidak langsung
     * diulang lagi detik itu juga -- retry-nya baru berlaku mulai run
     * berikutnya.
     *
     * @return Collection<int, object{siswa: Siswa, tanggal: Carbon}>
     */
    private function siswaAlphaPerluDiulang(Carbon $today, Collection $idBaruDitandai): Collection
    {
        $pasanganBelumBerhasil = NotifikasiAbsensiLog::where('jenis', 'alpha')
            ->selectRaw("siswa_id, tanggal, MAX(CASE WHEN status != 'gagal' THEN 1 ELSE 0 END) as ada_berhasil")
            ->groupBy('siswa_id', 'tanggal')
            ->having('ada_berhasil', 0)
            ->get()
            ->reject(fn ($row) => $row->tanggal->isSameDay($today) && $idBaruDitandai->contains($row->siswa_id));

        $siswaById = Siswa::whereIn('id', $pasanganBelumBerhasil->pluck('siswa_id'))->get()->keyBy('id');

        return $pasanganBelumBerhasil
            ->map(fn ($row) => isset($siswaById[$row->siswa_id])
                ? (object) ['siswa' => $siswaById[$row->siswa_id], 'tanggal' => $row->tanggal]
                : null)
            ->filter()
            ->values();
    }

    private function notifikasi(Siswa $siswa, Carbon $tanggal): void
    {
        $pesan = "Yth. Orang Tua/Wali dari {$siswa->nama}, kami informasikan ananda tidak hadir di sekolah pada "
            . "{$tanggal->format('d/m/Y')} tanpa keterangan. Mohon konfirmasi ke pihak sekolah. Terima kasih.";

        // WA jadi kanal prioritas — email cuma dikirim kalau WA tidak bisa
        // dipakai buat siswa ini (nomor kosong) atau kanalnya belum
        // diaktifkan (lihat WhatsAppNotifier).
        if (app(WhatsAppNotifier::class)->kirimDanCatat($siswa, $tanggal, 'alpha', $pesan)) {
            return;
        }

        if (! $siswa->email_orang_tua) {
            NotifikasiAbsensiLog::create([
                'siswa_id' => $siswa->id,
                'siswa_nama' => $siswa->nama,
                'tanggal' => $tanggal,
                'jenis' => 'alpha',
                'kanal' => 'email',
                'kontak' => null,
                'pesan' => $pesan,
                'status' => 'tidak_ada_kontak',
            ]);

            return;
        }

        try {
            // ->queue() bukan ->send(): dispatch ke tabel jobs, dikirim
            // (dan dicoba ulang kalau gagal, lihat $tries/$backoff di
            // SiswaAlphaMail) belakangan oleh queue worker -- konsisten
            // dengan pola di AbsensiRecorder::notifikasiKehadiran(). Efek
            // sampingnya: status 'terkirim' di sini berarti "berhasil
            // didaftarkan ke antrian", bukan "dikonfirmasi sampai" --
            // kegagalan pengiriman asli (setelah retry habis) tidak
            // tercermin balik ke baris log ini.
            Mail::to($siswa->email_orang_tua)->queue(new SiswaAlphaMail($siswa->nama, $tanggal));
            $status = 'terkirim';
        } catch (Throwable) {
            $status = 'gagal';
        }

        NotifikasiAbsensiLog::create([
            'siswa_id' => $siswa->id,
            'siswa_nama' => $siswa->nama,
            'tanggal' => $tanggal,
            'jenis' => 'alpha',
            'kanal' => 'email',
            'kontak' => $siswa->email_orang_tua,
            'pesan' => $pesan,
            'status' => $status,
        ]);
    }
}
