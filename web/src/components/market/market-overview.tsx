"use client";

import { useEffect, useState } from "react";
import { getMarketOverview } from "@/lib/market";
import type {
  CryptoPrice,
  CurrencyRate,
  MarketIndex,
  MarketOverview as MarketOverviewData,
  StockMover,
} from "@/lib/market-types";

function formatPrice(value: number | null, decimals?: number): string {
  if (value === null) return "—";
  const places = decimals ?? (Math.abs(value) < 1 ? 4 : 2);
  return new Intl.NumberFormat("en-US", {
    minimumFractionDigits: places,
    maximumFractionDigits: places,
  }).format(value);
}

function ChangeBadge({ pct }: { pct: number | null }) {
  if (pct === null) return <span className="text-zinc-500">—</span>;
  const sign = pct > 0 ? "+" : "";
  const color =
    pct > 0
      ? "text-green-600 dark:text-green-500"
      : pct < 0
        ? "text-red-600 dark:text-red-500"
        : "text-zinc-500";
  return (
    <span className={color}>
      {sign}
      {pct.toFixed(2)}%
    </span>
  );
}

function SectionError({ message }: { message: string }) {
  return <p className="text-sm text-red-600">{message}</p>;
}

function CurrenciesSection({
  base,
  rates,
  error,
}: {
  base: string;
  rates: CurrencyRate[];
  error: string | null;
}) {
  return (
    <div className="rounded border border-black/10 p-4 dark:border-white/10">
      <p className="mb-3 text-sm font-medium">Currencies (per 1 {base})</p>
      {error ? (
        <SectionError message={error} />
      ) : (
        <ul className="flex flex-col gap-1 text-sm">
          {rates.map((rate) => (
            <li key={rate.code} className="flex items-center justify-between">
              <span className="text-zinc-500">{rate.name}</span>
              <span>
                {formatPrice(rate.rate)} {rate.code}
              </span>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

function CryptoSection({
  items,
  error,
}: {
  items: CryptoPrice[];
  error: string | null;
}) {
  return (
    <div className="rounded border border-black/10 p-4 dark:border-white/10">
      <p className="mb-3 text-sm font-medium">Crypto</p>
      {error ? (
        <SectionError message={error} />
      ) : (
        <ul className="flex flex-col gap-1 text-sm">
          {items.map((coin) => (
            <li
              key={coin.symbol}
              className="flex items-center justify-between gap-3"
            >
              <span className="text-zinc-500">
                {coin.name} <span className="text-xs">({coin.symbol})</span>
              </span>
              <span className="flex items-center gap-2">
                <span>${formatPrice(coin.price_usd)}</span>
                <ChangeBadge pct={coin.change_24h_pct} />
              </span>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

function IndicesSection({
  items,
  error,
}: {
  items: MarketIndex[];
  error: string | null;
}) {
  return (
    <div className="rounded border border-black/10 p-4 dark:border-white/10">
      <p className="mb-3 text-sm font-medium">Stock Market Indices</p>
      {error ? (
        <SectionError message={error} />
      ) : (
        <ul className="flex flex-col gap-1 text-sm">
          {items.map((index) => (
            <li
              key={index.symbol}
              className="flex items-center justify-between gap-3"
            >
              <span className="text-zinc-500">
                {index.name} <span className="text-xs">({index.exchange})</span>
              </span>
              <span className="flex items-center gap-2">
                <span>
                  {formatPrice(index.price)}
                  {index.currency ? ` ${index.currency}` : ""}
                </span>
                <ChangeBadge pct={index.change_pct} />
              </span>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

function MoverList({ title, items }: { title: string; items: StockMover[] }) {
  return (
    <div>
      <p className="mb-2 text-xs font-medium text-zinc-500">{title}</p>
      <ul className="flex flex-col gap-1 text-sm">
        {items.map((stock) => (
          <li
            key={stock.symbol}
            className="flex items-center justify-between gap-3"
          >
            <span className="text-zinc-500">
              {stock.name} <span className="text-xs">({stock.symbol})</span>
            </span>
            <span className="flex items-center gap-2">
              <span>${formatPrice(stock.price)}</span>
              <ChangeBadge pct={stock.change_pct} />
            </span>
          </li>
        ))}
        {items.length === 0 && (
          <li className="text-zinc-500">No data.</li>
        )}
      </ul>
    </div>
  );
}

function MoversSection({
  gainers,
  losers,
  error,
}: {
  gainers: StockMover[];
  losers: StockMover[];
  error: string | null;
}) {
  return (
    <div className="rounded border border-black/10 p-4 dark:border-white/10">
      <p className="mb-3 text-sm font-medium">Top Movers (US markets)</p>
      {error ? (
        <SectionError message={error} />
      ) : (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <MoverList title="Best performers" items={gainers} />
          <MoverList title="Worst performers" items={losers} />
        </div>
      )}
    </div>
  );
}

function timeAgo(iso: string): string {
  const diffMs = Date.now() - new Date(iso).getTime();
  const diffMin = Math.round(diffMs / 60_000);
  if (diffMin < 1) return "just now";
  if (diffMin < 60) return `${diffMin}m ago`;
  return `${Math.round(diffMin / 60)}h ago`;
}

export function MarketOverview() {
  const [data, setData] = useState<MarketOverviewData | null>(null);
  const [updatedAt, setUpdatedAt] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [requestId, setRequestId] = useState(0);
  const [resolvedId, setResolvedId] = useState(-1);
  const loading = requestId !== resolvedId;

  useEffect(() => {
    let ignore = false;

    getMarketOverview(requestId > 0)
      .then((res) => {
        if (ignore) return;
        setData(res.data);
        setUpdatedAt(res.updated_at);
        setError(null);
      })
      .catch(() => {
        if (!ignore) setError("Couldn't load market data.");
      })
      .finally(() => {
        if (!ignore) setResolvedId(requestId);
      });

    return () => {
      ignore = true;
    };
  }, [requestId]);

  function refresh() {
    setRequestId((n) => n + 1);
  }

  return (
    <div>
      <div className="mb-4 flex items-center justify-between">
        <h1 className="text-xl font-semibold">Market Info</h1>
        <div className="flex items-center gap-3 text-xs text-zinc-500">
          {updatedAt && !loading && <span>Updated {timeAgo(updatedAt)}</span>}
          <button
            type="button"
            onClick={refresh}
            disabled={loading}
            className="rounded border border-black/10 px-2 py-1 font-medium text-foreground disabled:opacity-50 dark:border-white/10"
          >
            {loading ? "Refreshing..." : "Refresh"}
          </button>
        </div>
      </div>

      {error && <p className="mb-4 text-sm text-red-600">{error}</p>}

      {!data ? (
        <p className="text-sm text-zinc-500">Loading...</p>
      ) : (
        <div className="flex flex-col gap-4">
          <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
            <CurrenciesSection
              base={data.currencies.base}
              rates={data.currencies.rates}
              error={data.currencies.error}
            />
            <CryptoSection
              items={data.crypto.items}
              error={data.crypto.error}
            />
            <IndicesSection
              items={data.indices.items}
              error={data.indices.error}
            />
          </div>
          <MoversSection
            gainers={data.movers.gainers}
            losers={data.movers.losers}
            error={data.movers.error}
          />
        </div>
      )}
    </div>
  );
}
