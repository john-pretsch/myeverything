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
};

export type MarketOverview = {
  currencies: { base: string; rates: CurrencyRate[]; error: string | null };
  crypto: { items: CryptoPrice[]; error: string | null };
  indices: { items: MarketIndex[]; error: string | null };
  movers: {
    gainers: StockMover[];
    losers: StockMover[];
    error: string | null;
  };
};
