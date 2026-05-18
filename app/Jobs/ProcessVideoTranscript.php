<?php

namespace App\Jobs;

use App\Models\VideoResource;
use App\Models\VideoTranscript;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessVideoTranscript implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $videoResourceId;

    public function __construct(int $videoResourceId)
    {
        $this->videoResourceId = $videoResourceId;
    }

    public function handle(): void
    {
        $video = VideoResource::find($this->videoResourceId);
        if (! $video) {
            return;
        }

        // Placeholder: integrate with a transcription service (Google Speech-to-Text, Whisper, etc.)
        // For now, store an empty transcript or a note that processing is pending.
        VideoTranscript::updateOrCreate(
            ['video_resource_id' => $video->id],
            ['transcript' => 'TRANSCRIPT_PENDING']
        );
    }
}
