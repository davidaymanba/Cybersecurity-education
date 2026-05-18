<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoResource extends Model
{
    protected $fillable = [
        'lesson_id', 'title', 'youtube_id', 'channel_name', 'channel_id',
        'thumbnail_url', 'description', 'approved',
    ];

    protected function casts(): array
    {
        return ['approved' => 'boolean'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
