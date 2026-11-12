<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use LogicException;

/**
 * The signed-in person, as reported by Jepflow SSO. There is no users table:
 * the id is the SSO user id, which the per-user tables store as `user_id`.
 * Instances are built from the profile kept in the session and are never
 * persisted, so only relations (not save/update) are meaningful.
 */
class User extends Authenticatable
{
    public $incrementing = false;

    /** @param array{id: int|string, name?: ?string, email?: ?string} $profile */
    public static function fromSso(array $profile): static
    {
        $user = new static;
        $user->forceFill([
            'id' => (int) $profile['id'],
            'name' => $profile['name'] ?? null,
            'email' => $profile['email'] ?? null,
        ]);
        $user->exists = true;

        return $user;
    }

    public function save(array $options = []): never
    {
        throw new LogicException('Users live in Jepflow SSO and cannot be saved here.');
    }

    public function newsSources(): BelongsToMany
    {
        return $this->belongsToMany(NewsSource::class, 'news_source_user')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class)->orderBy('position');
    }

    public function gigLeads(): HasMany
    {
        return $this->hasMany(GigLead::class);
    }

    public function interviewQuestions(): HasMany
    {
        return $this->hasMany(InterviewQuestion::class);
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

    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->id, config('auth.admin_sso_ids'), true);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->isAdmin() ? 'admin' : 'user',
        ];
    }
}
