import { apiFetch } from "./api";
import type { AdminTopic, Topic } from "./news-types";

export function getAdminTopics(): Promise<AdminTopic[]> {
  return apiFetch<AdminTopic[]>("/api/admin/topics");
}

export function createTopic(name: string): Promise<Topic> {
  return apiFetch<Topic>("/api/admin/topics", {
    method: "POST",
    body: { name },
  });
}

export function renameTopic(topicId: number, name: string): Promise<Topic> {
  return apiFetch<Topic>(`/api/admin/topics/${topicId}`, {
    method: "PATCH",
    body: { name },
  });
}

export function deleteTopic(topicId: number): Promise<void> {
  return apiFetch<void>(`/api/admin/topics/${topicId}`, { method: "DELETE" });
}

export function attachSourceToTopic(
  topicId: number,
  sourceId: number,
): Promise<void> {
  return apiFetch<void>(`/api/admin/topics/${topicId}/sources`, {
    method: "POST",
    body: { news_source_id: sourceId },
  });
}

export function detachSourceFromTopic(
  topicId: number,
  sourceId: number,
): Promise<void> {
  return apiFetch<void>(`/api/admin/topics/${topicId}/sources/${sourceId}`, {
    method: "DELETE",
  });
}
