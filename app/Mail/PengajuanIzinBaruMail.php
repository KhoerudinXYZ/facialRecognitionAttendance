<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class PengajuanIzinBaruMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $siswaNama,
        public string $kelasNama,
        public string $jenis,
        public string $keterangan,
        public Carbon $tanggal,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pengajuan '.ucfirst($this->jenis)." Baru — {$this->siswaNama}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.pengajuan-izin-baru',
        );
    }
}
