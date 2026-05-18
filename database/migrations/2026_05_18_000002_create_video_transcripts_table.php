<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_transcripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_resource_id')->constrained('video_resources')->onDelete('cascade');
            $table->longText('transcript')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_transcripts');
    }
};
