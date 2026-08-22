<?php

namespace App\Http\Controllers;

use App\Http\Resources\ResumeResource;
use App\Models\GigLead;
use App\Services\Resumes\ResumePdfGenerator;
use App\Services\Resumes\ResumeTailoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ResumeTailoringController extends Controller
{
    /**
     * Ask ChatGPT to tailor a resume to this lead's job description.
     * Nothing is persisted here — the result is a preview only.
     */
    public function preview(Request $request, GigLead $gigLead, ResumeTailoringService $tailor)
    {
        $this->authorize('update', $gigLead);

        $validated = $request->validate([
            'resume_id' => ['required', 'integer'],
        ]);

        $resume = $request->user()->resumes()->find($validated['resume_id']);

        if (! $resume) {
            return response()->json(['message' => 'Resume not found.'], 404);
        }

        if (! $gigLead->description) {
            return response()->json(['message' => 'This lead has no job description yet.'], 422);
        }

        if (! $resume->content) {
            return response()->json(['message' => 'This resume has no parsed text to work from.'], 422);
        }

        try {
            $content = $tailor->tailor(
                $resume->content,
                $gigLead->title ?? '',
                $gigLead->company ?? '',
                $gigLead->description,
            );
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['content' => $content]);
    }

    /**
     * Save a previewed, user-accepted tailored resume: store the plain text
     * and a generated PDF as a new resumes row, linked to the source resume
     * and the lead (and lead's organization) it was tailored for.
     */
    public function accept(Request $request, GigLead $gigLead, ResumePdfGenerator $pdfGenerator)
    {
        $this->authorize('update', $gigLead);

        $validated = $request->validate([
            'resume_id' => ['required', 'integer'],
            'content' => ['required', 'string'],
        ]);

        $sourceResume = $request->user()->resumes()->find($validated['resume_id']);

        if (! $sourceResume) {
            return response()->json(['message' => 'Resume not found.'], 404);
        }

        $organization = null;
        if ($gigLead->company) {
            $organization = $request->user()->organizations()->firstOrCreate([
                'name' => $gigLead->company,
            ]);

            if (! $gigLead->organization_id) {
                $gigLead->update(['organization_id' => $organization->id]);
            }
        }

        $pdf = $pdfGenerator->generate($validated['content']);

        $baseName = pathinfo($sourceResume->original_filename, PATHINFO_FILENAME);
        $filename = $baseName.' - tailored'.($gigLead->company ? ' for '.$gigLead->company : '').'.pdf';

        $path = 'resumes/'.$request->user()->id.'/'.Str::uuid().'.pdf';
        Storage::disk('local')->put($path, $pdf);

        $resume = $request->user()->resumes()->create([
            'original_filename' => $filename,
            'mime_type' => 'application/pdf',
            'size' => strlen($pdf),
            'path' => $path,
            'content' => $validated['content'],
            'organization_id' => $organization?->id,
            'gig_lead_id' => $gigLead->id,
            'source_resume_id' => $sourceResume->id,
        ]);

        return new ResumeResource($resume);
    }
}
