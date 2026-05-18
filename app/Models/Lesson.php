<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lesson extends Model
{
    protected $fillable = [
        'section_id', 'title', 'slug', 'category', 'summary', 'content',
        'code_examples', 'difficulty', 'duration_minutes', 'sort_order', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'code_examples' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(VideoResource::class);
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ProgressTracking::class);
    }
}
