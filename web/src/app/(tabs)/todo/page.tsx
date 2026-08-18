import { RequireAuth } from "@/components/require-auth";

export default function TodoPage() {
  return (
    <RequireAuth>
      <h1 className="mb-2 text-xl font-semibold">Todo</h1>
      <p className="text-sm text-zinc-500">
        Your personal, user-defined task list. Not wired up yet.
      </p>
    </RequireAuth>
  );
}
