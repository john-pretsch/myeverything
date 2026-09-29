<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'sso_id'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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

    public function newsSources(): BelongsToMany
    {
        return $this->belongsToMany(NewsSource::class, 'news_source_user')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function newsArticleFeedback(): HasMany
    {
        return $this->hasMany(NewsArticleFeedback::class);
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class)->orderBy('position');
    }

    public function gigLeads(): HasMany
    {
        return $this->hasMany(GigLead::class);
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    public function brainiacAttempts(): HasMany
    {
        return $this->hasMany(BrainiacAttempt::class);
    }
}
