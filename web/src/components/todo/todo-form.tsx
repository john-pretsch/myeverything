"use client";

import { useState, type FormEvent } from "react";
import { ApiError } from "@/lib/api";
import type { Recurrence, TodoInput } from "@/lib/todo-types";

function today(): string {
  return new Date().toISOString().slice(0, 10);
}

export function TodoForm({
  initial,
  submitLabel,
  onSubmit,
  onCancel,
}: {
  initial?: TodoInput;
  submitLabel: string;
  onSubmit: (input: TodoInput) => Promise<void>;
  onCancel?: () => void;
}) {
  const [title, setTitle] = useState(initial?.title ?? "");
  const [description, setDescription] = useState(initial?.description ?? "");
  const [recurrence, setRecurrence] = useState<Recurrence>(
    initial?.recurrence ?? "once",
  );
  const [intervalDays, setIntervalDays] = useState(
    initial?.interval_days ? String(initial.interval_days) : "3",
  );
  const [startDate, setStartDate] = useState(initial?.start_date ?? today());
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await onSubmit({
        title,
        description: description || null,
        recurrence,
        interval_days: recurrence === "custom" ? Number(intervalDays) : null,
        start_date: startDate,
      });
    } catch (err) {
      if (err instanceof ApiError) {
        const message =
          typeof err.data === "object" && err.data && "message" in err.data
            ? String((err.data as { message: unknown }).message)
            : "Couldn't save that todo.";
        setError(message);
      } else {
        setError("Couldn't save that todo.");
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="flex flex-col gap-3 rounded border border-black/10 p-4 dark:border-white/10"
    >
      <input
        type="text"
        placeholder="Title"
        required
        value={title}
        onChange={(e) => setTitle(e.target.value)}
        className="rounded border border-black/10 px-2 py-1 text-sm dark:border-white/10"
      />
      <textarea
        placeholder="Description (optional)"
        value={description}
        onChange={(e) => setDescription(e.target.value)}
        rows={2}
        className="rounded border border-black/10 px-2 py-1 text-sm dark:border-white/10"
      />

      <div className="flex flex-wrap items-center gap-3 text-sm">
        <label className="flex items-center gap-1">
          <input
            type="radio"
            name="recurrence"
            checked={recurrence === "once"}
            onChange={() => setRecurrence("once")}
          />
          One-off
        </label>
        <label className="flex items-center gap-1">
          <input
            type="radio"
            name="recurrence"
            checked={recurrence === "daily"}
            onChange={() => setRecurrence("daily")}
          />
          Daily
        </label>
        <label className="flex items-center gap-1">
          <input
            type="radio"
            name="recurrence"
            checked={recurrence === "weekly"}
            onChange={() => setRecurrence("weekly")}
          />
          Weekly
        </label>
        <label className="flex items-center gap-1">
          <input
            type="radio"
            name="recurrence"
            checked={recurrence === "custom"}
            onChange={() => setRecurrence("custom")}
          />
          Every
          <input
            type="number"
            min={2}
            max={365}
            value={intervalDays}
            onChange={(e) => {
              setIntervalDays(e.target.value);
              setRecurrence("custom");
            }}
            className="w-14 rounded border border-black/10 px-1 py-0.5 dark:border-white/10"
          />
          days
        </label>
      </div>

      <label className="flex items-center gap-2 text-sm text-zinc-500">
        {recurrence === "once" ? "Due" : "Starting"}
        <input
          type="date"
          required
          value={startDate}
          onChange={(e) => setStartDate(e.target.value)}
          className="rounded border border-black/10 px-2 py-1 text-foreground dark:border-white/10"
        />
      </label>

      {error && <p className="text-sm text-red-600">{error}</p>}

      <div className="flex gap-2">
        <button
          type="submit"
          disabled={submitting}
          className="self-start rounded bg-foreground px-3 py-1 text-sm font-medium text-background disabled:opacity-50"
        >
          {submitLabel}
        </button>
        {onCancel && (
          <button
            type="button"
            onClick={onCancel}
            className="self-start rounded border border-black/10 px-3 py-1 text-sm dark:border-white/10"
          >
            Cancel
          </button>
        )}
      </div>
    </form>
  );
}
