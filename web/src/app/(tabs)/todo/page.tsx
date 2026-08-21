import { RequireAuth } from "@/components/require-auth";
import { TodoList } from "@/components/todo/todo-list";

export default function TodoPage() {
  return (
    <RequireAuth>
      <TodoList />
    </RequireAuth>
  );
}
