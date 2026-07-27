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

    // Dibaca queue worker (butuh `php artisan queue:work` jalan --
    // QUEUE_CONNECTION=database) kalau kirim gagal (SMTP timeout, server
    // mail sesaat tidak bisa dihubungi, dsb): coba lagi 30 detik, 2 menit,
    // lalu 5 menit kemudian sebelum menyerah, bukan sekali coba lalu diam.
    public int $tries = 3;
    public array $backoff = [30, 120, 300];

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
