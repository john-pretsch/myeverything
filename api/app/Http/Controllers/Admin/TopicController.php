<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsSource;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TopicController extends Controller
{
    public function index()
    {
        $topics = Topic::with('sources:id,name')->withCount('sources')->orderBy('name')->get();

        return $topics->map(fn (Topic $topic) => [
            'id' => $topic->id,
            'name' => $topic->name,
            'source_count' => $topic->sources_count,
            'sources' => $topic->sources->map(fn (NewsSource $source) => [
                'id' => $source->id,
                'name' => $source->name,
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:topics,name'],
        ]);

        $topic = Topic::create(['name' => trim($validated['name'])]);

        return response()->json(['id' => $topic->id, 'name' => $topic->name]);
    }

    public function update(Request $request, Topic $topic)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('topics', 'name')->ignore($topic->id)],
        ]);

        $topic->update(['name' => trim($validated['name'])]);

        return response()->json(['id' => $topic->id, 'name' => $topic->name]);
    }

    public function destroy(Topic $topic)
    {
        $topic->delete();

        return response()->noContent();
    }

    public function attachSource(Request $request, Topic $topic)
    {
        $validated = $request->validate([
            'news_source_id' => ['required', 'integer', 'exists:news_sources,id'],
        ]);

        $topic->sources()->syncWithoutDetaching([$validated['news_source_id']]);

        return response()->noContent();
    }

    public function detachSource(Topic $topic, NewsSource $source)
    {
        $topic->sources()->detach($source->id);

        return response()->noContent();
    }
}
