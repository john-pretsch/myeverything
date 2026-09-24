"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useAuth } from "@/lib/auth-context";

const TABS = [
  { href: "/news", label: "News Feed", requiresAuth: false },
  { href: "/market", label: "Market Info", requiresAuth: false },
  { href: "/todo", label: "Todo", requiresAuth: true },
  { href: "/gig-leads", label: "Gig Leads", requiresAuth: true },
  { href: "/weather", label: "Weather", requiresAuth: false },
  { href: "/brainiac", label: "Brainiac", requiresAuth: true },
] as const;

export function TabNav() {
  const pathname = usePathname();
  const { user, status, logout } = useAuth();

  return (
    <nav className="border-b border-black/10 dark:border-white/10">
      <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between px-4">
        <ul className="flex flex-wrap gap-1">
          {TABS.map((tab) => {
            const active = pathname === tab.href;
            return (
              <li key={tab.href}>
                <Link
                  href={tab.href}
                  className={`inline-flex items-center gap-1.5 border-b-2 px-3 py-3 text-sm font-medium ${
                    active
                      ? "border-foreground text-foreground"
                      : "border-transparent text-zinc-500 hover:text-foreground"
                  }`}
                >
                  {tab.label}
                  {tab.requiresAuth && (
                    <span className="text-xs text-zinc-400">
                      (login required)
                    </span>
                  )}
                </Link>
              </li>
            );
          })}
        </ul>
        <div className="py-2 text-sm">
          {status === "authenticated" && user ? (
            <div className="flex items-center gap-3">
              <span className="text-zinc-500">{user.email}</span>
              <Link href="/account" className="font-medium hover:underline">
                Account
              </Link>
              <button
                type="button"
                onClick={() => logout()}
                className="font-medium hover:underline"
              >
                Log out
              </button>
            </div>
          ) : status === "unauthenticated" ? (
            <Link href="/login" className="font-medium hover:underline">
              Log in
            </Link>
          ) : null}
        </div>
      </div>
    </nav>
  );
}
