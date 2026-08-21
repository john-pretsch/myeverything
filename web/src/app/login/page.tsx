"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState, type FormEvent } from "react";
import { ApiError } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";

function errorMessage(err: unknown): string {
  if (err instanceof ApiError) {
    return typeof err.data === "object" && err.data && "message" in err.data
      ? String((err.data as { message: unknown }).message)
      : "Something went wrong.";
  }
  return "Something went wrong.";
}

export default function LoginPage() {
  const router = useRouter();
  const { login, verifyTwoFactorLogin, register } = useAuth();
  const [mode, setMode] = useState<"login" | "register">("login");
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const [loginToken, setLoginToken] = useState<string | null>(null);
  const [useRecoveryCode, setUseRecoveryCode] = useState(false);
  const [twoFactorInput, setTwoFactorInput] = useState("");

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      if (mode === "login") {
        const result = await login(email, password);
        if (result.twoFactorRequired) {
          setLoginToken(result.loginToken);
          return;
        }
      } else {
        await register(name, email, password, passwordConfirmation);
      }
      router.push("/news");
    } catch (err) {
      setError(errorMessage(err));
    } finally {
      setSubmitting(false);
    }
  }

  async function handleTwoFactorSubmit(event: FormEvent) {
    event.preventDefault();
    if (!loginToken) return;
    setError(null);
    setSubmitting(true);
    try {
      await verifyTwoFactorLogin(loginToken, {
        code: useRecoveryCode ? undefined : twoFactorInput,
        recoveryCode: useRecoveryCode ? twoFactorInput : undefined,
      });
      router.push("/news");
    } catch (err) {
      setError(errorMessage(err));
    } finally {
      setSubmitting(false);
    }
  }

  if (loginToken) {
    return (
      <div className="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center px-4 py-16">
        <h1 className="mb-6 text-xl font-semibold">Two-factor authentication</h1>
        <form onSubmit={handleTwoFactorSubmit} className="flex flex-col gap-4">
          <label className="flex flex-col gap-1 text-sm">
            {useRecoveryCode ? "Recovery code" : "Authentication code"}
            <input
              type="text"
              required
              autoFocus
              value={twoFactorInput}
              onChange={(e) => setTwoFactorInput(e.target.value)}
              className="rounded border border-black/10 px-3 py-2 dark:border-white/10"
            />
          </label>
          {error && <p className="text-sm text-red-600">{error}</p>}
          <button
            type="submit"
            disabled={submitting}
            className="rounded bg-foreground px-4 py-2 text-sm font-medium text-background disabled:opacity-50"
          >
            Verify
          </button>
        </form>
        <button
          type="button"
          onClick={() => {
            setUseRecoveryCode(!useRecoveryCode);
            setTwoFactorInput("");
            setError(null);
          }}
          className="mt-4 text-sm text-zinc-500 hover:underline"
        >
          {useRecoveryCode
            ? "Use an authentication code instead"
            : "Use a recovery code instead"}
        </button>
        <button
          type="button"
          onClick={() => {
            setLoginToken(null);
            setTwoFactorInput("");
            setError(null);
          }}
          className="mt-2 text-sm text-zinc-500 hover:underline"
        >
          Back to log in
        </button>
      </div>
    );
  }

  return (
    <div className="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center px-4 py-16">
      <h1 className="mb-6 text-xl font-semibold">
        {mode === "login" ? "Log in" : "Create an account"}
      </h1>
      <form onSubmit={handleSubmit} className="flex flex-col gap-4">
        {mode === "register" && (
          <label className="flex flex-col gap-1 text-sm">
            Name
            <input
              type="text"
              required
              value={name}
              onChange={(e) => setName(e.target.value)}
              className="rounded border border-black/10 px-3 py-2 dark:border-white/10"
            />
          </label>
        )}
        <label className="flex flex-col gap-1 text-sm">
          Email
          <input
            type="email"
            required
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            className="rounded border border-black/10 px-3 py-2 dark:border-white/10"
          />
        </label>
        <label className="flex flex-col gap-1 text-sm">
          Password
          <input
            type="password"
            required
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className="rounded border border-black/10 px-3 py-2 dark:border-white/10"
          />
        </label>
        {mode === "register" && (
          <label className="flex flex-col gap-1 text-sm">
            Confirm password
            <input
              type="password"
              required
              value={passwordConfirmation}
              onChange={(e) => setPasswordConfirmation(e.target.value)}
              className="rounded border border-black/10 px-3 py-2 dark:border-white/10"
            />
          </label>
        )}
        {error && <p className="text-sm text-red-600">{error}</p>}
        <button
          type="submit"
          disabled={submitting}
          className="rounded bg-foreground px-4 py-2 text-sm font-medium text-background disabled:opacity-50"
        >
          {mode === "login" ? "Log in" : "Create account"}
        </button>
      </form>
      {mode === "login" && (
        <Link
          href="/forgot-password"
          className="mt-4 text-sm text-zinc-500 hover:underline"
        >
          Forgot password?
        </Link>
      )}
      <button
        type="button"
        onClick={() => setMode(mode === "login" ? "register" : "login")}
        className="mt-4 text-sm text-zinc-500 hover:underline"
      >
        {mode === "login"
          ? "Need an account? Register"
          : "Already have an account? Log in"}
      </button>
    </div>
  );
}
