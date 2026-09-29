import { apiFetch } from "./api";
import type { MarketOverview } from "./market-types";

export async function getMarketOverview(fresh = false): Promise<{
  data: MarketOverview;
  updated_at: string;
}> {
  return apiFetch<{ data: MarketOverview; updated_at: string }>(
    `/api/market/overview${fresh ? "?fresh=1" : ""}`,
  );
}
