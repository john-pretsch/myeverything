"use client";

import { RequireAuth } from "@/components/require-auth";
import { useAuth } from "@/lib/auth-context";

const SSO_URL = process.env.NEXT_PUBLIC_SSO_URL ?? "https://sso.jepflow.io";

export default function AccountPage() {
  const { user } = useAuth();

  return (
    <RequireAuth>
      <div className="flex flex-col gap-6">
        <div>
          <h1 className="text-xl font-semibold">Account</h1>
          {user && <p className="text-sm text-zinc-500">{user.email}</p>}
        </div>
        <div className="rounded border border-black/10 p-4 text-sm dark:border-white/10">
          <p className="mb-2">
            Your name, email and password are managed by your Jepflow account.
          </p>
          <a href={`${SSO_URL}/profile`} className="font-medium underline">
            Manage your Jepflow account
          </a>
        </div>
      </div>
    </RequireAuth>
  );
}
