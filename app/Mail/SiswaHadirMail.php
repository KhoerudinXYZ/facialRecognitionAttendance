<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SiswaHadirMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $siswaNama,
        public Carbon $waktu,
        public string $status,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Konfirmasi Kehadiran {$this->siswaNama}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.siswa-hadir',
        );
    }
}
