<?php

namespace App\Services;

use App\Mail\SiswaBelumHadirMail;
use App\Models\HariLibur;
use App\Models\NotifikasiAbsensiLog;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Peringatan dini "belum hadir" — beda tujuan dari AbsensiAlphaChecker:
 * ini cuma mengingatkan orang tua di jam kritis (mis. 09:30), siswa
 * mungkin masih dalam perjalanan, jadi TIDAK menulis baris absensi
 * (biarkan status akhir hari tetap tugas AlphaChecker di mulai_pulang).
 * Nonaktif diam-diam kalau Pengaturan::jam_cek_belum_hadir kosong — lihat
 * Pengaturan::cekBelumHadirAktif().
 *
 * Dijadwalkan lebih rapat dari alpha checker (lihat routes/console.php)
 * karena keterlambatan besar mengurangi gunanya sebagai peringatan dini;
 * dedup "sekali per hari per siswa" dijaga lewat notifikasi_absensi_log
 * (jenis 'belum_hadir') supaya cron yang jalan tiap beberapa menit tidak
 * mengirim ulang ke siswa yang sama.
 */
class AbsensiBelumHadirChecker
{
    /**
     * Return jumlah siswa yang baru dinotifikasi (0 kalau fitur nonaktif,
     * hari libur, belum lewat jam cek, atau tidak ada kandidat baru).
     */
    public function jalankan(): int
    {
        $pengaturan = Pengaturan::get();

        if (! $pengaturan->cekBelumHadirAktif()) {
            return 0;
        }

        $now = $pengaturan->waktuSekarang();
        $today = $now->copy()->startOfDay();

        if (HariLibur::isLibur($today)) {
            return 0;
        }

        $bolehJalan = Carbon::parse($today->toDateString() . ' ' . $pengaturan->jam_cek_belum_hadir);

        if ($now->lessThan($bolehJalan)) {
            return 0;
        }

        // status != 'gagal' (bukan "punya baris log sama sekali") supaya
        // siswa yang percobaan sebelumnya gagal (mis. Fonnte lagi disconnect)
        // otomatis dicoba ulang di run 10-menitan berikutnya begitu channel-nya
        // pulih, bukan dianggap "sudah selesai" selamanya cuma karena pernah
        // gagal sekali.
        $sudahDinotifikasi = NotifikasiAbsensiLog::whereDate('tanggal', $today)
            ->where('jenis', 'belum_hadir')
            ->where('status', '!=', 'gagal')
            ->pluck('siswa_id');

        $siswaBelumHadir = Siswa::where('is_active', true)
            ->whereDoesntHave('absensi', fn ($q) => $q->whereDate('tanggal', $today))
            ->whereDoesntHave('pengajuanIzin', fn ($q) => $q->whereDate('tanggal', $today)->where('status', 'menunggu'))
            ->whereNotIn('id', $sudahDinotifikasi)
            ->get();

        foreach ($siswaBelumHadir as $siswa) {
            $this->notifikasi($siswa, $today, $pengaturan->jam_cek_belum_hadir);
        }

        return $siswaBelumHadir->count();
    }

    private function notifikasi(Siswa $siswa, Carbon $tanggal, string $jamCek): void
    {
        $jamCekSingkat = Str::of($jamCek)->substr(0, 5);
        $pesan = "Yth. Orang Tua/Wali dari {$siswa->nama}, kami informasikan ananda belum hadir di sekolah hingga "
            . "pukul {$jamCekSingkat}. Mohon konfirmasi ke pihak sekolah. Terima kasih.";

        // WA jadi kanal prioritas — email cuma dikirim kalau WA tidak bisa
        // dipakai buat siswa ini (nomor kosong) atau kanalnya belum
        // diaktifkan (lihat WhatsAppNotifier). Sama seperti alpha (bukan
        // kehadiran): volume kecil (cuma siswa yang belum hadir), jadi
        // tidak dikunci di belakang FONNTE_KEHADIRAN_AKTIF — cukup
        // FONNTE_TOKEN terisi.
        if (app(WhatsAppNotifier::class)->kirimDanCatat($siswa, $tanggal, 'belum_hadir', $pesan)) {
            return;
        }

        if (! $siswa->email_orang_tua) {
            NotifikasiAbsensiLog::create([
                'siswa_id' => $siswa->id,
                'siswa_nama' => $siswa->nama,
                'tanggal' => $tanggal,
                'jenis' => 'belum_hadir',
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
            // SiswaBelumHadirMail) belakangan oleh queue worker --
            // konsisten dengan pola di AbsensiRecorder::notifikasiKehadiran().
            // Efek sampingnya: status 'terkirim' di sini berarti "berhasil
            // didaftarkan ke antrian", bukan "dikonfirmasi sampai".
            Mail::to($siswa->email_orang_tua)->queue(new SiswaBelumHadirMail($siswa->nama, $tanggal, (string) $jamCekSingkat));
            $status = 'terkirim';
        } catch (Throwable) {
            $status = 'gagal';
        }

        NotifikasiAbsensiLog::create([
            'siswa_id' => $siswa->id,
            'siswa_nama' => $siswa->nama,
            'tanggal' => $tanggal,
            'jenis' => 'belum_hadir',
            'kanal' => 'email',
            'kontak' => $siswa->email_orang_tua,
            'pesan' => $pesan,
            'status' => $status,
        ]);
    }
}
