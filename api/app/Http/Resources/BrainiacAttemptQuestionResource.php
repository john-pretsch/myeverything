<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expects the 'question' and 'attempt' relations to be loaded.
 * The correct answer and explanation are withheld until the parent
 * attempt is completed, so an in-progress test can't be peeked at.
 */
class BrainiacAttemptQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $completed = $this->attempt->completed_at !== null;

        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'position' => $this->position,
            'category' => $this->question->category,
            'prompt' => $this->question->prompt,
            'options' => $this->question->options,
            'selected_option' => $this->selected_option,
            'answered_at' => $this->answered_at?->toIso8601String(),
            'is_correct' => $this->is_correct,
            'correct_option' => $this->when($completed, $this->question->correct_option),
            'explanation' => $this->when($completed, $this->question->explanation),
        ];
    }
}
