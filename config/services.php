<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
        // Rollout bertahap: alpha (volume kecil, cuma siswa yang tidak hadir)
        // aktif duluan begitu token diisi. Kehadiran (volume tinggi, burst
        // tiap pagi) sengaja dikunci di belakang toggle terpisah ini supaya
        // nomor WhatsApp "menghangat" dulu sebelum dipakai volume tinggi.
        'kehadiran_aktif' => env('FONNTE_KEHADIRAN_AKTIF', false),
        // Jeda antar pengiriman WA saat AbsensiAlphaChecker/AbsensiBelumHadirChecker
        // memproses BANYAK siswa dalam satu run -- kirim bertubi-tubi ke banyak
        // nomor berbeda dalam hitungan detik adalah pola yang bisa membuat
        // WhatsApp/Fonnte men-disconnect device (lihat insiden 2026-07-30).
        // Tidak berlaku buat notifikasi kehadiran real-time per siswa
        // (AbsensiRecorder) -- itu satu request per siswa, bukan burst.
        'jeda_kirim_ms' => env('FONNTE_JEDA_KIRIM_MS', 1500),
        // Dipasang manual di dashboard Fonnte (device->edit->Webhook Update
        // Message Status) sebagai bagian URL: /webhooks/fonnte/{secret}.
        // Fonnte sendiri tidak menandatangani/mengautentikasi request webhook-
        // nya (lihat docs), jadi secret di path ini satu-satunya penghalang
        // supaya orang lain tidak bisa mengirim update status palsu.
        'webhook_secret' => env('FONNTE_WEBHOOK_SECRET'),
    ],

];
