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
            'convertible_to' => $this->convertibleTo(),
            'is_primary' => $this->is_primary,
            'profile_image_url' => $this->profileImageUrl(),
            'views' => collect([
                [$this->mime_type, null],
                [$this->alt_mime_type, 'alt'],
            ])->filter(fn ($v) => in_array($v[0], ['application/pdf', 'text/html'], true))
                ->map(fn ($v) => [
                    'format' => $v[0] === 'text/html' ? 'html' : 'pdf',
                    'url' => URL::temporarySignedRoute(
                        'resumes.view-signed',
                        now()->addMinutes(5),
                        array_filter(['resume' => $this->id, 'version' => $v[1]]),
                    ),
                ])->values(),
            'uploaded_at' => $this->created_at->toIso8601String(),
        ];
    }

    private function profileImageUrl(): ?string
    {
        if (! $this->hasHtmlVersion()) {
            return null;
        }

        $filename = ProfileImageProcessor::filenameFor($this->user_id);
        $path = ProfileImageProcessor::directory().'/'.$filename;

        return is_file($path)
            ? url('/api/assets/images/'.$filename).'?v='.filemtime($path)
            : null;
    }
}
