<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ntes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kawasan_id')->constrained('kawasans')->onDelete('cascade');
            $table->integer('tahun')->default(2025);
            $table->decimal('nte_langsung', 15, 2)->default(0);
            $table->decimal('nte_tidak_langsung', 15, 2)->default(0);
            $table->decimal('total_nte', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ntes');
    }
};