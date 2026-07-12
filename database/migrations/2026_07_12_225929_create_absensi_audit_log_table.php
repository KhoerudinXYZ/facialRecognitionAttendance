<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak audit absensi yang dihapus. Semua kolom data absensi disalin
 * (bukan foreign key ke tabel absensi) supaya baris log ini tetap utuh
 * dan bisa dibaca apa adanya walau baris absensi aslinya sudah tidak ada
 * lagi — itu justru inti dari fitur ini.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('absensi_audit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('absensi_id');
            $table->foreignId('siswa_id')->nullable()->constrained('siswa')->nullOnDelete();
            $table->string('siswa_nama');
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->string('status');
            $table->string('metode');
            $table->string('keterangan')->nullable();
            $table->foreignId('dihapus_oleh_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('dihapus_oleh_nama');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi_audit_log');
    }
};
