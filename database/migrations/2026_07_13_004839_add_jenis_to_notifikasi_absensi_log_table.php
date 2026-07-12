<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom jenis membedakan dua macam notifikasi yang sekarang berbagi tabel
 * yang sama: 'alpha' (siswa tidak hadir tanpa keterangan, dari
 * AbsensiAlphaChecker) dan 'kehadiran' (konfirmasi anak sudah sampai di
 * sekolah, dari AbsensiRecorder). Default 'alpha' karena itu satu-satunya
 * jenis yang sudah ada sebelum kolom ini ditambahkan.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notifikasi_absensi_log', function (Blueprint $table) {
            $table->string('jenis')->default('alpha')->after('siswa_nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifikasi_absensi_log', function (Blueprint $table) {
            $table->dropColumn('jenis');
        });
    }
};
