"use client";

import Link from "next/link";
import type { ReactNode } from "react";
import { useAuth } from "@/lib/auth-context";

export function RequireAdmin({ children }: { children: ReactNode }) {
  const { user, status } = useAuth();

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

  if (user?.role !== "admin") {
    return (
      <div className="rounded border border-black/10 p-6 text-sm dark:border-white/10">
        <p>You don&apos;t have permission to view this page.</p>
      </div>
    );
  }

  return <>{children}</>;
}
