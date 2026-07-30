<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alasan mentah dari Fonnte/exception saat status = 'gagal' -- sebelumnya
 * cuma tersimpan status gagal/berhasil tanpa detail kenapanya, jadi admin
 * harus buka dashboard Fonnte sendiri buat tahu penyebabnya (device
 * disconnect, nomor diblokir, dll). Nullable karena cuma relevan buat
 * baris 'gagal' (dan cuma untuk kanal WA -- gagal kirim email dari Mail
 * facade tidak mengembalikan alasan sedetail ini).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notifikasi_absensi_log', function (Blueprint $table) {
            $table->string('alasan_gagal')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifikasi_absensi_log', function (Blueprint $table) {
            $table->dropColumn('alasan_gagal');
        });
    }
};
