import { apiFetch } from "./api";
import type { MarketOverview } from "./market-types";

export async function getMarketOverview(): Promise<{
  data: MarketOverview;
  updated_at: string;
}> {
  return apiFetch<{ data: MarketOverview; updated_at: string }>(
    "/api/market/overview",
  );
}
