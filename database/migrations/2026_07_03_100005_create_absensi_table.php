<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->enum('status', ['hadir', 'terlambat', 'izin', 'sakit', 'alpha'])->default('hadir');
            $table->enum('metode', ['face', 'manual'])->default('face');
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['siswa_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};
