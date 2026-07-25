<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sebelumnya pakai DB::statement('ALTER TABLE ... MODIFY COLUMN ... ENUM(...)')
 * — sintaks itu MySQL-only, gagal total di SQLite (dipakai testing, lihat
 * phpunit.xml) dan menjatuhkan seluruh migration stack untuk setiap test
 * di repo ini. Blueprint::enum()->change() portable ke kedua driver, sama
 * seperti pola di make_bukti_nullable_in_pengajuan_izin_table.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_izin', function (Blueprint $table) {
            $table->enum('jenis', ['izin', 'sakit', 'pulang_cepat'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_izin', function (Blueprint $table) {
            $table->enum('jenis', ['izin', 'sakit'])->change();
        });
    }
};
