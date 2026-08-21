"use client";

import { useEffect, useState } from "react";
import {
  createTodo,
  deleteTodo,
  getTodos,
  reorderTodos,
  toggleTodo,
  updateTodo,
} from "@/lib/todo";
import type { Todo, TodoInput } from "@/lib/todo-types";
import { TodoForm } from "./todo-form";
import { TodoItem } from "./todo-item";

export function TodoList() {
  const [todos, setTodos] = useState<Todo[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [requestId, setRequestId] = useState(0);
  const [resolvedId, setResolvedId] = useState(-1);
  const loading = requestId !== resolvedId;

  useEffect(() => {
    let ignore = false;

    getTodos()
      .then((data) => {
        if (ignore) return;
        setTodos(data);
        setError(null);
      })
      .catch(() => {
        if (!ignore) setError("Couldn't load your todos.");
      })
      .finally(() => {
        if (!ignore) setResolvedId(requestId);
      });

    return () => {
      ignore = true;
    };
  }, [requestId]);

  function reload() {
    setRequestId((n) => n + 1);
  }

  async function handleCreate(input: TodoInput) {
    await createTodo(input);
    setShowForm(false);
    reload();
  }

  async function handleUpdate(id: number, input: TodoInput) {
    await updateTodo(id, input);
    reload();
  }

  async function handleDelete(id: number) {
    await deleteTodo(id);
    reload();
  }

  async function handleToggle(id: number) {
    await toggleTodo(id);
    reload();
  }

  const dueToday = todos.filter((t) => t.due_today);
  const notDueToday = todos.filter((t) => !t.due_today);

  async function handleMove(id: number, delta: 1 | -1) {
    const index = notDueToday.findIndex((t) => t.id === id);
    const target = index + delta;
    if (index === -1 || target < 0 || target >= notDueToday.length) return;

    const reordered = [...notDueToday];
    [reordered[index], reordered[target]] = [
      reordered[target],
      reordered[index],
    ];

    const fullOrder = [...dueToday, ...reordered];
    setTodos(fullOrder);
    await reorderTodos(fullOrder.map((t) => t.id));
  }

  return (
    <div>
      <div className="mb-4 flex items-center justify-between">
        <h1 className="text-xl font-semibold">Todo</h1>
        <button
          type="button"
          onClick={() => setShowForm((v) => !v)}
          className="rounded border border-black/10 px-2 py-1 text-xs font-medium dark:border-white/10"
        >
          {showForm ? "Close" : "Add todo"}
        </button>
      </div>

      {error && <p className="mb-4 text-sm text-red-600">{error}</p>}

      {showForm && (
        <div className="mb-4">
          <TodoForm submitLabel="Add" onSubmit={handleCreate} />
        </div>
      )}

      {loading && todos.length === 0 ? (
        <p className="text-sm text-zinc-500">Loading...</p>
      ) : (
        <div className="flex flex-col gap-6">
          <div>
            <p className="mb-2 text-sm font-medium">Today</p>
            {dueToday.length === 0 ? (
              <p className="text-sm text-zinc-500">Nothing due today.</p>
            ) : (
              <ul className="flex flex-col gap-2">
                {dueToday.map((todo) => (
                  <TodoItem
                    key={todo.id}
                    todo={todo}
                    showCheckbox
                    onToggle={handleToggle}
                    onUpdate={handleUpdate}
                    onDelete={handleDelete}
                  />
                ))}
              </ul>
            )}
          </div>

          {notDueToday.length > 0 && (
            <div>
              <p className="mb-2 text-sm font-medium">
                Other todos
              </p>
              <ul className="flex flex-col gap-2">
                {notDueToday.map((todo, index) => (
                  <TodoItem
                    key={todo.id}
                    todo={todo}
                    showCheckbox={false}
                    onToggle={handleToggle}
                    onUpdate={handleUpdate}
                    onDelete={handleDelete}
                    onMove={handleMove}
                    isFirst={index === 0}
                    isLast={index === notDueToday.length - 1}
                  />
                ))}
              </ul>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
