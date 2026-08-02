<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            $table->string('ip_sekolah')->nullable()->after('lokasi_radius_meter');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            $table->dropColumn('ip_sekolah');
        });
    }
};
