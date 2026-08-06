<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SiswaPulangMail extends Mailable
{
    use SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    public function __construct(
        public string $siswaNama,
        public Carbon $waktu,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Konfirmasi Absen Pulang {$this->siswaNama}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.siswa-pulang',
        );
    }
}
