<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TodoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'recurrence' => $this->recurrence,
            'interval_days' => $this->interval_days,
            'start_date' => $this->start_date->toDateString(),
            'position' => $this->position,
            'due_today' => $this->due_today,
            'completed_today' => $this->completed_today,
        ];
    }
}
