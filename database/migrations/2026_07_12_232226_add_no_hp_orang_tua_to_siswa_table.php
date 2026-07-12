<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nomor WhatsApp orang tua/wali, dipakai untuk notifikasi otomatis saat
 * siswa alpha (lihat AbsensiAlphaChecker). Nullable — siswa tanpa nomor
 * ini tetap absen normal, cuma tidak ada notifikasi yang bisa dikirim.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('no_hp_orang_tua')->nullable()->after('nisn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn('no_hp_orang_tua');
        });
    }
};
