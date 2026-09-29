<?php

namespace App\Http\Controllers\News;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\NewsArticle;
use App\Models\Tag;
use App\Models\TagVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $tags = Tag::orderBy('name')->get();

        $counts = DB::table('news_article_tag')
            ->select('tag_id', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('tag_id')
            ->pluck('usage_count', 'tag_id');

        $tags->each(function (Tag $tag) use ($counts) {
            $tag->usage_count = (int) ($counts->get($tag->id) ?? 0);
        });

        return TagResource::collection($tags);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
        ]);

        $tag = Tag::firstOrCreate(['name' => trim($validated['name'])]);

        return new TagResource($tag);
    }

    /**
     * Tags are a shared/global resource with no ownership concept, so any
     * authenticated user may delete any tag entirely — this removes it
     * from every article, for every user. That's intentional, not a
     * missing authorization check.
     */
    public function destroy(Request $request, Tag $tag)
    {
        $tag->delete();

        return response()->noContent();
    }

    public function attach(Request $request, NewsArticle $article)
    {
        $validated = $request->validate([
            'tag_id' => ['required_without:name', 'integer', 'exists:tags,id'],
            'name' => ['required_without:tag_id', 'string', 'max:50'],
        ]);

        if (! empty($validated['tag_id'])) {
            $tag = Tag::findOrFail($validated['tag_id']);
        } else {
            $tag = Tag::firstOrCreate(['name' => trim($validated['name'])]);
        }

        $article->tags()->syncWithoutDetaching([
            $tag->id => ['applied_by_user_id' => $request->user()->id],
        ]);

        return new TagResource($tag);
    }

    public function detach(Request $request, NewsArticle $article, Tag $tag)
    {
        $article->tags()->detach($tag->id);

        return response()->noContent();
    }

    public function vote(Request $request, Tag $tag)
    {
        $validated = $request->validate([
            'direction' => ['required', 'integer', 'in:1,-1'],
        ]);

        TagVote::updateOrCreate(
            ['user_id' => $request->user()->id, 'tag_id' => $tag->id],
            ['direction' => $validated['direction']],
        );

        return response()->json(['direction' => $validated['direction']]);
    }

    public function clearVote(Request $request, Tag $tag)
    {
        TagVote::where('user_id', $request->user()->id)
            ->where('tag_id', $tag->id)
            ->delete();

        return response()->noContent();
    }
}
