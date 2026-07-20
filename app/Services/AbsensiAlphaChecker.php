<?php

namespace App\Services;

use App\Mail\SiswaAlphaMail;
use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\NotifikasiAbsensiLog;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Support\Carbon;
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
 * ini belum punya baris absensi hari ini ditandai alpha, lalu (kalau email
 * orang tua terdaftar) dikirimi notifikasi. Kanal WhatsApp sempat dibangun
 * tapi untuk sekarang dipakai email dulu (belum ada penyedia WhatsApp API
 * yang dipilih) — no_hp_orang_tua tetap tersimpan di Siswa untuk dipakai
 * lagi nanti.
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
        }

        return $siswaBelumAbsen->count();
    }

    private function notifikasi(Siswa $siswa, Carbon $tanggal): void
    {
        $pesan = "Yth. Orang Tua/Wali dari {$siswa->nama}, kami informasikan ananda tidak hadir di sekolah pada "
            . "{$tanggal->format('d/m/Y')} tanpa keterangan. Mohon konfirmasi ke pihak sekolah. Terima kasih.";

        if (! $siswa->email_orang_tua) {
            NotifikasiAbsensiLog::create([
                'siswa_id' => $siswa->id,
                'siswa_nama' => $siswa->nama,
                'tanggal' => $tanggal,
                'jenis' => 'alpha',
                'kontak' => null,
                'pesan' => $pesan,
                'status' => 'tidak_ada_kontak',
            ]);

            return;
        }

        try {
            Mail::to($siswa->email_orang_tua)->send(new SiswaAlphaMail($siswa->nama, $tanggal));
            $status = 'terkirim';
        } catch (Throwable) {
            $status = 'gagal';
        }

        NotifikasiAbsensiLog::create([
            'siswa_id' => $siswa->id,
            'siswa_nama' => $siswa->nama,
            'tanggal' => $tanggal,
            'jenis' => 'alpha',
            'kontak' => $siswa->email_orang_tua,
            'pesan' => $pesan,
            'status' => $status,
        ]);
    }
}
