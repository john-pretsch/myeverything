"use client";

import { useEffect } from "react";
import { useAuth } from "@/lib/auth-context";

export default function LoginPage() {
  const { login } = useAuth();

  useEffect(() => {
    login();
  }, [login]);

  return (
    <div className="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center px-4 py-16">
      <h1 className="mb-6 text-xl font-semibold">Log in</h1>
      <p className="mb-4 text-sm text-zinc-500">Redirecting to Jepflow sign-in…</p>
      <button
        type="button"
        onClick={login}
        className="rounded border border-black/10 px-4 py-2 text-sm font-medium dark:border-white/10"
      >
        Sign in with Jepflow
      </button>
    </div>
  );
}
