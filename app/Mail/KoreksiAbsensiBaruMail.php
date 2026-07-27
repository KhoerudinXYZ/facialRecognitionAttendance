<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class KoreksiAbsensiBaruMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $siswaNama,
        public string $kelasNama,
        public Carbon $tanggal,
        public string $statusDiminta,
        public string $alasan,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Pengajuan Koreksi Absensi — {$this->siswaNama}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.koreksi-absensi-baru',
        );
    }
}
