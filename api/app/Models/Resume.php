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
])]
class Resume extends Model
{
    /** @use HasFactory<\Database\Factories\ResumeFactory> */
    use HasFactory;

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

    public function sourceResume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'source_resume_id');
    }
}
