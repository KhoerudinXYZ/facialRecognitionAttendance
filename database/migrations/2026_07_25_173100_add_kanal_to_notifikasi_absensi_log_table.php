<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kanal WhatsApp (Fonnte) diaktifkan berdampingan dengan email — satu event
 * (kehadiran/alpha) sekarang bisa menghasilkan dua baris log, satu per
 * kanal, jadi butuh kolom ini untuk membedakan mana yang mana. Default
 * 'email' supaya baris lama (sebelum kolom ini ada) tetap benar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifikasi_absensi_log', function (Blueprint $table) {
            $table->string('kanal')->default('email')->after('jenis');
        });
    }

    public function down(): void
    {
        Schema::table('notifikasi_absensi_log', function (Blueprint $table) {
            $table->dropColumn('kanal');
        });
    }
};
