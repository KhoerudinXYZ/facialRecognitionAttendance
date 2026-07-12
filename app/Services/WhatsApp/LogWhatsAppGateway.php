<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGateway;
use Illuminate\Support\Facades\Log;

/**
 * Implementasi default selama belum ada akun penyedia WhatsApp API
 * (Fonnte/WABlas/dst) yang dipilih. Tidak mengirim apa pun ke luar —
 * cuma mencatat ke log supaya AbsensiAlphaChecker & notifikasi_absensi_log
 * tetap bisa diuji/dipakai hari ini. Selalu "berhasil" (true) supaya
 * alurnya bisa diverifikasi end-to-end tanpa kredensial API asli.
 */
class LogWhatsAppGateway implements WhatsAppGateway
{
    public function send(string $noHp, string $pesan): bool
    {
        Log::info('[WhatsApp simulasi] Pesan yang akan dikirim', [
            'no_hp' => $noHp,
            'pesan' => $pesan,
        ]);

        return true;
    }
}
