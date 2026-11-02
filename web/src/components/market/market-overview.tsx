"use client";

import { useEffect, useState } from "react";
import { useAuth } from "@/lib/auth-context";
import {
  getMarketOverview,
  getMarketPreferences,
  getMovers,
  saveMarketPreferences,
} from "@/lib/market";
import {
  MOVER_COUNTS,
  type CryptoPrice,
  type CurrencyRate,
  type MarketIndex,
  type MarketOverview as MarketOverviewData,
  MOVER_REGIONS,
  type MoverCount,
  type MoverRegion,
  type Movers,
  type StockMover,
} from "@/lib/market-types";
import { StockModal } from "./stock-modal";

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

function MoverList({
  title,
  items,
  onSelect,
}: {
  title: string;
  items: StockMover[];
  onSelect: (stock: StockMover) => void;
}) {
  return (
    <div>
      <p className="mb-2 text-xs font-medium text-zinc-500">{title}</p>
      <ul className="flex flex-col gap-1 text-sm">
        {items.map((stock) => (
          <li key={stock.symbol}>
            <button
              type="button"
              onClick={() => onSelect(stock)}
              className="flex w-full items-center justify-between gap-3 rounded px-1 py-0.5 text-left hover:bg-black/5 dark:hover:bg-white/10"
            >
              <span className="text-zinc-500">
                {stock.name} <span className="text-xs">({stock.symbol})</span>
              </span>
              <span className="flex items-center gap-2">
                <span>
                  ${formatPrice(stock.price)}
                  {stock.currency && stock.currency !== "USD"
                    ? ` ${stock.currency}`
                    : ""}
                </span>
                <ChangeBadge pct={stock.change_pct} />
              </span>
            </button>
          </li>
        ))}
        {items.length === 0 && <li className="text-zinc-500">No data.</li>}
      </ul>
    </div>
  );
}

function MoversSection({
  gainers,
  losers,
  error,
  count,
  region,
  loading,
  onCountChange,
  onRegionChange,
  onSelect,
}: {
  gainers: StockMover[];
  losers: StockMover[];
  error: string | null;
  count: MoverCount;
  region: MoverRegion;
  loading: boolean;
  onCountChange: (count: MoverCount) => void;
  onRegionChange: (region: MoverRegion) => void;
  onSelect: (stock: StockMover) => void;
}) {
  return (
    <div className="rounded border border-black/10 p-4 dark:border-white/10">
      <div className="mb-3 flex items-center justify-between">
        <p className="text-sm font-medium">
          Top Movers ({MOVER_REGIONS.find((r) => r.value === region)?.title})
        </p>
        <div className="flex items-center gap-2">
          <select
            aria-label="Market"
            value={region}
            onChange={(e) => onRegionChange(e.target.value as MoverRegion)}
            className="rounded border border-black/10 bg-background px-2 py-1 text-xs font-medium dark:border-white/10"
          >
            {MOVER_REGIONS.map((r) => (
              <option key={r.value} value={r.value}>
                {r.label}
              </option>
            ))}
          </select>
          <select
            aria-label="Number of movers"
            value={count}
            onChange={(e) =>
              onCountChange(Number(e.target.value) as MoverCount)
            }
            className="rounded border border-black/10 bg-background px-2 py-1 text-xs font-medium dark:border-white/10"
          >
            {MOVER_COUNTS.map((n) => (
              <option key={n} value={n}>
                Top {n}
              </option>
            ))}
          </select>
        </div>
      </div>
      {error ? (
        <SectionError message={error} />
      ) : (
        <div
          className={`grid grid-cols-1 gap-4 sm:grid-cols-2 ${loading ? "opacity-50" : ""}`}
        >
          <MoverList
            title="Best performers"
            items={gainers}
            onSelect={onSelect}
          />
          <MoverList
            title="Worst performers"
            items={losers}
            onSelect={onSelect}
          />
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
  const { status: authStatus } = useAuth();
  const [data, setData] = useState<MarketOverviewData | null>(null);
  const [updatedAt, setUpdatedAt] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [moversCount, setMoversCount] = useState<MoverCount>(MOVER_COUNTS[0]);
  const [moversRegion, setMoversRegion] = useState<MoverRegion>(
    MOVER_REGIONS[0].value,
  );
  const [moversOverride, setMoversOverride] = useState<{
    count: MoverCount;
    region: MoverRegion;
    movers: Movers;
  } | null>(null);
  const [selectedStock, setSelectedStock] = useState<StockMover | null>(null);
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

  // Logged-in users get their last-used movers market and size back.
  useEffect(() => {
    if (authStatus !== "authenticated") return;

    let ignore = false;
    getMarketPreferences()
      .then((prefs) => {
        if (ignore) return;
        setMoversCount(prefs.movers_count);
        setMoversRegion(prefs.movers_region);
      })
      .catch(() => {});

    return () => {
      ignore = true;
    };
  }, [authStatus]);

  function changeMovers(count: MoverCount, region: MoverRegion) {
    setMoversCount(count);
    setMoversRegion(region);
    if (authStatus === "authenticated") {
      saveMarketPreferences({
        movers_count: count,
        movers_region: region,
      }).catch(() => {});
    }
  }

  // The overview carries the default-size lists; any other size is fetched
  // on its own (and again after each Refresh).
  useEffect(() => {
    if (
      moversCount === MOVER_COUNTS[0] &&
      moversRegion === MOVER_REGIONS[0].value
    )
      return;

    let ignore = false;
    getMovers(moversCount, moversRegion, requestId > 0)
      .then((movers) => {
        if (!ignore)
          setMoversOverride({
            count: moversCount,
            region: moversRegion,
            movers,
          });
      })
      .catch(() => {
        if (!ignore) {
          setMoversOverride({
            count: moversCount,
            region: moversRegion,
            movers: {
              gainers: [],
              losers: [],
              error: "Top movers are unavailable right now.",
            },
          });
        }
      });

    return () => {
      ignore = true;
    };
  }, [moversCount, moversRegion, requestId]);

  function refresh() {
    setRequestId((n) => n + 1);
  }

  const isDefaultMovers =
    moversCount === MOVER_COUNTS[0] && moversRegion === MOVER_REGIONS[0].value;
  const shownMovers = isDefaultMovers ? data?.movers : moversOverride?.movers;
  const moversLoading =
    !isDefaultMovers &&
    (moversOverride?.count !== moversCount ||
      moversOverride?.region !== moversRegion);

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
            gainers={(shownMovers ?? data.movers).gainers}
            losers={(shownMovers ?? data.movers).losers}
            error={(shownMovers ?? data.movers).error}
            count={moversCount}
            region={moversRegion}
            loading={moversLoading}
            onCountChange={(count) => changeMovers(count, moversRegion)}
            onRegionChange={(region) => changeMovers(moversCount, region)}
            onSelect={setSelectedStock}
          />
        </div>
      )}
      {selectedStock && (
        <StockModal
          stock={selectedStock}
          onClose={() => setSelectedStock(null)}
        />
      )}
    </div>
  );
}
