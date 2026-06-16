<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['role_id', 'name', 'email', 'learning_level', 'password'];

    protected $hidden = ['password', 'remember_token'];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ProgressTracking::class);
    }

    public function quizResults(): HasMany
    {
        return $this->hasMany(QuizResult::class);
    }

    public function aiInteractions(): HasMany
    {
        return $this->hasMany(AiInteraction::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(\App\Models\Bookmark::class);
    }

    public function isAdmin(): bool
    {
        return $this->role?->name === 'admin';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
