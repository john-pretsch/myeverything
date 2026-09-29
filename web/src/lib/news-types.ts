export type NewsTopic = "local" | "hackery";

export const NEWS_TOPICS: { value: NewsTopic; label: string }[] = [
  { value: "local", label: "Local" },
  { value: "hackery", label: "Hackery" },
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

export type NewsArticle = {
  id: number;
  title: string;
  url: string;
  summary: string | null;
  image_url: string | null;
  published_at: string | null;
  source: { id: number; name: string };
  feedback?: 1 | -1;
};
