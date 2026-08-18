<?php

namespace App\Http\Controllers\News;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use App\Models\NewsArticleFeedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request, NewsArticle $article)
    {
        $validated = $request->validate([
            'direction' => ['required', 'integer', 'in:1,-1'],
        ]);

        NewsArticleFeedback::updateOrCreate(
            ['user_id' => $request->user()->id, 'news_article_id' => $article->id],
            ['news_source_id' => $article->news_source_id, 'direction' => $validated['direction']],
        );

        return response()->json(['direction' => $validated['direction']]);
    }

    public function destroy(Request $request, NewsArticle $article)
    {
        NewsArticleFeedback::where('user_id', $request->user()->id)
            ->where('news_article_id', $article->id)
            ->delete();

        return response()->noContent();
    }
}
