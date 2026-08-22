<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GigLeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'country' => $this->country,
            'job_type' => $this->job_type,
            'origin' => $this->origin,
            'status' => $this->status,
            'completion_status' => $this->completion_status,
            'title' => $this->title,
            'company' => $this->company,
            'description' => $this->description,
            'added_at' => $this->created_at->toIso8601String(),
        ];
    }
}
