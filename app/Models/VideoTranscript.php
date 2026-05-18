<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoTranscript extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_resource_id',
        'transcript',
    ];

    public function videoResource()
    {
        return $this->belongsTo(VideoResource::class);
    }
}
