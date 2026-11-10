<?php

namespace App\Http\Controllers\News;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsSourceResource;
use App\Models\NewsSource;
use App\Rules\SafeFeedUrl;
use App\Services\News\NewsSourceFetcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SourceController extends Controller
{
    public function index(Request $request)
    {
        $sources = NewsSource::with('topics')->orderBy('name')->get();

        $positions = $request->user()
            ? DB::table('news_source_user')
                ->where('user_id', $request->user()->id)
                ->pluck('position', 'news_source_id')
            : collect();

        $sources->each(function (NewsSource $source) use ($positions) {
            $source->added_position = $positions->has($source->id) ? (int) $positions->get($source->id) : null;
        });

        return NewsSourceResource::collection($sources);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'news_source_id' => ['required_without:feed_url', 'integer', 'exists:news_sources,id'],
            'name' => ['required_with:feed_url', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'feed_url' => ['required_without:news_source_id', 'url', 'max:2048', new SafeFeedUrl],
        ]);

        if (! empty($validated['news_source_id'])) {
            $source = NewsSource::findOrFail($validated['news_source_id']);
        } else {
            $source = NewsSource::firstOrCreate(
                ['feed_url' => $validated['feed_url']],
                [
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'site_url' => $validated['feed_url'],
                ],
            );
            // is_default isn't mass-assignable (kept off the model's fillable
            // list so it can never be set from request input); the DB column
            // default (false) applies on insert, but refresh so the in-memory
            // model reflects it instead of showing null on this response.
            $source->refresh();
        }

        if (! $user->newsSources()->where('news_sources.id', $source->id)->exists()) {
            $nextPosition = (int) ($user->newsSources()->max('position') ?? -1) + 1;
            $user->newsSources()->attach($source->id, ['position' => $nextPosition]);
        }

        $source->added_position = $user->newsSources()
            ->where('news_sources.id', $source->id)
            ->value('position');

        return new NewsSourceResource($source);
    }

    /**
     * Search the public Feedly feed directory (keyless, unofficial) by name
     * or topic, so a feed URL doesn't have to be hunted down by hand.
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        try {
            $response = Http::timeout(8)->acceptJson()->get('https://cloud.feedly.com/v3/search/feeds', [
                'query' => $validated['q'],
                'count' => 15,
            ]);
        } catch (\Throwable) {
            return response()->json(['message' => 'Feed search is unavailable right now.'], 502);
        }

        if (! $response->successful()) {
            return response()->json(['message' => 'Feed search is unavailable right now.'], 502);
        }

        $results = collect($response->json('results', []))
            ->map(fn (array $r) => [
                'title' => (string) ($r['title'] ?? ''),
                'feed_url' => preg_replace('#^feed/#', '', (string) ($r['feedId'] ?? '')),
                'website' => $r['website'] ?? null,
                'description' => isset($r['description']) ? mb_strimwidth($r['description'], 0, 200, '…') : null,
                'subscribers' => (int) ($r['subscribers'] ?? 0),
                'topics' => array_slice($r['topics'] ?? [], 0, 5),
            ])
            ->filter(fn (array $r) => $r['title'] !== '' && filter_var($r['feed_url'], FILTER_VALIDATE_URL))
            ->values();

        return response()->json(['data' => $results]);
    }

    /**
     * Fetch one of the user's sources right now and report which articles
     * were new, so the feed can surface them at the top.
     */
    public function refresh(Request $request, NewsSource $source, NewsSourceFetcher $fetcher)
    {
        abort_unless(
            $request->user()->newsSources()->where('news_sources.id', $source->id)->exists(),
            404,
        );

        // Manual refreshes tag fewer articles than the scheduled run, to keep the request quick.
        $taggingBudget = 10;
        $result = $fetcher->fetch($source, $taggingBudget);

        if ($result['error'] !== null) {
            return response()->json(['message' => "Couldn't refresh {$source->name}: {$result['error']}"], 502);
        }

        return response()->json(['data' => ['new_article_ids' => $result['ids']]]);
    }

    public function destroy(Request $request, NewsSource $source)
    {
        $request->user()->newsSources()->detach($source->id);

        return response()->noContent();
    }

    public function reorder(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'source_ids' => ['required', 'array'],
            'source_ids.*' => ['integer'],
        ]);

        $ownedIds = $user->newsSources()->pluck('news_sources.id');

        foreach (array_values($validated['source_ids']) as $position => $sourceId) {
            if (! $ownedIds->contains($sourceId)) {
                continue;
            }

            DB::table('news_source_user')
                ->where('user_id', $user->id)
                ->where('news_source_id', $sourceId)
                ->update(['position' => $position]);
        }

        return $this->index($request);
    }
}
