<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->interview_category_id,
            'question' => $this->question,
            'answer' => $this->answer,
            'position' => $this->position,
        ];
    }
}
