<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom opsional: titik sekolah + radius toleransi buat verifikasi lokasi
 * GPS saat absen mandiri. All-or-nothing (lihat Pengaturan::lokasiAktif()) —
 * kalau kosong, AbsensiRecorder tidak pernah mengecek lokasi sama sekali.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            $table->decimal('lokasi_lat', 10, 7)->nullable()->after('simulasi_waktu');
            $table->decimal('lokasi_lng', 10, 7)->nullable()->after('lokasi_lat');
            $table->unsignedInteger('lokasi_radius_meter')->nullable()->after('lokasi_lng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            $table->dropColumn(['lokasi_lat', 'lokasi_lng', 'lokasi_radius_meter']);
        });
    }
};
