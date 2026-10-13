<?php

namespace App\Http\Controllers;

use App\Http\Resources\InterviewQuestionResource;
use App\Models\InterviewCategory;
use App\Models\InterviewQuestion;
use Illuminate\Http\Request;

class InterviewController extends Controller
{
    /**
     * Category tree (top-level categories with their children), each with
     * the current user's question count. A parent's count includes its
     * children's questions.
     */
    public function categories(Request $request)
    {
        $counts = $request->user()->interviewQuestions()
            ->selectRaw('interview_category_id, count(*) as total')
            ->groupBy('interview_category_id')
            ->pluck('total', 'interview_category_id');

        $tree = InterviewCategory::whereNull('parent_id')
            ->with('children')
            ->orderBy('position')
            ->get()
            ->map(function (InterviewCategory $category) use ($counts) {
                $children = $category->children->map(fn (InterviewCategory $child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'question_count' => (int) ($counts[$child->id] ?? 0),
                ])->values();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'question_count' => (int) ($counts[$category->id] ?? 0) + $children->sum('question_count'),
                    'children' => $children,
                ];
            });

        return response()->json(['data' => $tree]);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'exists:interview_categories,slug'],
        ]);

        $query = $request->user()->interviewQuestions()->orderBy('position')->orderBy('id');

        if (! empty($validated['category'])) {
            $category = InterviewCategory::where('slug', $validated['category'])->first();
            $query->whereIn('interview_category_id', $category->children()->pluck('id')->push($category->id));
        }

        return InterviewQuestionResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        $nextPosition = (int) ($request->user()->interviewQuestions()
            ->where('interview_category_id', $validated['interview_category_id'])
            ->max('position') ?? -1) + 1;

        $question = $request->user()->interviewQuestions()->create([
            ...$validated,
            'position' => $nextPosition,
        ]);

        return new InterviewQuestionResource($question);
    }

    public function update(Request $request, InterviewQuestion $question)
    {
        $this->authorize('update', $question);

        $question->update($this->validated($request));

        return new InterviewQuestionResource($question);
    }

    public function destroy(Request $request, InterviewQuestion $question)
    {
        $this->authorize('delete', $question);

        $question->delete();

        return response()->noContent();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'interview_category_id' => ['required', 'integer', 'exists:interview_categories,id'],
            'question' => ['required', 'string', 'max:2000'],
            'answer' => ['nullable', 'string', 'max:20000'],
        ]);
    }
}
