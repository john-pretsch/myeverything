<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrainiacAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $completed = $this->completed_at !== null;
        $elapsed = $completed
            ? $this->time_limit_seconds
            : (int) now()->diffInSeconds($this->started_at, true);

        return [
            'id' => $this->id,
            'subsection' => $this->subsection,
            'total_questions' => $this->total_questions,
            'time_limit_seconds' => $this->time_limit_seconds,
            'started_at' => $this->started_at->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'completed' => $completed,
            'score' => $this->score,
            'percent_correct' => $this->score !== null
                ? (int) round($this->score / max(1, $this->total_questions) * 100)
                : null,
            'time_remaining_seconds' => $completed ? 0 : max(0, $this->time_limit_seconds - $elapsed),
        ];
    }
}
