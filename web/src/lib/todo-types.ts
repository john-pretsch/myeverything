export type Recurrence = "once" | "daily" | "weekly" | "custom";

export type Todo = {
  id: number;
  title: string;
  description: string | null;
  recurrence: Recurrence;
  interval_days: number | null;
  start_date: string;
  position: number;
  due_today: boolean;
  completed_today: boolean;
};

export type TodoInput = {
  title: string;
  description?: string | null;
  recurrence: Recurrence;
  interval_days?: number | null;
  start_date: string;
};
