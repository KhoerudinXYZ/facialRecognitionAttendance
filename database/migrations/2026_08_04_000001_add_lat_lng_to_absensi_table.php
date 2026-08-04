<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Simpan koordinat GPS yang dipakai cekLokasi() -- sebelumnya lat/lng cuma
 * dipakai sesaat buat hitung jarak lalu dibuang, jadi tidak ada jejak
 * koordinat mana yang berhasil dipakai absen. Murni data audit (sama
 * seperti ip_request/ip_cocok_sekolah), tidak dipakai menolak absen apapun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable()->after('ip_cocok_sekolah');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
        });
    }

    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });
    }
};
