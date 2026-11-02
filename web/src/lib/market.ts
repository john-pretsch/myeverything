import { apiFetch } from "./api";
import type {
  ChartRange,
  MarketOverview,
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
