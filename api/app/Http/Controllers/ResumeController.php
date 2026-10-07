<?php

namespace App\Http\Controllers;

use App\Http\Resources\ResumeResource;
use App\Models\Resume;
use App\Services\Resumes\ProfileImageProcessor;
use App\Services\Resumes\ResumeImageRewriter;
use App\Services\Resumes\ResumePdfGenerator;
use App\Services\Resumes\ResumeTextExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            // PDF, plain text, and HTML only, for now. 10MB is an arbitrary but
            // generous cap for a resume file, not something the user specified.
            'file' => ['required', 'file', 'mimes:pdf,txt,html,htm', 'max:10240'],
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

    public function storeProfileImage(Request $request, ProfileImageProcessor $processor)
    {
        $validated = $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
        ]);

        try {
            $processor->store($validated['image'], $request->user()->id);
        } catch (\RuntimeException) {
            return response()->json(['message' => 'Could not read that image.'], 422);
        }

        return response()->json(['message' => 'Profile image saved.']);
    }

    public function image(string $filename)
    {
        $path = ProfileImageProcessor::directory().'/'.basename($filename);

        abort_unless(is_file($path), 404);

        return response()->file($path);
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
    public function viewSigned(Request $request, Resume $resume, ResumeImageRewriter $images)
    {
        $alt = $request->query('version') === 'alt' && $resume->alt_path !== null;
        $path = $alt ? $resume->alt_path : $resume->path;
        $mime = $alt ? $resume->alt_mime_type : $resume->mime_type;
        $filename = $alt
            ? pathinfo($resume->original_filename, PATHINFO_FILENAME).'.'.($mime === 'text/html' ? 'html' : 'pdf')
            : $resume->original_filename;

        $headers = ['Content-Type' => $mime];

        if ($mime === 'text/html') {
            // User-supplied HTML served from the API origin: no scripts, no
            // network fetches beyond inline styles and images.
            $headers['Content-Security-Policy'] = "sandbox; default-src 'none'; style-src 'unsafe-inline'; img-src 'self' data: https:";
            $headers['X-Content-Type-Options'] = 'nosniff';

            return response(
                $images->rewrite(Storage::disk('local')->get($path), $resume->user_id),
                200,
                $headers,
            );
        }

        return Storage::disk('local')->response($path, $filename, $headers);
    }

    public function makePrimary(Request $request, Resume $resume)
    {
        $this->authorize('update', $resume);

        DB::transaction(function () use ($request, $resume) {
            $request->user()->resumes()->where('is_primary', true)->update(['is_primary' => false]);
            $resume->update(['is_primary' => true]);
        });

        return new ResumeResource($resume->refresh());
    }

    /**
     * Add the missing PDF or HTML version to a resume (stored on the same row): HTML → PDF
     * renders the stored HTML; PDF/text → HTML renders the extracted text.
     */
    public function convert(
        Request $request,
        Resume $resume,
        ResumePdfGenerator $generator,
        ResumeImageRewriter $images,
    ) {
        $this->authorize('update', $resume);

        $target = $resume->convertibleTo();

        if ($target === null) {
            return response()->json(['message' => 'This resume already has another version.'], 422);
        }

        $userId = $request->user()->id;

        if ($target === 'pdf') {
            $html = preg_replace('/<img\b[^>]*>/i', '', Storage::disk('local')->get($resume->path));
            $contents = $generator->renderHtml($html);
            $mime = 'application/pdf';
        } else {
            if (! $resume->content) {
                return response()->json(['message' => 'No text available to build an HTML version from.'], 422);
            }

            $hasImage = is_file(ProfileImageProcessor::directory().'/'.ProfileImageProcessor::filenameFor($userId));
            $contents = $generator->toHtml($resume->content, $hasImage ? 'profile.png' : null);
            $mime = 'text/html';
        }

        $path = 'resumes/'.$userId.'/'.Str::uuid().'.'.$target;
        Storage::disk('local')->put($path, $contents);

        $resume->update([
            'alt_path' => $path,
            'alt_mime_type' => $mime,
            'alt_size' => strlen($contents),
        ]);

        return new ResumeResource($resume);
    }

    public function destroy(Request $request, Resume $resume)
    {
        $this->authorize('delete', $resume);

        Storage::disk('local')->delete(array_filter([$resume->path, $resume->alt_path]));
        $resume->delete();

        return response()->noContent();
    }
}
