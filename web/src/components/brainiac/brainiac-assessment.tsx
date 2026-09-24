"use client";

import { useEffect, useState } from "react";
import { getAttempt, listAttempts, startAttempt } from "@/lib/brainiac";
import type { BrainiacAttemptDetail, BrainiacAttemptSummary } from "@/lib/brainiac-types";
import { AttemptHistory } from "./attempt-history";
import { AttemptResults } from "./attempt-results";
import { QuizRunner } from "./quiz-runner";

type View =
  | { name: "loading" }
  | { name: "history"; attempts: BrainiacAttemptSummary[] }
  | { name: "active"; detail: BrainiacAttemptDetail }
  | { name: "results"; detail: BrainiacAttemptDetail };

export function BrainiacAssessment() {
  const [view, setView] = useState<View>({ name: "loading" });
  const [starting, setStarting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let ignore = false;

    listAttempts()
      .then(async (attempts) => {
        if (ignore) return;

        const inProgress = attempts.find((a) => !a.completed);
        if (inProgress) {
          const detail = await getAttempt(inProgress.id);
          if (!ignore) setView({ name: "active", detail });
          return;
        }

        setView({ name: "history", attempts });
      })
      .catch(() => {
        if (!ignore) setError("Couldn't load your Brainiac history.");
      });

    return () => {
      ignore = true;
    };
  }, []);

  async function refreshHistory() {
    const attempts = await listAttempts();
    setView({ name: "history", attempts });
  }

  async function handleStart() {
    setStarting(true);
    setError(null);
    try {
      const detail = await startAttempt();
      setView({ name: "active", detail });
    } catch {
      setError("Couldn't start a new assessment. Try again.");
    } finally {
      setStarting(false);
    }
  }

  async function handleResume(id: number) {
    setError(null);
    try {
      const detail = await getAttempt(id);
      setView({ name: "active", detail });
    } catch {
      setError("Couldn't resume that attempt.");
    }
  }

  async function handleViewResults(id: number) {
    setError(null);
    try {
      const detail = await getAttempt(id);
      setView({ name: "results", detail });
    } catch {
      setError("Couldn't load those results.");
    }
  }

  if (view.name === "loading") {
    return <p className="text-sm text-zinc-500">Loading...</p>;
  }

  return (
    <div>
      <h1 className="mb-4 text-xl font-semibold">Brainiac</h1>
      {error && <p className="mb-4 text-sm text-red-600">{error}</p>}

      {view.name === "history" && (
        <AttemptHistory
          attempts={view.attempts}
          starting={starting}
          onStart={handleStart}
          onResume={handleResume}
          onViewResults={handleViewResults}
        />
      )}

      {view.name === "active" && (
        <QuizRunner
          attempt={view.detail.attempt}
          initialQuestions={view.detail.questions}
          onFinished={(detail) => setView({ name: "results", detail })}
        />
      )}

      {view.name === "results" && (
        <AttemptResults
          detail={view.detail}
          onBack={() => void refreshHistory()}
          onRetake={handleStart}
        />
      )}
    </div>
  );
}
