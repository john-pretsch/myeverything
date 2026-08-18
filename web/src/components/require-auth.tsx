"use client";

import Link from "next/link";
import type { ReactNode } from "react";
import { useAuth } from "@/lib/auth-context";

export function RequireAuth({ children }: { children: ReactNode }) {
  const { status } = useAuth();

  if (status === "loading") {
    return <p className="text-sm text-zinc-500">Loading...</p>;
  }

  if (status === "unauthenticated") {
    return (
      <div className="rounded border border-black/10 p-6 text-sm dark:border-white/10">
        <p className="mb-2">You need to be logged in to view this tab.</p>
        <Link href="/login" className="font-medium underline">
          Log in
        </Link>
      </div>
    );
  }

  return <>{children}</>;
}
