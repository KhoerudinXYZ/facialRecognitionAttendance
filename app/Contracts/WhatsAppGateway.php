<?php

namespace App\Contracts;

/**
 * Seam antara AbsensiAlphaChecker dan penyedia WhatsApp API yang
 * sebenarnya (Fonnte, WABlas, Meta Cloud API, dst). Belum ada penyedia
 * yang dipilih saat fitur ini dibuat, jadi default binding-nya adalah
 * LogWhatsAppGateway (lihat AppServiceProvider) yang cuma mencatat pesan
 * ke log tanpa benar-benar mengirim — tinggal ganti binding-nya begitu
 * penyedia sudah dipilih, tanpa menyentuh AbsensiAlphaChecker sama sekali.
 */
interface WhatsAppGateway
{
    /**
     * Kirim pesan WhatsApp ke satu nomor. Return true kalau penyedia
     * menerima pesan untuk dikirim (bukan berarti sudah terbaca/delivered),
     * false kalau gagal terkirim sama sekali.
     */
    public function send(string $noHp, string $pesan): bool;
}
