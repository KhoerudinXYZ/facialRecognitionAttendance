<?php

namespace App\Services;

use App\Mail\WhatsAppGagalBeruntunMail;
use App\Models\NotifikasiAbsensiLog;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
    // Bukan email fallback ke orang tua (sengaja dihindari -- lihat docblock
    // kelas ini & WhatsAppGagalBeruntunMail: kalau device Fonnte mati total,
    // fallback ke email berarti SEMUA orang tua kebanjiran email sekaligus,
    // bisa menghabiskan kuota harian Resend). Ini cuma alert internal ke
    // admin supaya device yang terputus ketahuan hari itu juga, bukan pas
    // admin kebetulan buka halaman notifikasi.
    private const AMBANG_GAGAL_BERUNTUN = 5;

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

        [$berhasil, $alasanGagal, $messageId] = $this->kirim($token, $nomor, $pesan);
        $status = $berhasil ? 'terkirim' : 'gagal';

        NotifikasiAbsensiLog::create([
            'siswa_id' => $siswa->id,
            'siswa_nama' => $siswa->nama,
            'tanggal' => $tanggal,
            'jenis' => $jenis,
            'kanal' => 'whatsapp',
            'kontak' => $nomor,
            'pesan' => $pesan,
            'status' => $status,
            'alasan_gagal' => $alasanGagal,
            'fonnte_message_id' => $messageId,
        ]);

        if ($status === 'gagal') {
            $this->peringatkanAdminJikaGagalBeruntun();
        }

        return true;
    }

    /**
     * Dipanggil FonnteWebhookController saat Fonnte melaporkan perubahan
     * status pesan belakangan (async, bisa menit/jam setelah kirim() awal).
     * $state === 0 (lihat docs Fonnte "About state 0") berarti WhatsApp
     * sendiri menolak pengiriman -- reach-out time-lock/limit reputasi
     * nomor -- walau respons /send tadi bilang sukses. Baris log yang
     * cocok diperbarui jadi 'gagal' supaya admin tidak dikira sudah
     * sampai ke orang tua padahal belum. State lain (1/2/3, dst) cuma
     * dicatat mentah di whatsapp_state, tidak mengubah status -- kita
     * belum tahu persis artinya semua & yang benar-benar perlu ditindak
     * cuma kegagalan (state 0).
     */
    public function tandaiStatusDariWebhook(string $messageId, ?int $state): void
    {
        $log = NotifikasiAbsensiLog::where('fonnte_message_id', $messageId)
            ->latest('id')
            ->first();

        if (! $log) {
            return;
        }

        $log->whatsapp_state = $state;

        if ($state === 0 && $log->status !== 'gagal') {
            $log->status = 'gagal';
            $log->alasan_gagal = 'WhatsApp menolak pengiriman (state 0) -- kemungkinan limit pengiriman atau reputasi nomor pengirim.';
        }

        $log->save();

        if ($log->status === 'gagal') {
            $this->peringatkanAdminJikaGagalBeruntun();
        }
    }

    /**
     * Kirim satu email peringatan ke admin persis saat streak gagal
     * MENCAPAI ambang -- bukan tiap kali "N terakhir semuanya gagal" (yang
     * tetap true untuk setiap kegagalan berikutnya begitu streak sudah lewat
     * ambang). Makanya ambil (ambang + 1) baris terakhir: kalau baris ke-
     * (ambang+1) dari belakang JUGA gagal, berarti streak-nya sudah lebih
     * panjang dari ambang sebelum kegagalan ini terjadi, dan alert sudah
     * dikirim pas streak baru pertama kali mencapai ambang -- jangan kirim
     * lagi. Tanpa perlu tabel/flag terpisah buat menandai "sudah pernah
     * diperingatkan", dan otomatis reset begitu ada satu pengiriman sukses
     * yang mematahkan streak-nya.
     */
    private function peringatkanAdminJikaGagalBeruntun(): void
    {
        $ambang = self::AMBANG_GAGAL_BERUNTUN;

        $statusTerbaru = NotifikasiAbsensiLog::where('kanal', 'whatsapp')
            ->latest('id')
            ->limit($ambang + 1)
            ->pluck('status');

        $streakTerbaru = $statusTerbaru->take($ambang);

        if ($streakTerbaru->count() < $ambang || $streakTerbaru->contains(fn ($s) => $s !== 'gagal')) {
            return;
        }

        if ($statusTerbaru->get($ambang) === 'gagal') {
            return;
        }

        $alasanTerakhir = NotifikasiAbsensiLog::where('kanal', 'whatsapp')
            ->where('status', 'gagal')
            ->latest('id')
            ->value('alasan_gagal');

        $emailAdmin = User::where('role', 'admin')->pluck('email');

        try {
            // ->send() bukan ->queue(): alert ini justru harus tetap terkirim
            // kalau-kalau queue worker yang bermasalah (bukan cuma Fonnte),
            // jadi sengaja tidak bergantung pada queue worker jalan.
            foreach ($emailAdmin as $email) {
                Mail::to($email)->send(new WhatsAppGagalBeruntunMail(self::AMBANG_GAGAL_BERUNTUN, $alasanTerakhir));
            }
        } catch (Throwable $e) {
            // Gagal kirim alert tidak boleh mengganggu alur utama (absen
            // masuk / penandaan alpha / peringatan dini) yang memanggil ini,
            // tapi harus tetap tercatat -- kalau tidak, admin justru tidak
            // pernah tahu WA sedang bermasalah DAN alert email-nya sendiri
            // juga gagal, tanpa jejak sama sekali.
            Log::error('Gagal mengirim WhatsAppGagalBeruntunMail ke admin: ' . $e->getMessage());
        }
    }

    /**
     * @return array{0: bool, 1: ?string, 2: ?string} [berhasil, alasan gagal (null kalau berhasil), message id Fonnte (untuk cocokkan webhook status, lihat tandaiStatusDariWebhook())]
     */
    private function kirim(string $token, string $nomor, string $pesan): array
    {
        try {
            // retry(3, 500): 3 percobaan total (1 awal + 2 ulang), jeda
            // 500ms -- dipanggil sinkron di tengah request absen siswa
            // (lihat AbsensiRecorder::notifikasiKehadiran()), jadi sengaja
            // pendek (maks ~1 detik tambahan) supaya tidak bikin siswa
            // nunggu lama cuma buat menangani gangguan sesaat (device
            // Fonnte lagi sibuk sepersekian detik, bukan benar-benar down
            // berkepanjangan). Laravel retry($times,...): $times itu total
            // percobaan, bukan jumlah ulangan tambahan.
            $response = Http::withHeaders(['Authorization' => $token])
                ->asForm()
                ->retry(3, 500, throw: false)
                ->post('https://api.fonnte.com/send', [
                    'target' => $nomor,
                    'message' => $pesan,
                ]);

            if ($response->successful() && $response->json('status') === true) {
                return [true, null, $this->messageIdDariRespons($response)];
            }

            return [false, $this->alasanGagalDariRespons($response), null];
        } catch (Throwable $e) {
            return [false, Str::limit($e->getMessage(), 255), null];
        }
    }

    /**
     * Respons sukses /send Fonnte: {"id": ["80367170"], ...} -- array
     * karena satu request bisa broadcast ke banyak target, tapi kirim()
     * di sini selalu satu target per panggilan jadi ambil elemen pertama.
     */
    private function messageIdDariRespons(Response $response): ?string
    {
        $id = $response->json('id');

        if (is_array($id)) {
            return isset($id[0]) ? (string) $id[0] : null;
        }

        return $id !== null ? (string) $id : null;
    }

    /**
     * Fonnte biasanya menyertakan 'reason' di body JSON kalau status
     * gagal (lihat test WhatsAppNotifier) -- tapi kalau responsnya bukan
     * JSON sama sekali (mis. gateway error, HTML error page), jatuh balik
     * ke ringkasan kode HTTP + potongan body mentah supaya tetap ada
     * sesuatu yang berguna buat admin, bukan kosong.
     */
    private function alasanGagalDariRespons(Response $response): string
    {
        $alasan = $response->json('reason') ?? $response->json('message');

        if ($alasan) {
            return Str::limit((string) $alasan, 255);
        }

        return Str::limit("HTTP {$response->status()}: {$response->body()}", 255);
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
