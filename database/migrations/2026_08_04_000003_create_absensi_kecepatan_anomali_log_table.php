<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak anomali kecepatan: dua bacaan GPS per absen (lokasi saat buka
 * halaman vs lokasi saat submit, lihat face-kiosk.js) yang jaraknya jauh
 * dalam waktu terlalu singkat buat ditempuh manusia beneran. Murni audit
 * trail buat direview manual -- tidak pernah dipakai menolak absen apapun,
 * sama seperti absensi_lokasi_gagal_log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_kecepatan_anomali_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->decimal('lat_buka', 10, 7);
            $table->decimal('lng_buka', 10, 7);
            $table->decimal('lat_absen', 10, 7);
            $table->decimal('lng_absen', 10, 7);
            $table->decimal('jarak_meter', 10, 2);
            $table->unsignedInteger('jeda_ms');
            $table->decimal('kecepatan_kmh', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_kecepatan_anomali_log');
    }
};
