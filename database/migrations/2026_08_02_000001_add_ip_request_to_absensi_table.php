<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->string('ip_request', 45)->nullable()->after('liveness_verified'); // 45 = cukup untuk IPv6
            $table->boolean('ip_cocok_sekolah')->nullable()->after('ip_request');
        });
    }

    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropColumn(['ip_request', 'ip_cocok_sekolah']);
        });
    }
};
