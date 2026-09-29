"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState } from "react";
import { getMarketOverview } from "@/lib/market";
import { useAuth } from "@/lib/auth-context";

const TABS = [
  { href: "/news", label: "News Feed", requiresAuth: false },
  { href: "/market", label: "Market Info", requiresAuth: false },
  { href: "/todo", label: "Todo", requiresAuth: true },
  { href: "/gig-leads", label: "Gig Leads", requiresAuth: true },
  { href: "/weather", label: "Weather", requiresAuth: false },
  { href: "/brainiac", label: "Brainiac", requiresAuth: true },
] as const;

function AuthControls({ onNavigate }: { onNavigate?: () => void }) {
  const { user, status, logout } = useAuth();

  if (status === "authenticated" && user) {
    return (
      <div className="flex flex-col items-start gap-2 md:flex-row md:items-center md:gap-3">
        <span className="text-zinc-500">{user.email}</span>
        <Link
          href="/account"
          onClick={onNavigate}
          className="font-medium hover:underline"
        >
          Account
        </Link>
        <button
          type="button"
          onClick={() => {
            logout();
            onNavigate?.();
          }}
          className="font-medium hover:underline"
        >
          Log out
        </button>
      </div>
    );
  }

  if (status === "unauthenticated") {
    return (
      <Link
        href="/login"
        onClick={onNavigate}
        className="font-medium hover:underline"
      >
        Log in
      </Link>
    );
  }

  return null;
}

function hardReload() {
  // A changed query string guarantees the browser can't serve this
  // navigation from cache — location.reload() alone doesn't reliably
  // force a network fetch of the document across browsers.
  const url = new URL(window.location.href);
  url.searchParams.set("_refresh", Date.now().toString());
  window.location.href = url.toString();
}

function ForceRefreshButton() {
  const [refreshing, setRefreshing] = useState(false);

  async function handleClick() {
    setRefreshing(true);
    try {
      // The only server-side response cache in this app lives in
      // MarketDataService (Cache::remember, 2-5 min TTL); bust it so the
      // reload below actually pulls fresh data instead of a stale hit.
      // Bounded so a slow/unreachable provider can't hang the button.
      await Promise.race([
        getMarketOverview(true),
        new Promise((resolve) => setTimeout(resolve, 3000)),
      ]);
    } catch {
      // Best-effort — still hard-reload below even if this failed.
    } finally {
      hardReload();
    }
  }

  return (
    <button
      type="button"
      onClick={handleClick}
      disabled={refreshing}
      aria-label="Force refresh"
      title="Force refresh — bypass all caches and reload everything from the server"
      className="flex items-center gap-1.5 rounded border border-black/10 p-2 text-zinc-500 hover:text-foreground disabled:opacity-50 dark:border-white/10"
    >
      <svg
        viewBox="0 0 24 24"
        width="16"
        height="16"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
        className={refreshing ? "animate-spin" : undefined}
      >
        <path d="M20 12a8 8 0 1 1-2.34-5.66" />
        <path d="M20 4v5h-5" />
      </svg>
    </button>
  );
}

export function TabNav() {
  const pathname = usePathname();
  const [open, setOpen] = useState(false);
  const [lastPathname, setLastPathname] = useState(pathname);

  if (pathname !== lastPathname) {
    setLastPathname(pathname);
    setOpen(false);
  }

  return (
    <nav className="border-b border-black/10 dark:border-white/10">
      <div className="mx-auto flex max-w-5xl items-center justify-between px-4">
        <ul className="hidden flex-wrap gap-1 md:flex">
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
        <div className="hidden items-center gap-3 py-2 text-sm md:flex">
          <AuthControls />
          <ForceRefreshButton />
        </div>

        <div className="flex w-full items-center justify-between py-2 md:hidden">
          <span className="text-sm font-semibold">myeverything</span>
          <div className="flex items-center gap-2">
            <ForceRefreshButton />
            <button
              type="button"
              onClick={() => setOpen((v) => !v)}
              aria-label={open ? "Close menu" : "Open menu"}
              aria-expanded={open}
              className="rounded border border-black/10 p-2 dark:border-white/10"
            >
              <svg
                viewBox="0 0 24 24"
                width="20"
                height="20"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
              >
                {open ? (
                  <path d="M6 6l12 12M18 6l-12 12" />
                ) : (
                  <path d="M4 7h16M4 12h16M4 17h16" />
                )}
              </svg>
            </button>
          </div>
        </div>
      </div>

      {open && (
        <div className="border-t border-black/10 px-4 py-3 md:hidden dark:border-white/10">
          <ul className="flex flex-col gap-1">
            {TABS.map((tab) => {
              const active = pathname === tab.href;
              return (
                <li key={tab.href}>
                  <Link
                    href={tab.href}
                    className={`flex items-center gap-1.5 rounded px-2 py-2 text-sm font-medium ${
                      active
                        ? "bg-black/5 text-foreground dark:bg-white/10"
                        : "text-zinc-500"
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
          <div className="mt-3 border-t border-black/10 pt-3 text-sm dark:border-white/10">
            <AuthControls onNavigate={() => setOpen(false)} />
          </div>
        </div>
      )}
    </nav>
  );
}
