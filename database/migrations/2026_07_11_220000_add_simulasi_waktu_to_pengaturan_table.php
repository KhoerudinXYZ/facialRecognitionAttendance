<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom testing-only: override jam "sekarang" yang dipakai AbsensiRecorder,
 * supaya admin bisa simulasi absen masuk/pulang tanpa nunggu jam asli.
 * Aman dihapus kapan pun (lihat AbsensiRecorder::now()/today()).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            $table->dateTime('simulasi_waktu')->nullable()->after('mulai_pulang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            $table->dropColumn('simulasi_waktu');
        });
    }
};
