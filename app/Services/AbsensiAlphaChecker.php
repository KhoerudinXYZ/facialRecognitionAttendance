<?php

namespace App\Services;

use App\Contracts\WhatsAppGateway;
use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\NotifikasiAbsensiLog;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Support\Carbon;

/**
 * Jalan di akhir hari (lihat perintah absensi:cek-alpha & jadwalnya di
 * routes/console.php): siswa aktif yang sampai saat ini belum punya baris
 * absensi hari ini ditandai alpha, lalu (kalau nomor WhatsApp orang tua
 * terdaftar) dikirimi notifikasi. AbsensiRecorder tahu cara menimpa baris
 * alpha ini kalau siswa ternyata scan beneran setelahnya (lihat komentar
 * di AbsensiRecorder::record()).
 */
class AbsensiAlphaChecker
{
    public function __construct(private WhatsAppGateway $gateway)
    {
    }

    /**
     * Return jumlah siswa yang baru ditandai alpha (0 kalau hari libur).
     */
    public function jalankan(): int
    {
        $pengaturan = Pengaturan::get();
        $today = $pengaturan->waktuSekarang()->startOfDay();

        if (HariLibur::isLibur($today)) {
            return 0;
        }

        $siswaBelumAbsen = Siswa::where('is_active', true)
            ->whereDoesntHave('absensi', fn ($q) => $q->whereDate('tanggal', $today))
            ->get();

        foreach ($siswaBelumAbsen as $siswa) {
            Absensi::create([
                'siswa_id' => $siswa->id,
                'tanggal' => $today,
                'status' => 'alpha',
                'metode' => 'manual',
            ]);

            $this->notifikasi($siswa, $today);
        }

        return $siswaBelumAbsen->count();
    }

    private function notifikasi(Siswa $siswa, Carbon $tanggal): void
    {
        $pesan = "Yth. Orang Tua/Wali dari {$siswa->nama}, kami informasikan ananda tidak hadir di sekolah pada "
            . "{$tanggal->format('d/m/Y')} tanpa keterangan. Mohon konfirmasi ke pihak sekolah. Terima kasih.";

        if (! $siswa->no_hp_orang_tua) {
            NotifikasiAbsensiLog::create([
                'siswa_id' => $siswa->id,
                'siswa_nama' => $siswa->nama,
                'tanggal' => $tanggal,
                'no_hp' => null,
                'pesan' => $pesan,
                'status' => 'tidak_ada_no_hp',
            ]);

            return;
        }

        $berhasil = $this->gateway->send($siswa->no_hp_orang_tua, $pesan);

        NotifikasiAbsensiLog::create([
            'siswa_id' => $siswa->id,
            'siswa_nama' => $siswa->nama,
            'tanggal' => $tanggal,
            'no_hp' => $siswa->no_hp_orang_tua,
            'pesan' => $pesan,
            'status' => $berhasil ? 'terkirim' : 'gagal',
        ]);
    }
}
