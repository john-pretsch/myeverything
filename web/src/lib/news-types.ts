export type Topic = {
  id: number;
  name: string;
  source_count?: number;
};

export type AdminTopic = Topic & {
  source_count: number;
  sources: { id: number; name: string }[];
};

export type NewsSource = {
  id: number;
  name: string;
  site_url: string;
  feed_url: string;
  is_default: boolean;
  topics: Topic[];
  is_added: boolean;
  position: number | null;
};

export type Tag = {
  id: number;
  name: string;
  usage_count?: number;
  viewer_vote?: 1 | -1 | null;
};

export type NewsArticle = {
  id: number;
  title: string;
  url: string;
  summary: string | null;
  image_url: string | null;
  published_at: string | null;
  source: { id: number; name: string; topics: Topic[] };
  tags: Tag[];
};
