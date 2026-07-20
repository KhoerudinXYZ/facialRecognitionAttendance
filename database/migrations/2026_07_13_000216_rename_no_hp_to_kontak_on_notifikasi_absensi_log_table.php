<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * notifikasi_absensi_log baru dibuat (belum ada baris nyata) saat kanal
 * notifikasi masih WhatsApp. Sekarang beralih ke email untuk sementara
 * (lihat AbsensiAlphaChecker) — kolom no_hp diganti nama jadi kontak
 * (generik: bisa email atau nomor HP) supaya skemanya tetap jujur
 * mencerminkan kanal yang benar-benar aktif.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notifikasi_absensi_log', function (Blueprint $table) {
            $table->renameColumn('no_hp', 'kontak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifikasi_absensi_log', function (Blueprint $table) {
            $table->renameColumn('kontak', 'no_hp');
        });
    }
};
