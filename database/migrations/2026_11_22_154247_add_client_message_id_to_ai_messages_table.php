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
        Schema::table('ai_messages', function (Blueprint $table) {
            $table->string('client_message_id', 36)->nullable();
            $table->unique(
                ['ai_conversation_id', 'role', 'client_message_id'],
                'ai_messages_client_message_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_messages', function (Blueprint $table) {
            $table->dropUnique('ai_messages_client_message_unique');
            $table->dropColumn('client_message_id');
        });
    }
};
