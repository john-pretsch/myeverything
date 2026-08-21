<?php

namespace App\Http\Controllers;

use App\Http\Resources\TodoResource;
use App\Models\Todo;
use App\Models\TodoCompletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TodoController extends Controller
{
    public function index(Request $request)
    {
        $todos = $request->user()->todos()->get();
        $today = now()->startOfDay();

        $completedTodayIds = TodoCompletion::whereIn('todo_id', $todos->pluck('id'))
            ->whereDate('completed_on', $today->toDateString())
            ->pluck('todo_id');

        $todos->each(function (Todo $todo) use ($today, $completedTodayIds) {
            $todo->due_today = $todo->isDueOn($today);
            $todo->completed_today = $completedTodayIds->contains($todo->id);
        });

        return TodoResource::collection($todos);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        $nextPosition = (int) ($request->user()->todos()->max('position') ?? -1) + 1;

        $todo = $request->user()->todos()->create([
            ...$validated,
            'position' => $nextPosition,
        ]);

        $todo->due_today = $todo->isDueOn(now()->startOfDay());
        $todo->completed_today = false;

        return new TodoResource($todo);
    }

    public function update(Request $request, Todo $todo)
    {
        $this->authorize('update', $todo);

        $validated = $this->validated($request);
        $todo->update($validated);

        $today = now()->startOfDay();
        $todo->due_today = $todo->isDueOn($today);
        $todo->completed_today = $todo->completions()
            ->whereDate('completed_on', $today->toDateString())
            ->exists();

        return new TodoResource($todo);
    }

    public function destroy(Request $request, Todo $todo)
    {
        $this->authorize('delete', $todo);

        $todo->delete();

        return response()->noContent();
    }

    public function reorder(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'todo_ids' => ['required', 'array'],
            'todo_ids.*' => ['integer'],
        ]);

        $ownedIds = $user->todos()->pluck('id');

        foreach (array_values($validated['todo_ids']) as $position => $todoId) {
            if (! $ownedIds->contains($todoId)) {
                continue;
            }

            DB::table('todos')
                ->where('id', $todoId)
                ->where('user_id', $user->id)
                ->update(['position' => $position]);
        }

        return $this->index($request);
    }

    public function toggle(Request $request, Todo $todo)
    {
        $this->authorize('update', $todo);

        $today = now()->toDateString();

        $completion = $todo->completions()->whereDate('completed_on', $today)->first();

        if ($completion) {
            $completion->delete();
            $completedToday = false;
        } else {
            $todo->completions()->create(['completed_on' => $today]);
            $completedToday = true;
        }

        $todo->due_today = $todo->isDueOn(now()->startOfDay());
        $todo->completed_today = $completedToday;

        return new TodoResource($todo);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'recurrence' => ['required', Rule::in(['once', 'daily', 'weekly', 'custom'])],
            'interval_days' => ['required_if:recurrence,custom', 'nullable', 'integer', 'min:2', 'max:365'],
            'start_date' => ['required', 'date'],
        ]);
    }
}
