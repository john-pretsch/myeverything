"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import {
  getCurrencyTrends,
  getMarketOverview,
  getStockChart,
} from "@/lib/market";
import type {
  CurrencyTrends,
  MarketIndex,
  MarketOverview,
} from "@/lib/market-types";
import { Sparkline } from "./sparkline";

function Change({ pct }: { pct: number | null }) {
  if (pct === null) return <span className="text-zinc-500">—</span>;
  const color =
    pct > 0
      ? "text-green-600 dark:text-green-500"
      : pct < 0
        ? "text-red-600 dark:text-red-500"
        : "text-zinc-500";
  return (
    <span className={color}>
      {pct > 0 ? "+" : ""}
      {pct.toFixed(2)}%
    </span>
  );
}

function Tile({
  label,
  value,
  change,
  changeNote,
  values,
}: {
  label: string;
  value: string;
  change: number | null;
  changeNote?: string;
  values: number[] | null;
}) {
  return (
    <div className="rounded border border-black/10 p-3 dark:border-white/10">
      <div className="flex items-baseline justify-between gap-2">
        <p className="text-sm font-medium">{label}</p>
        <p className="text-sm tabular-nums">{value}</p>
      </div>
      <div className="mt-1 text-xs">
        <Change pct={change} />
        {changeNote && <span className="ml-1 text-zinc-500">{changeNote}</span>}
      </div>
      <div className="mt-1 h-9">{values && <Sparkline values={values} />}</div>
    </div>
  );
}

function IndexTile({ index }: { index: MarketIndex }) {
  const [values, setValues] = useState<number[] | null>(null);

  useEffect(() => {
    let ignore = false;
    getStockChart(index.symbol, "1mo")
      .then((chart) => {
        if (!ignore) setValues(chart.points.map((p) => p.c));
      })
      .catch(() => {});
    return () => {
      ignore = true;
    };
  }, [index.symbol]);

  return (
    <Tile
      label={index.name}
      value={new Intl.NumberFormat("en-US", {
        maximumFractionDigits: 2,
      }).format(index.price)}
      change={index.change_pct}
      changeNote="today"
      values={values}
    />
  );
}

export function MarketSnapshot() {
  const [overview, setOverview] = useState<MarketOverview | null>(null);
  const [overviewError, setOverviewError] = useState(false);
  const [trends, setTrends] = useState<CurrencyTrends | null>(null);
  const [trendsError, setTrendsError] = useState(false);

  useEffect(() => {
    let ignore = false;
    getMarketOverview()
      .then((res) => {
        if (!ignore) setOverview(res.data);
      })
      .catch(() => {
        if (!ignore) setOverviewError(true);
      });
    getCurrencyTrends()
      .then((data) => {
        if (!ignore) setTrends(data);
      })
      .catch(() => {
        if (!ignore) setTrendsError(true);
      });
    return () => {
      ignore = true;
    };
  }, []);

  const indices = overview?.indices.items ?? [];

  return (
    <section>
      <div className="mb-3 flex items-center justify-between">
        <h2 className="text-lg font-semibold">Markets</h2>
        <Link href="/market" className="text-sm font-medium hover:underline">
          Market Info →
        </Link>
      </div>

      <p className="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-500">
        Indices · 1 month
      </p>
      {overviewError || overview?.indices.error ? (
        <p className="mb-4 text-sm text-red-600">
          Index data is unavailable right now.
        </p>
      ) : !overview ? (
        <p className="mb-4 text-sm text-zinc-500">Loading...</p>
      ) : (
        <div className="mb-4 grid gap-3 sm:grid-cols-3">
          {indices.map((index) => (
            <IndexTile key={index.symbol} index={index} />
          ))}
        </div>
      )}

      <p className="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-500">
        Currencies · 30 days
      </p>
      {trendsError || trends?.error ? (
        <p className="text-sm text-red-600">
          Currency data is unavailable right now.
        </p>
      ) : !trends ? (
        <p className="text-sm text-zinc-500">Loading...</p>
      ) : (
        <div className="grid gap-3 sm:grid-cols-3">
          {trends.items.map((item) => (
            <Tile
              key={item.pair}
              label={item.pair}
              value={item.rate.toFixed(4)}
              change={item.change_pct}
              changeNote="30d"
              values={item.points.map((p) => p.c)}
            />
          ))}
        </div>
      )}
    </section>
  );
}
