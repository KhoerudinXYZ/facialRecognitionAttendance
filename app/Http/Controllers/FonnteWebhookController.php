<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Endpoint publik (di luar auth, di luar CSRF -- lihat bootstrap/app.php)
 * yang dipasang manual di dashboard Fonnte: device->edit->Webhook Update
 * Message Status, URL /webhooks/fonnte/{secret}. Fonnte mem-POST ke sini
 * setiap kali status pesan WA berubah, termasuk "state 0" (WhatsApp
 * menolak kirim meski respons /send awal bilang sukses -- lihat
 * WhatsAppNotifier::tandaiStatusDariWebhook()).
 */
class FonnteWebhookController extends Controller
{
    public function __invoke(Request $request, string $secret, WhatsAppNotifier $notifier): Response
    {
        $expected = config('services.fonnte.webhook_secret');

        // hash_equals, bukan '===': secret ini satu-satunya penghalang
        // (Fonnte tidak menandatangani requestnya sendiri, lihat docblock
        // config('services.fonnte.webhook_secret')) jadi perbandingannya
        // sengaja constant-time. Kosong = endpoint memang dimatikan.
        if (! $expected || ! hash_equals($expected, $secret)) {
            abort(404);
        }

        $id = $request->string('id')->toString();

        // Tanpa id, tidak ada yang bisa dicocokkan ke baris log manapun --
        // tetap balas 200 (bukan 422) supaya Fonnte tidak retry-storm kita
        // untuk payload yang memang tidak pernah bisa diproses.
        if ($id !== '') {
            $state = $request->input('state');
            $notifier->tandaiStatusDariWebhook($id, $state !== null ? (int) $state : null);
        }

        return response()->noContent();
    }
}
