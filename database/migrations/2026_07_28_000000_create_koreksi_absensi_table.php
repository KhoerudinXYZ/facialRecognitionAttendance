<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengajuan koreksi absensi -- beda dari pengajuan_izin (yang soal hari
 * ini/besok, siswa mendeklarasikan niat sebelum kejadian): ini soal
 * TANGGAL YANG SUDAH LEWAT, siswa membantah baris absensi yang sudah
 * tercatat (mis. kena bug, ditandai alpha padahal sebenarnya hadir).
 * Tabel terpisah (bukan jenis baru di pengajuan_izin) supaya tidak
 * tabrakan sama unique(siswa_id, tanggal) di sana.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('koreksi_absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('status_diminta', ['hadir', 'terlambat', 'izin', 'sakit']);
            $table->string('alasan');
            $table->string('bukti')->nullable();
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->string('catatan_admin')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['siswa_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('koreksi_absensi');
    }
};
