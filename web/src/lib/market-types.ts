export type CurrencyRate = {
  code: string;
  name: string;
  rate: number;
};

export type CryptoPrice = {
  symbol: string;
  name: string;
  price_usd: number | null;
  change_24h_pct: number | null;
};

export type MarketIndex = {
  symbol: string;
  name: string;
  exchange: string;
  price: number;
  previous_close: number | null;
  change_pct: number | null;
  currency: string | null;
};

export type StockMover = {
  symbol: string;
  name: string;
  price: number;
  change_pct: number;
  exchange: string | null;
  currency?: string | null;
};

export const MOVER_COUNTS = [5, 10, 25, 50] as const;

export type MoverCount = (typeof MOVER_COUNTS)[number];

export const MOVER_REGIONS = [
  { value: "us", label: "US", title: "US markets" },
  { value: "ca", label: "Canada", title: "TSX" },
] as const;

export type MoverRegion = (typeof MOVER_REGIONS)[number]["value"];

export type Movers = {
  gainers: StockMover[];
  losers: StockMover[];
  error: string | null;
};

export type MarketOverview = {
  currencies: { base: string; rates: CurrencyRate[]; error: string | null };
  crypto: { items: CryptoPrice[]; error: string | null };
  indices: { items: MarketIndex[]; error: string | null };
  movers: Movers;
};

export type StockProfile = {
  symbol: string;
  description: string | null;
  sector: string | null;
  industry: string | null;
  website: string | null;
};

export const CHART_RANGES = [
  { value: "1d", label: "1D" },
  { value: "5d", label: "5D" },
  { value: "1mo", label: "1M" },
  { value: "3mo", label: "3M" },
  { value: "6mo", label: "6M" },
  { value: "ytd", label: "YTD" },
  { value: "1y", label: "1Y" },
  { value: "5y", label: "5Y" },
  { value: "max", label: "Max" },
] as const;

export type ChartRange = (typeof CHART_RANGES)[number]["value"];

export type StockChart = {
  symbol: string;
  range: ChartRange;
  currency: string | null;
  previous_close: number | null;
  points: { t: number; o: number; h: number; l: number; c: number }[];
};

export type CurrencyTrend = {
  pair: string;
  rate: number;
  change_pct: number | null;
  points: { t: string; c: number }[];
};

export type CurrencyTrends = { items: CurrencyTrend[]; error: string | null };
