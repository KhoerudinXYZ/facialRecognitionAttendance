<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SiswaAlphaMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $siswaNama,
        public Carbon $tanggal,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Pemberitahuan Ketidakhadiran {$this->siswaNama}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.siswa-alpha',
        );
    }
}
