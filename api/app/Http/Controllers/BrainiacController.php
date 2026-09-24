<?php

namespace App\Http\Controllers;

use App\Http\Resources\BrainiacAttemptQuestionResource;
use App\Http\Resources\BrainiacAttemptResource;
use App\Models\BrainiacAttempt;
use App\Models\BrainiacQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrainiacController extends Controller
{
    private const QUESTIONS_PER_ATTEMPT = 50;

    private const TIME_LIMIT_SECONDS = 720; // 12 minutes, matching a PI-style timed assessment

    public function index(Request $request)
    {
        $attempts = $request->user()->brainiacAttempts()->orderByDesc('started_at')->get();

        return BrainiacAttemptResource::collection($attempts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subsection' => ['sometimes', 'string', 'max:100'],
        ]);
        $subsection = $validated['subsection'] ?? 'pi_cognitive';

        $questionIds = BrainiacQuestion::where('subsection', $subsection)
            ->inRandomOrder()
            ->limit(self::QUESTIONS_PER_ATTEMPT)
            ->pluck('id');

        abort_if($questionIds->isEmpty(), 422, 'No questions available for this subsection.');

        $attempt = DB::transaction(function () use ($request, $subsection, $questionIds) {
            $attempt = $request->user()->brainiacAttempts()->create([
                'subsection' => $subsection,
                'total_questions' => $questionIds->count(),
                'time_limit_seconds' => self::TIME_LIMIT_SECONDS,
                'started_at' => now(),
            ]);

            foreach ($questionIds->values() as $position => $questionId) {
                $attempt->attemptQuestions()->create([
                    'question_id' => $questionId,
                    'position' => $position,
                ]);
            }

            return $attempt;
        });

        return $this->attemptResponse($attempt);
    }

    public function show(Request $request, BrainiacAttempt $attempt)
    {
        $this->authorize('view', $attempt);

        if ($attempt->isExpired()) {
            $attempt->finalize();
        }

        return $this->attemptResponse($attempt);
    }

    public function answer(Request $request, BrainiacAttempt $attempt)
    {
        $this->authorize('update', $attempt);

        if ($attempt->isExpired()) {
            $attempt->finalize();
        }

        if ($attempt->completed_at !== null) {
            return response()->json(['message' => 'This attempt is already complete.'], 422);
        }

        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
            'selected_option' => ['required', 'integer', 'min:0'],
        ]);

        $attemptQuestion = $attempt->attemptQuestions()
            ->where('question_id', $validated['question_id'])
            ->firstOrFail();

        $question = $attemptQuestion->question;

        abort_if($validated['selected_option'] >= count($question->options), 422, 'Invalid option.');

        $attemptQuestion->update([
            'selected_option' => $validated['selected_option'],
            'is_correct' => $validated['selected_option'] === $question->correct_option,
            'answered_at' => now(),
        ]);

        $attemptQuestion->setRelation('attempt', $attempt);

        return new BrainiacAttemptQuestionResource($attemptQuestion);
    }

    public function complete(Request $request, BrainiacAttempt $attempt)
    {
        $this->authorize('update', $attempt);

        $attempt->finalize();

        return $this->attemptResponse($attempt);
    }

    private function attemptResponse(BrainiacAttempt $attempt): array
    {
        $questions = $attempt->attemptQuestions()->with('question')->orderBy('position')->get();
        $questions->each(fn ($attemptQuestion) => $attemptQuestion->setRelation('attempt', $attempt));

        return [
            'attempt' => new BrainiacAttemptResource($attempt),
            'questions' => BrainiacAttemptQuestionResource::collection($questions),
        ];
    }
}
