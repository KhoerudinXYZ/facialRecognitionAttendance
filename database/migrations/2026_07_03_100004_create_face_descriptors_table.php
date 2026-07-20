<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_descriptors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->json('descriptor'); // array 128 float dari face-api.js
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_descriptors');
    }
};
