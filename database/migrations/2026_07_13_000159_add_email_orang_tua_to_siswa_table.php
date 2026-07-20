<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email orang tua/wali — kanal notifikasi alpha yang aktif dipakai untuk
 * sekarang (lihat AbsensiAlphaChecker). no_hp_orang_tua tetap ada, tidak
 * dihapus — didiamkan untuk WhatsApp yang mungkin dipakai lagi nanti
 * begitu penyedia API sudah dipilih.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('email_orang_tua')->nullable()->after('no_hp_orang_tua');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn('email_orang_tua');
        });
    }
};
