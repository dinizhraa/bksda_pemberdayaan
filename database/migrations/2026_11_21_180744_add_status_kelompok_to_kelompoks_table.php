<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelompoks', function (Blueprint $table): void {
            $table->string('status_kelompok')->nullable()->after('nama_kelompok');
        });
    }

    public function down(): void
    {
        Schema::table('kelompoks', function (Blueprint $table): void {
            $table->dropColumn('status_kelompok');
        });
    }
};
