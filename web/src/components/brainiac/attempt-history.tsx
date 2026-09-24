import type { BrainiacAttemptSummary } from "@/lib/brainiac-types";

function formatDate(iso: string): string {
  return new Date(iso).toLocaleString(undefined, {
    dateStyle: "medium",
    timeStyle: "short",
  });
}

export function AttemptHistory({
  attempts,
  starting,
  onStart,
  onResume,
  onViewResults,
}: {
  attempts: BrainiacAttemptSummary[];
  starting: boolean;
  onStart: () => void;
  onResume: (id: number) => void;
  onViewResults: (id: number) => void;
}) {
  const completed = attempts.filter((a) => a.completed);

  return (
    <div>
      <div className="mb-6 rounded border border-black/10 p-6 dark:border-white/10">
        <h2 className="mb-1 text-lg font-semibold">PI Cognitive Assessment</h2>
        <p className="mb-4 text-sm text-zinc-500">
          50 questions covering numerical, verbal, and abstract reasoning.
          12 minutes on the clock, unanswered questions count as incorrect —
          just like the real thing.
        </p>
        <button
          type="button"
          onClick={onStart}
          disabled={starting}
          className="rounded bg-foreground px-4 py-2 text-sm font-medium text-background disabled:opacity-50"
        >
          {starting ? "Starting..." : "Start assessment"}
        </button>
      </div>

      <h3 className="mb-2 text-sm font-medium">History</h3>
      {attempts.length === 0 ? (
        <p className="text-sm text-zinc-500">
          No attempts yet — start one above.
        </p>
      ) : (
        <ul className="flex flex-col gap-2">
          {attempts.map((attempt) => (
            <li
              key={attempt.id}
              className="flex items-center justify-between rounded border border-black/10 p-3 text-sm dark:border-white/10"
            >
              <div>
                <p className="font-medium">{formatDate(attempt.started_at)}</p>
                {attempt.completed ? (
                  <p className="text-zinc-500">
                    Score: {attempt.score} / {attempt.total_questions} (
                    {attempt.percent_correct}%)
                  </p>
                ) : (
                  <p className="text-amber-600 dark:text-amber-500">
                    In progress
                  </p>
                )}
              </div>
              <button
                type="button"
                onClick={() =>
                  attempt.completed
                    ? onViewResults(attempt.id)
                    : onResume(attempt.id)
                }
                className="rounded border border-black/10 px-3 py-1.5 text-xs font-medium dark:border-white/10"
              >
                {attempt.completed ? "View results" : "Resume"}
              </button>
            </li>
          ))}
        </ul>
      )}

      {completed.length > 0 && (
        <p className="mt-4 text-xs text-zinc-500">
          Best score: {Math.max(...completed.map((a) => a.percent_correct ?? 0))}%
        </p>
      )}
    </div>
  );
}
