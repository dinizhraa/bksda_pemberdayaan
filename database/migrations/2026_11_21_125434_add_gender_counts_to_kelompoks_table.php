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
        Schema::table('kelompoks', function (Blueprint $table) {
            $table->integer('jumlah_anggota_laki_laki')->nullable()->after('jumlah_anggota');
            $table->integer('jumlah_anggota_perempuan')->nullable()->after('jumlah_anggota_laki_laki');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelompoks', function (Blueprint $table) {
            $table->dropColumn(['jumlah_anggota_laki_laki', 'jumlah_anggota_perempuan']);
        });
    }
};
