<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')->where('role', 'guru')->update(['role' => 'wali_kelas']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('wali_kelas')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('guru')->change();
        });

        DB::table('users')->where('role', 'wali_kelas')->update(['role' => 'guru']);
    }
};
