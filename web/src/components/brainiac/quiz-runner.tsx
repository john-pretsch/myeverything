"use client";

import { useEffect, useRef, useState } from "react";
import { completeAttempt, submitAnswer } from "@/lib/brainiac";
import type {
  BrainiacAttemptDetail,
  BrainiacAttemptQuestion,
  BrainiacAttemptSummary,
} from "@/lib/brainiac-types";

function formatClock(totalSeconds: number): string {
  const s = Math.max(0, Math.floor(totalSeconds));
  const m = Math.floor(s / 60);
  const rem = s % 60;
  return `${m}:${rem.toString().padStart(2, "0")}`;
}

export function QuizRunner({
  attempt,
  initialQuestions,
  onFinished,
}: {
  attempt: BrainiacAttemptSummary;
  initialQuestions: BrainiacAttemptQuestion[];
  onFinished: (detail: BrainiacAttemptDetail) => void;
}) {
  const [questions, setQuestions] = useState(initialQuestions);
  const [index, setIndex] = useState(0);
  const [remaining, setRemaining] = useState(attempt.time_remaining_seconds);
  const [error, setError] = useState<string | null>(null);
  const [pendingOption, setPendingOption] = useState<number | null>(null);
  const finishingRef = useRef(false);

  const finish = async () => {
    if (finishingRef.current) return;
    finishingRef.current = true;
    try {
      const detail = await completeAttempt(attempt.id);
      onFinished(detail);
    } catch {
      setError("Couldn't submit the assessment. Try again.");
      finishingRef.current = false;
    }
  };

  useEffect(() => {
    const interval = setInterval(() => {
      setRemaining((prev) => {
        if (prev <= 1) {
          clearInterval(interval);
          finish();
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(interval);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const current = questions[index];
  const answeredCount = questions.filter((q) => q.selected_option !== null).length;

  async function handleSelect(optionIndex: number) {
    setError(null);
    setPendingOption(optionIndex);

    const prevQuestions = questions;
    setQuestions((qs) =>
      qs.map((q) =>
        q.id === current.id
          ? { ...q, selected_option: optionIndex, is_correct: null }
          : q,
      ),
    );

    try {
      const updated = await submitAnswer(
        attempt.id,
        current.question_id,
        optionIndex,
      );
      setQuestions((qs) => qs.map((q) => (q.id === current.id ? updated : q)));
    } catch {
      setQuestions(prevQuestions);
      setError("Couldn't save that answer — try again.");
    } finally {
      setPendingOption(null);
    }
  }

  async function handleFinish() {
    if (!confirm("Finish and submit your answers now?")) return;
    await finish();
  }

  if (!current) return null;

  return (
    <div>
      <div className="mb-4 flex items-center justify-between text-sm">
        <span className="text-zinc-500">
          Question {index + 1} of {questions.length} · {answeredCount} answered
        </span>
        <span
          className={`font-mono font-medium ${remaining <= 60 ? "text-red-600 dark:text-red-500" : ""}`}
        >
          {formatClock(remaining)}
        </span>
      </div>

      <div className="mb-4 flex flex-wrap gap-1">
        {questions.map((q, i) => {
          const isCurrent = i === index;
          const isAnswered = q.selected_option !== null;
          return (
            <button
              key={q.id}
              type="button"
              onClick={() => setIndex(i)}
              className={`h-7 w-7 rounded text-xs font-medium ${
                isCurrent
                  ? "bg-foreground text-background"
                  : isAnswered
                    ? "border border-black/10 bg-black/5 dark:border-white/10 dark:bg-white/10"
                    : "border border-black/10 text-zinc-500 dark:border-white/10"
              }`}
            >
              {i + 1}
            </button>
          );
        })}
      </div>

      <div className="mb-4 rounded border border-black/10 p-6 dark:border-white/10">
        <p className="mb-1 text-xs uppercase tracking-wide text-zinc-500">
          {current.category}
        </p>
        <p className="mb-4 text-base">{current.prompt}</p>

        <div className="flex flex-col gap-2">
          {current.options.map((option, optionIndex) => {
            const selected = current.selected_option === optionIndex;
            return (
              <button
                key={optionIndex}
                type="button"
                onClick={() => handleSelect(optionIndex)}
                disabled={pendingOption !== null}
                className={`rounded border px-3 py-2 text-left text-sm disabled:opacity-50 ${
                  selected
                    ? "border-foreground bg-foreground/5"
                    : "border-black/10 dark:border-white/10"
                }`}
              >
                {option}
              </button>
            );
          })}
        </div>
      </div>

      {error && <p className="mb-4 text-sm text-red-600">{error}</p>}

      <div className="flex items-center justify-between">
        <div className="flex gap-2">
          <button
            type="button"
            onClick={() => setIndex((i) => Math.max(0, i - 1))}
            disabled={index === 0}
            className="rounded border border-black/10 px-3 py-1.5 text-sm disabled:opacity-50 dark:border-white/10"
          >
            Previous
          </button>
          <button
            type="button"
            onClick={() => setIndex((i) => Math.min(questions.length - 1, i + 1))}
            disabled={index === questions.length - 1}
            className="rounded border border-black/10 px-3 py-1.5 text-sm disabled:opacity-50 dark:border-white/10"
          >
            Next
          </button>
        </div>
        <button
          type="button"
          onClick={handleFinish}
          className="rounded bg-foreground px-4 py-1.5 text-sm font-medium text-background"
        >
          Finish
        </button>
      </div>
    </div>
  );
}
