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
    /**
     * Lists only the current user's own applied tags — tags are a shared
     * name vocabulary (dedup by name), but which tags show up, and to
     * whom, is per-user: each user only sees tags they personally applied.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return TagResource::collection(collect());
        }

        $counts = DB::table('news_article_tag')
            ->where('applied_by_user_id', $user->id)
            ->select('tag_id', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('tag_id')
            ->pluck('usage_count', 'tag_id');

        $tags = Tag::whereIn('id', $counts->keys())->orderBy('name')->get();

        $tags->each(function (Tag $tag) use ($counts) {
            $tag->usage_count = (int) $counts->get($tag->id);
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
     * Removes the current user's own applications of this tag from every
     * article they applied it to. The Tag row (the shared name) itself is
     * left alone — other users may still be using it.
     */
    public function destroy(Request $request, Tag $tag)
    {
        DB::table('news_article_tag')
            ->where('tag_id', $tag->id)
            ->where('applied_by_user_id', $request->user()->id)
            ->delete();

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

        // Eloquent's sync/syncWithoutDetaching keys only on the two
        // relationship foreign keys (news_article_id, tag_id) — it doesn't
        // know applied_by_user_id is also part of this pivot's uniqueness,
        // so it would overwrite another user's row instead of adding a
        // second one. Upsert directly against the per-user unique index
        // instead — checking existence rather than relying on an update's
        // affected-row count, since MySQL reports 0 affected rows for an
        // update that matches a row but changes no column values.
        $pivotQuery = fn () => DB::table('news_article_tag')
            ->where('news_article_id', $article->id)
            ->where('tag_id', $tag->id)
            ->where('applied_by_user_id', $request->user()->id);

        if ($pivotQuery()->exists()) {
            $pivotQuery()->update(['updated_at' => now()]);
        } else {
            DB::table('news_article_tag')->insert([
                'news_article_id' => $article->id,
                'tag_id' => $tag->id,
                'applied_by_user_id' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return new TagResource($tag);
    }

    /**
     * Only removes the current user's own application of this tag from
     * this article — a no-op if someone else applied it instead.
     */
    public function detach(Request $request, NewsArticle $article, Tag $tag)
    {
        DB::table('news_article_tag')
            ->where('news_article_id', $article->id)
            ->where('tag_id', $tag->id)
            ->where('applied_by_user_id', $request->user()->id)
            ->delete();

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
