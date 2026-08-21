"use client";

import { useState } from "react";
import type { Todo, TodoInput } from "@/lib/todo-types";
import { TodoForm } from "./todo-form";

function describeRecurrence(todo: Todo): string {
  switch (todo.recurrence) {
    case "once":
      return `Once, on ${todo.start_date}`;
    case "daily":
      return "Daily";
    case "weekly":
      return "Weekly";
    case "custom":
      return `Every ${todo.interval_days} days`;
  }
}

export function TodoItem({
  todo,
  showCheckbox,
  onToggle,
  onUpdate,
  onDelete,
  onMove,
  isFirst,
  isLast,
}: {
  todo: Todo;
  showCheckbox: boolean;
  onToggle: (id: number) => void;
  onUpdate: (id: number, input: TodoInput) => Promise<void>;
  onDelete: (id: number) => void;
  onMove?: (id: number, delta: 1 | -1) => void;
  isFirst?: boolean;
  isLast?: boolean;
}) {
  const [editing, setEditing] = useState(false);

  if (editing) {
    return (
      <li>
        <TodoForm
          submitLabel="Save"
          initial={{
            title: todo.title,
            description: todo.description,
            recurrence: todo.recurrence,
            interval_days: todo.interval_days,
            start_date: todo.start_date,
          }}
          onSubmit={async (input) => {
            await onUpdate(todo.id, input);
            setEditing(false);
          }}
          onCancel={() => setEditing(false)}
        />
      </li>
    );
  }

  return (
    <li className="flex items-start justify-between gap-3 rounded border border-black/10 p-3 text-sm dark:border-white/10">
      <div className="flex items-start gap-2">
        {showCheckbox && (
          <input
            type="checkbox"
            checked={todo.completed_today}
            onChange={() => onToggle(todo.id)}
            className="mt-1"
          />
        )}
        <div>
          <p
            className={
              todo.completed_today
                ? "text-zinc-400 line-through"
                : "text-foreground"
            }
          >
            {todo.title}
          </p>
          {todo.description && (
            <p className="text-xs text-zinc-500">{todo.description}</p>
          )}
          <p className="text-xs text-zinc-400">{describeRecurrence(todo)}</p>
        </div>
      </div>
      <div className="flex items-center gap-1 text-xs text-zinc-500">
        {onMove && (
          <>
            <button
              type="button"
              aria-label="Move up"
              disabled={isFirst}
              onClick={() => onMove(todo.id, -1)}
              className="hover:text-foreground disabled:opacity-30"
            >
              ↑
            </button>
            <button
              type="button"
              aria-label="Move down"
              disabled={isLast}
              onClick={() => onMove(todo.id, 1)}
              className="hover:text-foreground disabled:opacity-30"
            >
              ↓
            </button>
          </>
        )}
        <button
          type="button"
          onClick={() => setEditing(true)}
          className="hover:text-foreground"
        >
          Edit
        </button>
        <button
          type="button"
          onClick={() => onDelete(todo.id)}
          className="hover:text-red-600"
        >
          Delete
        </button>
      </div>
    </li>
  );
}
