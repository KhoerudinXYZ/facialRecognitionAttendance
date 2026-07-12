<?php

namespace App\Services;

use App\Mail\SiswaHadirMail;
use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\NotifikasiAbsensiLog;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AbsensiRecorder
{
    /**
     * Catat kehadiran siswa untuk hari ini: scan pertama = absen masuk
     * (status hadir/terlambat ditentukan dari Pengaturan::batas_terlambat),
     * scan kedua setelah Pengaturan::mulai_pulang = absen pulang. Absen
     * diblokir total di tanggal yang terdaftar sebagai hari libur.
     * Dipakai oleh kiosk (AbsensiController) maupun absen mandiri siswa
     * (SiswaAbsensiController) agar logikanya tidak bercabang.
     *
     * $lat/$lng hanya dipakai kalau Pengaturan::lokasiAktif() — kiosk admin
     * (AbsensiController) tidak pernah mengirimnya karena kameranya memang
     * di sekolah, jadi parameter ini opsional & backward compatible.
     */
    public function record(Siswa $siswa, ?float $lat = null, ?float $lng = null): array
    {
        $pengaturan = Pengaturan::get();

        // Pengaturan::simulasi_waktu: field testing-only, boleh dihapus
        // kapan saja tanpa mempengaruhi alur normal (lihat migration-nya).
        $now = $pengaturan->waktuSekarang();
        $today = $now->copy()->startOfDay();

        if (HariLibur::isLibur($today)) {
            return [
                'status' => 'libur',
                'message' => 'Hari ini libur, absensi tidak aktif.',
                'nama' => $siswa->nama,
            ];
        }

        $existing = Absensi::where('siswa_id', $siswa->id)
            ->whereDate('tanggal', $today)
            ->first();

        // Baris 'alpha' ditulis otomatis oleh AbsensiAlphaChecker di akhir
        // hari (bukan hasil scan siswa) — kalau siswa ternyata muncul dan
        // scan beneran setelah itu, ini tetap harus diperlakukan sebagai
        // absen masuk asli (menimpa baris alpha), bukan "sudah absen".
        if (! $existing || $existing->status === 'alpha') {
            if ($tolakLokasi = $this->cekLokasi($pengaturan, $siswa, $lat, $lng)) {
                return $tolakLokasi;
            }

            $batas = Carbon::parse($today->toDateString() . ' ' . $pengaturan->batas_terlambat);
            $status = $now->greaterThan($batas) ? 'terlambat' : 'hadir';

            $atribut = [
                'jam_masuk' => $now->format('H:i:s'),
                'jam_pulang' => null,
                'status' => $status,
                'metode' => 'face',
            ];

            if ($existing) {
                $existing->update($atribut);
            } else {
                Absensi::create([...$atribut, 'siswa_id' => $siswa->id, 'tanggal' => $today]);
            }

            $this->notifikasiKehadiran($siswa, $now, $status);

            return [
                'status' => 'success',
                'message' => "Absen masuk berhasil: {$siswa->nama} ({$status})",
                'nama' => $siswa->nama,
                'jam' => $now->format('H:i'),
                'keterangan' => $status,
            ];
        }

        if (! $existing->jam_pulang) {
            $mulaiPulang = Carbon::parse($today->toDateString() . ' ' . $pengaturan->mulai_pulang);

            if ($now->lessThan($mulaiPulang)) {
                return [
                    'status' => 'already',
                    'message' => "{$siswa->nama} sudah absen masuk hari ini. Absen pulang dibuka mulai {$pengaturan->mulai_pulang}.",
                    'nama' => $siswa->nama,
                ];
            }

            if ($tolakLokasi = $this->cekLokasi($pengaturan, $siswa, $lat, $lng)) {
                return $tolakLokasi;
            }

            $existing->update(['jam_pulang' => $now->format('H:i:s')]);

            return [
                'status' => 'success',
                'message' => "Absen pulang berhasil: {$siswa->nama}",
                'nama' => $siswa->nama,
                'jam' => $now->format('H:i'),
            ];
        }

        return [
            'status' => 'already',
            'message' => "{$siswa->nama} sudah absen masuk & pulang hari ini.",
            'nama' => $siswa->nama,
        ];
    }

    /**
     * Konfirmasi ke orang tua bahwa anaknya sudah tiba di sekolah — best
     * effort (gagal kirim tidak boleh menggagalkan absen itu sendiri),
     * dicatat ke notifikasi_absensi_log sama seperti notifikasi alpha
     * (lihat AbsensiAlphaChecker) supaya riwayatnya ada di satu tempat.
     */
    private function notifikasiKehadiran(Siswa $siswa, Carbon $waktu, string $status): void
    {
        $pesan = "Yth. Orang Tua/Wali dari {$siswa->nama}, kami informasikan ananda sudah tiba di sekolah pada "
            . "{$waktu->format('d/m/Y H:i')} ({$status}).";

        if (! $siswa->email_orang_tua) {
            NotifikasiAbsensiLog::create([
                'siswa_id' => $siswa->id,
                'siswa_nama' => $siswa->nama,
                'tanggal' => $waktu->copy()->startOfDay(),
                'jenis' => 'kehadiran',
                'kontak' => null,
                'pesan' => $pesan,
                'status' => 'tidak_ada_kontak',
            ]);

            return;
        }

        try {
            Mail::to($siswa->email_orang_tua)->send(new SiswaHadirMail($siswa->nama, $waktu, $status));
            $hasil = 'terkirim';
        } catch (Throwable) {
            $hasil = 'gagal';
        }

        NotifikasiAbsensiLog::create([
            'siswa_id' => $siswa->id,
            'siswa_nama' => $siswa->nama,
            'tanggal' => $waktu->copy()->startOfDay(),
            'jenis' => 'kehadiran',
            'kontak' => $siswa->email_orang_tua,
            'pesan' => $pesan,
            'status' => $hasil,
        ]);
    }

    /**
     * Return null kalau boleh lanjut (lokasi tidak dikonfigurasi, atau
     * siswa terdeteksi di dalam radius). Return array status 'lokasi'
     * kalau ditolak (lat/lng tidak terkirim, atau di luar radius).
     * Dipanggil tepat sebelum tiap titik tulis (bukan di awal method) —
     * supaya cek libur & cek "sudah absen" tetap menang duluan, jadi
     * siswa yang memang sudah kelar absen dapat pesan yang benar.
     */
    private function cekLokasi(Pengaturan $pengaturan, Siswa $siswa, ?float $lat, ?float $lng): ?array
    {
        if (! $pengaturan->lokasiAktif()) {
            return null;
        }

        if ($lat === null || $lng === null) {
            return [
                'status' => 'lokasi',
                'message' => 'Lokasi GPS tidak terdeteksi. Aktifkan izin lokasi lalu coba lagi.',
                'nama' => $siswa->nama,
            ];
        }

        $jarak = $this->jarakMeter(
            (float) $pengaturan->lokasi_lat,
            (float) $pengaturan->lokasi_lng,
            $lat,
            $lng
        );

        if ($jarak > $pengaturan->lokasi_radius_meter) {
            return [
                'status' => 'lokasi',
                'message' => 'Kamu berada di luar radius sekolah, absen tidak bisa dicatat.',
                'nama' => $siswa->nama,
            ];
        }

        return null;
    }

    /**
     * Jarak antara dua titik koordinat dalam meter (formula haversine).
     */
    private function jarakMeter(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $bumiRadiusMeter = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $bumiRadiusMeter * $c;
    }
}
