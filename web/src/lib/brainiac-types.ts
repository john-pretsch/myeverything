export type BrainiacCategory = "numerical" | "verbal" | "abstract";

export type BrainiacAttemptSummary = {
  id: number;
  subsection: string;
  total_questions: number;
  time_limit_seconds: number;
  started_at: string;
  completed_at: string | null;
  completed: boolean;
  score: number | null;
  percent_correct: number | null;
  time_remaining_seconds: number;
};

export type BrainiacAttemptQuestion = {
  id: number;
  question_id: number;
  position: number;
  category: BrainiacCategory;
  prompt: string;
  options: string[];
  selected_option: number | null;
  answered_at: string | null;
  is_correct: boolean | null;
  correct_option?: number;
  explanation?: string | null;
};

export type BrainiacAttemptDetail = {
  attempt: BrainiacAttemptSummary;
  questions: BrainiacAttemptQuestion[];
};
