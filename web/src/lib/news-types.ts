export type NewsTopic = "local" | "hackery" | "world";

export const NEWS_TOPICS: { value: NewsTopic; label: string }[] = [
  { value: "local", label: "Local" },
  { value: "hackery", label: "Hackery" },
  { value: "world", label: "World" },
];

export type NewsSource = {
  id: number;
  name: string;
  site_url: string;
  feed_url: string;
  is_default: boolean;
  topic: NewsTopic | null;
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
  source: { id: number; name: string };
  tags: Tag[];
};
