<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'original_filename',
    'mime_type',
    'size',
    'path',
    'content',
    'organization_id',
    'gig_lead_id',
    'source_resume_id',
    'alt_path',
    'alt_mime_type',
    'alt_size',
    'is_primary',
])]
class Resume extends Model
{
    /** @use HasFactory<\Database\Factories\ResumeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function gigLead(): BelongsTo
    {
        return $this->belongsTo(GigLead::class);
    }

    /**
     * The format ('pdf' or 'html') this resume can still be converted to, or
     * null if it already has a counterpart or its type isn't convertible.
     */
    public function convertibleTo(): ?string
    {
        if ($this->alt_path !== null) {
            return null;
        }

        return match ($this->mime_type) {
            'text/html' => 'pdf',
            'application/pdf', 'text/plain' => 'html',
            default => null,
        };
    }

    public function hasHtmlVersion(): bool
    {
        return $this->mime_type === 'text/html' || $this->alt_mime_type === 'text/html';
    }

    public function sourceResume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'source_resume_id');
    }
}
