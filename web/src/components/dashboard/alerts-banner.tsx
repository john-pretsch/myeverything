"use client";

import { useEffect, useState } from "react";
import { getAlerts, type Alert } from "@/lib/dashboard";

const DISMISSED_KEY = "dashboard-dismissed-alerts";

const STYLES: Record<Alert["severity"], string> = {
  critical: "border-red-600/40 bg-red-600/10 text-red-900 dark:text-red-200",
  high: "border-amber-600/40 bg-amber-500/10 text-amber-900 dark:text-amber-200",
  info: "border-black/10 bg-black/5 dark:border-white/10 dark:bg-white/5",
};

const LABELS: Record<Alert["kind"], string> = {
  security: "Security",
  news: "Breaking",
};

function readDismissed(): string[] {
  try {
    const raw = localStorage.getItem(DISMISSED_KEY);
    const parsed: unknown = raw ? JSON.parse(raw) : [];
    return Array.isArray(parsed) ? parsed.map(String) : [];
  } catch {
    return [];
  }
}

export function AlertsBanner() {
  const [alerts, setAlerts] = useState<Alert[] | null>(null);
  const [dismissed, setDismissed] = useState<string[]>(readDismissed);

  useEffect(() => {
    let ignore = false;
    getAlerts()
      .then((data) => {
        if (!ignore) setAlerts(data);
      })
      .catch(() => {
        if (!ignore) setAlerts([]);
      });
    return () => {
      ignore = true;
    };
  }, []);

  function dismiss(id: string) {
    const next = [...dismissed, id].slice(-100);
    setDismissed(next);
    try {
      localStorage.setItem(DISMISSED_KEY, JSON.stringify(next));
    } catch {
      // Dismissal just won't survive a reload.
    }
  }

  const visible = (alerts ?? []).filter((a) => !dismissed.includes(a.id));
  if (visible.length === 0) return null;

  return (
    <section aria-label="Alerts" className="mb-6 flex flex-col gap-2">
      {visible.map((alert) => (
        <div
          key={alert.id}
          className={`flex items-start justify-between gap-3 rounded border p-3 text-sm ${STYLES[alert.severity]}`}
        >
          <div className="min-w-0">
            <p className="font-medium">
              <span className="mr-2 text-xs font-semibold uppercase tracking-wide">
                {LABELS[alert.kind]}
              </span>
              {alert.url ? (
                <a
                  href={alert.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="hover:underline"
                >
                  {alert.title}
                </a>
              ) : (
                alert.title
              )}
            </p>
            {alert.summary && (
              <p className="mt-0.5 line-clamp-2 text-xs opacity-80">
                {alert.summary}
              </p>
            )}
            <p className="mt-0.5 text-xs opacity-70">{alert.source}</p>
          </div>
          <button
            type="button"
            aria-label={`Dismiss: ${alert.title}`}
            onClick={() => dismiss(alert.id)}
            className="shrink-0 opacity-60 hover:opacity-100"
          >
            ×
          </button>
        </div>
      ))}
    </section>
  );
}
