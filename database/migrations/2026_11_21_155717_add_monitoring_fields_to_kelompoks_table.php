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
            $table->unsignedSmallInteger('tahun_pembentukan')->nullable();
            $table->unsignedSmallInteger('tahun_monitoring')->nullable();
            $table->text('catatan_kebutuhan_hasil_monev')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelompoks', function (Blueprint $table) {
            $table->dropColumn([
                'tahun_pembentukan',
                'tahun_monitoring',
                'catatan_kebutuhan_hasil_monev',
            ]);
        });
    }
};
