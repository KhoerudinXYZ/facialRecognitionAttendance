<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WhatsAppGagalBeruntunMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public int $jumlahGagalBeruntun,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Peringatan: Notifikasi WhatsApp Gagal Berturut-turut',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.whatsapp-gagal-beruntun',
        );
    }
}
