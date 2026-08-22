<?php

namespace App\Http\Controllers;

use App\Http\Resources\ResumeResource;
use App\Models\Resume;
use App\Services\Resumes\ResumeTextExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ResumeController extends Controller
{
    public function index(Request $request)
    {
        $resumes = $request->user()->resumes()->latest()->get();

        return ResumeResource::collection($resumes);
    }

    public function store(Request $request, ResumeTextExtractor $extractor)
    {
        $validated = $request->validate([
            // PDF and plain text only, for now. 10MB is an arbitrary but
            // generous cap for a resume file, not something the user specified.
            'file' => ['required', 'file', 'mimes:pdf,txt', 'max:10240'],
        ]);

        $file = $validated['file'];
        $extension = strtolower($file->getClientOriginalExtension());

        $path = $file->storeAs(
            'resumes/'.$request->user()->id,
            Str::uuid().'.'.$extension,
            'local',
        );

        $resume = $request->user()->resumes()->create([
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
            'content' => $extractor->extract($file),
        ]);

        return new ResumeResource($resume);
    }

    public function download(Request $request, Resume $resume)
    {
        $this->authorize('view', $resume);

        return Storage::disk('local')->download($resume->path, $resume->original_filename);
    }

    /**
     * Serve a resume inline for viewing in a new browser tab. Reached via a
     * signed URL (see ResumeResource) rather than auth:sanctum, since a
     * plain top-level navigation to a PDF often carries no Referer/Origin
     * header for Sanctum's stateful-request detection to key off of — the
     * signature itself is the authorization here, not the session cookie.
     */
    public function viewSigned(Request $request, Resume $resume)
    {
        return Storage::disk('local')->response($resume->path, $resume->original_filename, [
            'Content-Type' => $resume->mime_type,
        ]);
    }

    public function destroy(Request $request, Resume $resume)
    {
        $this->authorize('delete', $resume);

        Storage::disk('local')->delete($resume->path);
        $resume->delete();

        return response()->noContent();
    }
}
