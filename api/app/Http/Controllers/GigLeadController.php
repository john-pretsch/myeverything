<?php

namespace App\Http\Controllers;

use App\Http\Resources\GigLeadResource;
use App\Models\GigLead;
use App\Services\GigLeads\JobPostingFetcher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class GigLeadController extends Controller
{
    public function index(Request $request)
    {
        $leads = $request->user()->gigLeads()->latest()->get();

        return GigLeadResource::collection($leads);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => [
                'required',
                'url:http,https',
                'max:2048',
                Rule::unique('gig_leads')->where('user_id', $request->user()->id),
            ],
            'country' => ['required', Rule::in(['usa', 'canada'])],
            'job_type' => ['required', Rule::in([
                'full_time',
                'part_time',
                'short_term_contract',
                'long_term_contract',
            ])],
            'origin' => ['required', Rule::in(['linkedin', 'arc', 'indeed', 'gunio', 'other'])],
        ]);

        $lead = $request->user()->gigLeads()->create([
            ...$validated,
            'status' => 'new',
        ]);

        return new GigLeadResource($lead);
    }

    public function update(Request $request, GigLead $gigLead)
    {
        $this->authorize('update', $gigLead);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['new', 'reviewed', 'dismissed'])],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        $gigLead->update($validated);

        return new GigLeadResource($gigLead);
    }

    public function fetchDetails(Request $request, GigLead $gigLead, JobPostingFetcher $fetcher)
    {
        $this->authorize('update', $gigLead);

        if ($gigLead->description !== null) {
            return new GigLeadResource($gigLead);
        }

        try {
            $gigLead->update($fetcher->fetch($gigLead->url));
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return new GigLeadResource($gigLead);
    }

    public function destroy(Request $request, GigLead $gigLead)
    {
        $this->authorize('delete', $gigLead);

        $gigLead->delete();

        return response()->noContent();
    }
}
