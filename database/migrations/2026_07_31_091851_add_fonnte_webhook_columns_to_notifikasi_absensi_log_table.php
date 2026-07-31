<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * fonnte_message_id: id pesan dari respons /send Fonnte (lihat
 * WhatsAppNotifier::kirim()), dipakai untuk mencocokkan baris ini saat
 * webhook status Fonnte masuk belakangan. whatsapp_state: nilai "state"
 * mentah dari webhook itu -- terpisah dari kolom status supaya status
 * ('terkirim'/'gagal') tetap arti utamanya "boleh dianggap sukses",
 * sementara whatsapp_state simpan histori mentahnya untuk debugging.
 * Keduanya nullable: baris lama (sebelum webhook ada) & baris email
 * tidak akan pernah punya nilai ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifikasi_absensi_log', function (Blueprint $table) {
            $table->string('fonnte_message_id')->nullable()->after('kontak')->index();
            $table->unsignedTinyInteger('whatsapp_state')->nullable()->after('fonnte_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('notifikasi_absensi_log', function (Blueprint $table) {
            $table->dropColumn(['fonnte_message_id', 'whatsapp_state']);
        });
    }
};
