<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE pengajuan_izin MODIFY COLUMN jenis ENUM('izin', 'sakit', 'pulang_cepat') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE pengajuan_izin MODIFY COLUMN jenis ENUM('izin', 'sakit') NOT NULL");
    }
};
