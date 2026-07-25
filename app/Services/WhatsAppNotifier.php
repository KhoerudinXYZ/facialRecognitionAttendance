<?php

namespace App\Services;

use App\Models\NotifikasiAbsensiLog;
use App\Models\Siswa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Kanal WhatsApp lewat Fonnte (https://fonnte.com), berdampingan dengan
 * email — best effort sama seperti kanal email di AbsensiRecorder /
 * AbsensiAlphaChecker: kegagalan kirim (nomor kosong, token belum diisi,
 * device Fonnte offline, dsb) tidak boleh menggagalkan proses absen/alpha
 * itu sendiri, cuma dicatat ke notifikasi_absensi_log (kanal 'whatsapp').
 *
 * FONNTE_TOKEN kosong di .env = kanal ini nonaktif diam-diam, tidak ada
 * percobaan HTTP sama sekali (supaya tidak menghasilkan baris "gagal" di
 * log padahal memang sengaja belum diaktifkan).
 */
class WhatsAppNotifier
{
    public function kirimDanCatat(Siswa $siswa, Carbon $tanggal, string $jenis, string $pesan): void
    {
        $token = config('services.fonnte.token');

        if (! $token) {
            // Kanal belum diaktifkan sama sekali (token kosong) — tidak
            // dicatat ke log supaya tabel tidak kebanjiran baris "nonaktif"
            // di setiap event selama sekolah belum daftar Fonnte.
            return;
        }

        $nomor = $this->normalisasiNomor($siswa->no_hp_orang_tua);

        if (! $nomor) {
            NotifikasiAbsensiLog::create([
                'siswa_id' => $siswa->id,
                'siswa_nama' => $siswa->nama,
                'tanggal' => $tanggal,
                'jenis' => $jenis,
                'kanal' => 'whatsapp',
                'kontak' => null,
                'pesan' => $pesan,
                'status' => 'tidak_ada_kontak',
            ]);

            return;
        }

        NotifikasiAbsensiLog::create([
            'siswa_id' => $siswa->id,
            'siswa_nama' => $siswa->nama,
            'tanggal' => $tanggal,
            'jenis' => $jenis,
            'kanal' => 'whatsapp',
            'kontak' => $nomor,
            'pesan' => $pesan,
            'status' => $this->kirim($token, $nomor, $pesan) ? 'terkirim' : 'gagal',
        ]);
    }

    private function kirim(string $token, string $nomor, string $pesan): bool
    {
        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->asForm()
                ->post('https://api.fonnte.com/send', [
                    'target' => $nomor,
                    'message' => $pesan,
                ]);

            return $response->successful() && $response->json('status') === true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Fonnte butuh format 62xxxxxxxxxx (tanpa +, tanpa 0 di depan) — nomor
     * di database bisa masuk dalam berbagai format tergantung siapa yang
     * mengetik (081.., 62812.., +62812.., dst), jadi dinormalisasi di sini
     * alih-alih memaksa format ketat di form pendaftaran siswa.
     */
    private function normalisasiNomor(?string $noHp): ?string
    {
        if (! $noHp) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $noHp);

        if (! $digits) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62' . $digits;
        }

        return $digits;
    }
}
