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
        Schema::create('kelompoks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kawasan_id')->nullable()->constrained('kawasans')->onDelete('cascade');
            $table->foreignId('desa_id')->nullable()->constrained('desas')->onDelete('set null');
            $table->integer('tahun_pembinaan')->nullable();
            $table->string('nama_kelompok');
            $table->string('nama_ketua')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('jenis_usaha')->nullable();
            $table->string('produk_unggulan')->nullable();
            $table->string('sk_kelompok')->nullable();
            $table->integer('jumlah_anggota')->default(0);
            $table->decimal('jumlah_bantuan', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelompoks');
    }
};