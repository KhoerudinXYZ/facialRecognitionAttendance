<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak percobaan absen yang ditolak cekLokasi() -- sebelum ini, tiap
 * penolakan (GPS tidak terkirim/akurasi jelek/di luar radius) cuma jadi
 * respons JSON sesaat ke browser lalu hilang, tidak ada bekasnya di DB.
 * Murni audit trail buat wali kelas/admin review manual (pola percobaan
 * berulang dari lokasi jauh, dsb) -- tidak dipakai memblokir apapun lebih
 * lanjut, sama sekali tidak mengubah cekLokasi() itu sendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_lokasi_gagal_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('jarak_meter', 10, 2)->nullable();
            $table->string('alasan');
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_lokasi_gagal_log');
    }
};
