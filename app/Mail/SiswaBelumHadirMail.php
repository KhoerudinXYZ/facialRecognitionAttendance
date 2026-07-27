<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SiswaBelumHadirMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $siswaNama,
        public Carbon $tanggal,
        public string $jamCek,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Peringatan: {$this->siswaNama} Belum Hadir",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.siswa-belum-hadir',
        );
    }
}
