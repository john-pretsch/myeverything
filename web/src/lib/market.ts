import { apiFetch } from "./api";
import type {
  ChartRange,
  CurrencyTrends,
  MarketOverview,
  MoverCount,
  MoverRegion,
  Movers,
  StockChart,
  StockProfile,
} from "./market-types";

export async function getMarketOverview(fresh = false): Promise<{
  data: MarketOverview;
  updated_at: string;
}> {
  return apiFetch<{ data: MarketOverview; updated_at: string }>(
    `/api/market/overview${fresh ? "?fresh=1" : ""}`,
  );
}

export async function getMovers(
  count: MoverCount,
  region: MoverRegion,
  fresh = false,
): Promise<Movers> {
  const res = await apiFetch<{ data: Movers }>(
    `/api/market/movers?count=${count}&region=${region}${fresh ? "&fresh=1" : ""}`,
  );
  return res.data;
}

export type MarketPreferences = {
  movers_count: MoverCount;
  movers_region: MoverRegion;
};

export async function getMarketPreferences(): Promise<MarketPreferences> {
  const res = await apiFetch<{ data: MarketPreferences }>(
    "/api/market/preferences",
  );
  return res.data;
}

export function saveMarketPreferences(
  preferences: MarketPreferences,
): Promise<unknown> {
  return apiFetch("/api/market/preferences", {
    method: "PUT",
    body: preferences,
  });
}

export async function getStockProfile(
  symbol: string,
): Promise<StockProfile | null> {
  const res = await apiFetch<{ data: StockProfile | null }>(
    `/api/market/stocks/${encodeURIComponent(symbol)}`,
  );
  return res.data;
}

export async function getStockChart(
  symbol: string,
  range: ChartRange,
): Promise<StockChart> {
  const res = await apiFetch<{ data: StockChart }>(
    `/api/market/stocks/${encodeURIComponent(symbol)}/chart?range=${range}`,
  );
  return res.data;
}

export async function getCurrencyTrends(): Promise<CurrencyTrends> {
  const res = await apiFetch<{ data: CurrencyTrends }>(
    "/api/market/currency-trends",
  );
  return res.data;
}
