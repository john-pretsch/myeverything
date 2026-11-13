"use client";

import Link from "next/link";
import { useAuth } from "@/lib/auth-context";
import { TodoList } from "@/components/todo/todo-list";
import { AlertsBanner } from "./alerts-banner";
import { MarketSnapshot } from "./market-snapshot";

function TodoPanel() {
  const { status } = useAuth();

  if (status === "loading") {
    return <p className="text-sm text-zinc-500">Loading...</p>;
  }

  if (status === "unauthenticated") {
    return (
      <div className="rounded border border-black/10 p-4 text-sm dark:border-white/10">
        <p className="mb-2 font-medium">Todo</p>
        <Link href="/login" className="underline">
          Log in to see your todos
        </Link>
      </div>
    );
  }

  return <TodoList />;
}

export function Dashboard() {
  return (
    <div>
      <AlertsBanner />
      <div className="grid gap-8 lg:grid-cols-[3fr_2fr]">
        <MarketSnapshot />
        <TodoPanel />
      </div>
    </div>
  );
}
