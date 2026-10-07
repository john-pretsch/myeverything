<?php

namespace App\Http\Controllers;

use App\Http\Resources\ResumeResource;
use App\Models\Resume;
use App\Services\Resumes\ProfileImageProcessor;
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
    public function viewSigned(Request $request, Resume $resume)
    {
        $headers = ['Content-Type' => $resume->mime_type];

        if ($resume->mime_type === 'text/html') {
            // User-supplied HTML served from the API origin: no scripts, no
            // network fetches beyond inline styles and images.
            $headers['Content-Security-Policy'] = "sandbox; default-src 'none'; style-src 'unsafe-inline'; img-src 'self' data: https:";
            $headers['X-Content-Type-Options'] = 'nosniff';
        }

        if ($resume->mime_type === 'text/html') {
            return response(
                $this->rewriteImages(Storage::disk('local')->get($resume->path), $resume->user_id),
                200,
                $headers,
            );
        }

        return Storage::disk('local')->response($resume->path, $resume->original_filename, $headers);
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
     * Point relative <img src> values at images in public/assets/images.
     * `profile.*` maps to the owner's uploaded profile image; any other
     * relative name is used only if a file of that name exists there.
     */
    private function rewriteImages(string $html, int $userId): string
    {
        return preg_replace_callback(
            '/(<img\b[^>]*?\bsrc=)(["\'])([^"\']+)\2/i',
            function (array $m) use ($userId) {
                $src = $m[3];

                if (preg_match('#^([a-z][a-z0-9+.-]*:|//|/|data:)#i', $src)) {
                    return $m[0];
                }

                $name = basename($src);
                $file = preg_match('/^profile\.(png|jpe?g)$/i', $name)
                    ? ProfileImageProcessor::filenameFor($userId)
                    : $name;

                if (! is_file(ProfileImageProcessor::directory().'/'.$file)) {
                    return $m[0];
                }

                return $m[1].$m[2].url('/api/assets/images/'.rawurlencode($file)).$m[2];
            },
            $html,
        ) ?? $html;
    }

    public function destroy(Request $request, Resume $resume)
    {
        $this->authorize('delete', $resume);

        Storage::disk('local')->delete($resume->path);
        $resume->delete();

        return response()->noContent();
    }
}
