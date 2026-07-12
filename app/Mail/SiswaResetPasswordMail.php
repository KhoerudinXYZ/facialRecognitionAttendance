<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SiswaResetPasswordMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $siswaNama,
        public string $resetUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reset Password Akun {$this->siswaNama}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.siswa-reset-password',
        );
    }
}
