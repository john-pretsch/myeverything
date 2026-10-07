<?php

namespace App\Http\Resources;

use App\Services\Resumes\ProfileImageProcessor;
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
            'is_primary' => $this->is_primary,
            'profile_image_url' => $this->profileImageUrl(),
            'view_url' => URL::temporarySignedRoute(
                'resumes.view-signed',
                now()->addMinutes(5),
                ['resume' => $this->id],
            ),
            'uploaded_at' => $this->created_at->toIso8601String(),
        ];
    }

    private function profileImageUrl(): ?string
    {
        if ($this->mime_type !== 'text/html') {
            return null;
        }

        $filename = ProfileImageProcessor::filenameFor($this->user_id);
        $path = ProfileImageProcessor::directory().'/'.$filename;

        return is_file($path)
            ? url('/api/assets/images/'.$filename).'?v='.filemtime($path)
            : null;
    }
}
