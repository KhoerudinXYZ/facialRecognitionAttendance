<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * absensi.kelas_id menyimpan kelas siswa PADA SAAT baris ditulis, bukan
 * lewat join live ke siswa.kelas_id -- supaya laporan per kelas tidak
 * berubah sendiri kalau siswanya pindah kelas di kemudian hari (lihat
 * LaporanController::queryLaporan()). nullOnDelete (bukan cascade seperti
 * siswa.kelas_id): kelas yang dihapus tidak boleh ikut menghapus riwayat
 * absensi, cukup snapshot-nya jadi kosong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('siswa_id')->constrained('kelas')->nullOnDelete();
        });

        // Backfill baris lama pakai kelas_id siswa SAAT INI -- histori kelas
        // sebelum kolom ini ada memang tidak pernah disimpan di mana pun,
        // jadi ini cuma best-effort (baris yang siswanya sudah pindah kelas
        // sebelum migrasi ini tetap salah terasosiasi). Baris baru setelah
        // migrasi ini selalu snapshot yang benar.
        DB::statement('UPDATE absensi SET kelas_id = (SELECT kelas_id FROM siswa WHERE siswa.id = absensi.siswa_id) WHERE kelas_id IS NULL');
    }

    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelas_id');
        });
    }
};
