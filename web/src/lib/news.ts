import { apiFetch } from "./api";
import type { NewsArticle, NewsSource, Tag } from "./news-types";

export async function getSources(): Promise<NewsSource[]> {
  const res = await apiFetch<{ data: NewsSource[] }>("/api/news/sources");
  return res.data;
}

export async function getFeed(
  limit = 50,
  q = "",
  topic = "",
): Promise<NewsArticle[]> {
  const params = new URLSearchParams({ limit: String(limit) });
  if (q.trim() !== "") params.set("q", q.trim());
  if (topic.trim() !== "") params.set("topic", topic.trim());
  const res = await apiFetch<{ data: NewsArticle[] }>(
    `/api/news/feed?${params.toString()}`,
  );
  return res.data;
}

export async function addExistingSource(
  newsSourceId: number,
): Promise<NewsSource> {
  const res = await apiFetch<{ data: NewsSource }>("/api/news/sources", {
    method: "POST",
    body: { news_source_id: newsSourceId },
  });
  return res.data;
}

export async function addCustomSource(
  name: string,
  feedUrl: string,
): Promise<NewsSource> {
  const res = await apiFetch<{ data: NewsSource }>("/api/news/sources", {
    method: "POST",
    body: { name, feed_url: feedUrl },
  });
  return res.data;
}

export function removeSource(sourceId: number): Promise<void> {
  return apiFetch<void>(`/api/news/sources/${sourceId}`, { method: "DELETE" });
}

export async function reorderSources(
  sourceIds: number[],
): Promise<NewsSource[]> {
  const res = await apiFetch<{ data: NewsSource[] }>(
    "/api/news/sources/reorder",
    { method: "PATCH", body: { source_ids: sourceIds } },
  );
  return res.data;
}

export async function getTags(): Promise<Tag[]> {
  const res = await apiFetch<{ data: Tag[] }>("/api/news/tags");
  return res.data;
}

export async function createTag(name: string): Promise<Tag> {
  const res = await apiFetch<{ data: Tag }>("/api/news/tags", {
    method: "POST",
    body: { name },
  });
  return res.data;
}

export function deleteTag(tagId: number): Promise<void> {
  return apiFetch<void>(`/api/news/tags/${tagId}`, { method: "DELETE" });
}

export async function applyTagToArticle(
  articleId: number,
  tag: { tagId: number } | { name: string },
): Promise<Tag> {
  const body = "tagId" in tag ? { tag_id: tag.tagId } : { name: tag.name };
  const res = await apiFetch<{ data: Tag }>(
    `/api/news/articles/${articleId}/tags`,
    { method: "POST", body },
  );
  return res.data;
}

export function detachTagFromArticle(
  articleId: number,
  tagId: number,
): Promise<void> {
  return apiFetch<void>(`/api/news/articles/${articleId}/tags/${tagId}`, {
    method: "DELETE",
  });
}

export function voteTag(
  tagId: number,
  direction: 1 | -1,
): Promise<{ direction: 1 | -1 }> {
  return apiFetch(`/api/news/tags/${tagId}/vote`, {
    method: "POST",
    body: { direction },
  });
}

export function clearTagVote(tagId: number): Promise<void> {
  return apiFetch<void>(`/api/news/tags/${tagId}/vote`, {
    method: "DELETE",
  });
}
