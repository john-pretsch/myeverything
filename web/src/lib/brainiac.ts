import { apiFetch } from "./api";
import type {
  BrainiacAttemptDetail,
  BrainiacAttemptQuestion,
  BrainiacAttemptSummary,
} from "./brainiac-types";

export async function listAttempts(): Promise<BrainiacAttemptSummary[]> {
  const res = await apiFetch<{ data: BrainiacAttemptSummary[] }>(
    "/api/brainiac/attempts",
  );
  return res.data;
}

export function startAttempt(
  subsection = "pi_cognitive",
): Promise<BrainiacAttemptDetail> {
  return apiFetch<BrainiacAttemptDetail>("/api/brainiac/attempts", {
    method: "POST",
    body: { subsection },
  });
}

export function getAttempt(id: number): Promise<BrainiacAttemptDetail> {
  return apiFetch<BrainiacAttemptDetail>(`/api/brainiac/attempts/${id}`);
}

export async function submitAnswer(
  attemptId: number,
  questionId: number,
  selectedOption: number,
): Promise<BrainiacAttemptQuestion> {
  const res = await apiFetch<{ data: BrainiacAttemptQuestion }>(
    `/api/brainiac/attempts/${attemptId}/answers`,
    {
      method: "POST",
      body: { question_id: questionId, selected_option: selectedOption },
    },
  );
  return res.data;
}

export function completeAttempt(id: number): Promise<BrainiacAttemptDetail> {
  return apiFetch<BrainiacAttemptDetail>(
    `/api/brainiac/attempts/${id}/complete`,
    { method: "POST" },
  );
}
