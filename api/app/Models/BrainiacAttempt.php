<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'subsection', 'total_questions', 'time_limit_seconds', 'started_at', 'completed_at', 'score'])]
class BrainiacAttempt extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attemptQuestions(): HasMany
    {
        return $this->hasMany(BrainiacAttemptQuestion::class, 'attempt_id');
    }

    public function isExpired(): bool
    {
        if ($this->completed_at !== null) {
            return false;
        }

        return now()->greaterThan($this->started_at->copy()->addSeconds($this->time_limit_seconds));
    }

    /**
     * Score the attempt from whatever answers exist and mark it complete.
     * Safe to call more than once — a no-op once already completed.
     */
    public function finalize(): void
    {
        if ($this->completed_at !== null) {
            return;
        }

        $this->update([
            'completed_at' => now(),
            'score' => $this->attemptQuestions()->where('is_correct', true)->count(),
        ]);
    }
}
