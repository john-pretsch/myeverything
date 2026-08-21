<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'recurrence', 'interval_days', 'start_date', 'position'])]
class Todo extends Model
{
    /** @use HasFactory<\Database\Factories\TodoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'interval_days' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(TodoCompletion::class);
    }

    /**
     * Whether this todo has an occurrence falling on the given date.
     */
    public function isDueOn(CarbonInterface $date): bool
    {
        $date = $date->copy()->startOfDay();
        $start = $this->start_date->copy()->startOfDay();

        if ($date->lt($start)) {
            return false;
        }

        return match ($this->recurrence) {
            'once' => $date->equalTo($start),
            'daily' => true,
            'weekly' => $start->diffInDays($date) % 7 === 0,
            'custom' => $start->diffInDays($date) % max(1, (int) $this->interval_days) === 0,
            default => false,
        };
    }
}
