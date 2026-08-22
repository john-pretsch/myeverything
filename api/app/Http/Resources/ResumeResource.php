<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class ResumeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'content' => $this->content,
            'organization' => $this->organization?->name,
            'gig_lead_id' => $this->gig_lead_id,
            'view_url' => URL::temporarySignedRoute(
                'resumes.view-signed',
                now()->addMinutes(5),
                ['resume' => $this->id],
            ),
            'uploaded_at' => $this->created_at->toIso8601String(),
        ];
    }
}
