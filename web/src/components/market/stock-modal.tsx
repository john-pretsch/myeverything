"use client";

import { useEffect, useState } from "react";
import { getStockChart, getStockProfile } from "@/lib/market";
import {
  CHART_RANGES,
  type ChartRange,
  type StockChart,
  type StockMover,
  type StockProfile,
} from "@/lib/market-types";
import { PriceChart } from "./price-chart";

export function StockModal({
  stock,
  onClose,
}: {
  stock: StockMover;
  onClose: () => void;
}) {
  const [range, setRange] = useState<ChartRange>("1y");
  const [profile, setProfile] = useState<StockProfile | null>(null);
  const [profileLoading, setProfileLoading] = useState(true);
  const [chart, setChart] = useState<StockChart | null>(null);
  const [chartError, setChartError] = useState<string | null>(null);

  useEffect(() => {
    let ignore = false;
    getStockProfile(stock.symbol)
      .then((data) => {
        if (!ignore) setProfile(data);
      })
      .catch(() => {})
      .finally(() => {
        if (!ignore) setProfileLoading(false);
      });
    return () => {
      ignore = true;
    };
  }, [stock.symbol]);

  useEffect(() => {
    let ignore = false;
    getStockChart(stock.symbol, range)
      .then((data) => {
        if (ignore) return;
        setChart(data);
        setChartError(null);
      })
      .catch(() => {
        if (ignore) return;
        setChart(null);
        setChartError("Price history is unavailable right now.");
      });
    return () => {
      ignore = true;
    };
  }, [stock.symbol, range]);

  useEffect(() => {
    function onKeyDown(event: KeyboardEvent) {
      if (event.key === "Escape") onClose();
    }
    document.addEventListener("keydown", onKeyDown);
    return () => document.removeEventListener("keydown", onKeyDown);
  }, [onClose]);

  const chartCurrent = chart !== null && chart.range === range;

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      onClick={onClose}
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-label={`${stock.name} details`}
        onClick={(event) => event.stopPropagation()}
        className="max-h-full w-full max-w-2xl overflow-y-auto rounded-lg bg-background p-5 shadow-xl"
      >
        <div className="mb-3 flex items-start justify-between gap-3">
          <div>
            <h2 className="text-lg font-semibold">{stock.name}</h2>
            <p className="text-xs text-zinc-500">
              {stock.symbol}
              {stock.exchange ? ` · ${stock.exchange}` : ""}
              {profile?.sector ? ` · ${profile.sector}` : ""}
              {profile?.industry ? ` · ${profile.industry}` : ""}
            </p>
          </div>
          <button
            type="button"
            onClick={onClose}
            aria-label="Close"
            className="text-xl leading-none text-zinc-500 hover:text-foreground"
          >
            ×
          </button>
        </div>

        {profileLoading ? (
          <p className="mb-4 text-sm text-zinc-500">Loading...</p>
        ) : profile?.description ? (
          <p className="mb-4 text-sm text-zinc-600 dark:text-zinc-300">
            {profile.description}
            {profile.website && (
              <>
                {" "}
                <a
                  href={profile.website}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="underline"
                >
                  Website
                </a>
              </>
            )}
          </p>
        ) : (
          <p className="mb-4 text-sm text-zinc-500">
            No company description available.
          </p>
        )}

        <div className="mb-3 flex flex-wrap gap-1">
          {CHART_RANGES.map((r) => (
            <button
              key={r.value}
              type="button"
              onClick={() => setRange(r.value)}
              className={`rounded px-2.5 py-1 text-xs font-medium ${
                range === r.value
                  ? "bg-foreground text-background"
                  : "border border-black/10 text-zinc-500 hover:text-foreground dark:border-white/10"
              }`}
            >
              {r.label}
            </button>
          ))}
        </div>

        {chartError ? (
          <p className="text-sm text-red-600">{chartError}</p>
        ) : chart && chartCurrent ? (
          <PriceChart chart={chart} />
        ) : (
          <p className="py-16 text-center text-sm text-zinc-500">Loading chart...</p>
        )}
      </div>
    </div>
  );
}
