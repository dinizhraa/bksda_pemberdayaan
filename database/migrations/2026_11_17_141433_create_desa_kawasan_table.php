<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desa_kawasan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('desa_id')
                ->constrained('desas')
                ->cascadeOnDelete();

            $table->foreignId('kawasan_id')
                ->constrained('kawasans')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['desa_id', 'kawasan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desa_kawasan');
    }
};
