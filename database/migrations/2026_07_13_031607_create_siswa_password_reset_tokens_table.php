<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel token reset password khusus siswa — terpisah dari
 * password_reset_tokens milik staff supaya "email" di sini (yang
 * sebenarnya email_orang_tua, siswa tidak punya email sendiri) tidak
 * pernah tertukar dengan token staff walau kebetulan sama persis.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('siswa_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswa_password_reset_tokens');
    }
};
