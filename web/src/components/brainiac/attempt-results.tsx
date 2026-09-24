import type { BrainiacAttemptDetail, BrainiacCategory } from "@/lib/brainiac-types";

const CATEGORY_LABELS: Record<BrainiacCategory, string> = {
  numerical: "Numerical",
  verbal: "Verbal",
  abstract: "Abstract",
};

export function AttemptResults({
  detail,
  onBack,
  onRetake,
}: {
  detail: BrainiacAttemptDetail;
  onBack: () => void;
  onRetake: () => void;
}) {
  const { attempt, questions } = detail;

  const byCategory = (Object.keys(CATEGORY_LABELS) as BrainiacCategory[]).map(
    (category) => {
      const inCategory = questions.filter((q) => q.category === category);
      const correct = inCategory.filter((q) => q.is_correct).length;
      return { category, correct, total: inCategory.length };
    },
  );

  return (
    <div>
      <div className="mb-6 rounded border border-black/10 p-6 dark:border-white/10">
        <h2 className="mb-1 text-lg font-semibold">Results</h2>
        <p className="mb-4 text-3xl font-semibold">
          {attempt.score} / {attempt.total_questions}
          <span className="ml-2 text-base font-normal text-zinc-500">
            ({attempt.percent_correct}%)
          </span>
        </p>
        <div className="flex gap-6 text-sm text-zinc-500">
          {byCategory.map(({ category, correct, total }) => (
            <span key={category}>
              {CATEGORY_LABELS[category]}: {correct}/{total}
            </span>
          ))}
        </div>
      </div>

      <div className="mb-6 flex gap-2">
        <button
          type="button"
          onClick={onRetake}
          className="rounded bg-foreground px-4 py-2 text-sm font-medium text-background"
        >
          Start another attempt
        </button>
        <button
          type="button"
          onClick={onBack}
          className="rounded border border-black/10 px-4 py-2 text-sm font-medium dark:border-white/10"
        >
          Back to history
        </button>
      </div>

      <h3 className="mb-2 text-sm font-medium">Review</h3>
      <ul className="flex flex-col gap-3">
        {questions.map((q, i) => (
          <li
            key={q.id}
            className="rounded border border-black/10 p-4 text-sm dark:border-white/10"
          >
            <p className="mb-1 text-xs uppercase tracking-wide text-zinc-500">
              {i + 1}. {CATEGORY_LABELS[q.category]}
            </p>
            <p className="mb-2">{q.prompt}</p>
            <div className="flex flex-col gap-1">
              {q.options.map((option, optionIndex) => {
                const isCorrect = optionIndex === q.correct_option;
                const isSelected = optionIndex === q.selected_option;
                return (
                  <p
                    key={optionIndex}
                    className={
                      isCorrect
                        ? "font-medium text-green-600 dark:text-green-500"
                        : isSelected
                          ? "font-medium text-red-600 dark:text-red-500"
                          : "text-zinc-500"
                    }
                  >
                    {isCorrect ? "✓" : isSelected ? "✗" : "·"} {option}
                  </p>
                );
              })}
            </div>
            {q.selected_option === null && (
              <p className="mt-1 text-xs text-zinc-500">Not answered</p>
            )}
            {q.explanation && (
              <p className="mt-2 text-xs text-zinc-500">{q.explanation}</p>
            )}
          </li>
        ))}
      </ul>
    </div>
  );
}
