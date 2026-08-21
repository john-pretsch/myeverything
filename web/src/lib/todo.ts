import { apiFetch } from "./api";
import type { Todo, TodoInput } from "./todo-types";

export async function getTodos(): Promise<Todo[]> {
  const res = await apiFetch<{ data: Todo[] }>("/api/todos");
  return res.data;
}

export async function createTodo(input: TodoInput): Promise<Todo> {
  const res = await apiFetch<{ data: Todo }>("/api/todos", {
    method: "POST",
    body: input,
  });
  return res.data;
}

export async function updateTodo(
  id: number,
  input: TodoInput,
): Promise<Todo> {
  const res = await apiFetch<{ data: Todo }>(`/api/todos/${id}`, {
    method: "PATCH",
    body: input,
  });
  return res.data;
}

export function deleteTodo(id: number): Promise<void> {
  return apiFetch<void>(`/api/todos/${id}`, { method: "DELETE" });
}

export async function toggleTodo(id: number): Promise<Todo> {
  const res = await apiFetch<{ data: Todo }>(`/api/todos/${id}/toggle`, {
    method: "POST",
  });
  return res.data;
}

export async function reorderTodos(todoIds: number[]): Promise<Todo[]> {
  const res = await apiFetch<{ data: Todo[] }>("/api/todos/reorder", {
    method: "PATCH",
    body: { todo_ids: todoIds },
  });
  return res.data;
}
