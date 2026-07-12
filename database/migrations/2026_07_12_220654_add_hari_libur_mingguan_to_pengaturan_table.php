<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom opsional: daftar hari dalam seminggu (0=Minggu..6=Sabtu, cocok
 * dengan Carbon::dayOfWeek) yang otomatis dianggap libur setiap minggu,
 * supaya admin tidak perlu menambahkan Sabtu/Minggu satu per satu ke
 * hari_libur. Nullable/default kosong = tidak ada perubahan perilaku
 * (semua hari tetap hari sekolah kecuali didaftarkan manual di hari_libur).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            $table->json('hari_libur_mingguan')->nullable()->after('lokasi_radius_meter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            $table->dropColumn('hari_libur_mingguan');
        });
    }
};
