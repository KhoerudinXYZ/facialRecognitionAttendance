<?php

namespace App\Services;

use App\Models\NotifikasiAbsensiLog;
use App\Models\Siswa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Kanal WhatsApp lewat Fonnte (https://fonnte.com) — sekarang jadi kanal
 * PRIORITAS: kalau siswa punya nomor WA orang tua & kanal ini aktif,
 * kirimDanCatat() return true dan pemanggil (AbsensiRecorder/
 * AbsensiAlphaChecker/AbsensiBelumHadirChecker) TIDAK lagi mengirim email
 * dobel — email cuma jadi cadangan kalau WA tidak bisa dipakai untuk siswa
 * itu (nomor kosong) atau kanalnya memang belum diaktifkan (token kosong).
 * Return value cuma menandai "apakah WA dicoba" (token & nomor ada), bukan
 * "apakah benar-benar terkirim" — kegagalan jaringan/HTTP tetap dicatat
 * sebagai status 'gagal' di log, tapi TIDAK memicu email susulan supaya
 * tidak muncul notifikasi dobel yang membingungkan orang tua kalau
 * masalahnya cuma sesaat.
 *
 * FONNTE_TOKEN kosong di .env = kanal ini nonaktif diam-diam, tidak ada
 * percobaan HTTP sama sekali (supaya tidak menghasilkan baris "gagal" di
 * log padahal memang sengaja belum diaktifkan) — pemanggil otomatis jatuh
 * ke email seperti sebelum kanal WA ada.
 */
class WhatsAppNotifier
{
    public function kirimDanCatat(Siswa $siswa, Carbon $tanggal, string $jenis, string $pesan): bool
    {
        $token = config('services.fonnte.token');

        if (! $token) {
            // Kanal belum diaktifkan sama sekali (token kosong) — tidak
            // dicatat ke log supaya tabel tidak kebanjiran baris "nonaktif"
            // di setiap event selama sekolah belum daftar Fonnte.
            return false;
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

            return false;
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

        return true;
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
