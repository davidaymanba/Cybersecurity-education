<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_interactions', function (Blueprint $table) {
            // Hash of (normalized_message + agent_type + lesson_id) for fast cache lookup
            $table->string('message_hash', 64)->nullable()->index()->after('agent_type');
            // Whether this record was served from cache (no tokens spent)
            $table->boolean('cache_hit')->default(false)->after('latency_ms');
        });
    }

    public function down(): void
    {
        Schema::table('ai_interactions', function (Blueprint $table) {
            $table->dropIndex(['message_hash']);
            $table->dropColumn(['message_hash', 'cache_hit']);
        });
    }
};
