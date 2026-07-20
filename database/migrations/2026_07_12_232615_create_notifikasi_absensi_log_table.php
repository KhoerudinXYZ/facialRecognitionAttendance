<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak percobaan notifikasi WhatsApp orang tua saat siswa alpha (lihat
 * AbsensiAlphaChecker). siswa_nama disalin (bukan cuma foreign key) supaya
 * baris log tetap terbaca walau siswa-nya kemudian dihapus, sama seperti
 * pola absensi_audit_log.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifikasi_absensi_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->nullable()->constrained('siswa')->nullOnDelete();
            $table->string('siswa_nama');
            $table->date('tanggal');
            $table->string('no_hp')->nullable();
            $table->string('pesan');
            $table->string('status'); // terkirim | gagal | tidak_ada_no_hp
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi_absensi_log');
    }
};
