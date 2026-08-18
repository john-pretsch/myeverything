export type NewsSource = {
  id: number;
  name: string;
  site_url: string;
  feed_url: string;
  is_default: boolean;
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
