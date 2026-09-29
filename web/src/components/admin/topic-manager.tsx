"use client";

import { useEffect, useState, type FormEvent } from "react";
import {
  attachSourceToTopic,
  createTopic,
  deleteTopic,
  detachSourceFromTopic,
  getAdminTopics,
  renameTopic,
} from "@/lib/admin";
import { ApiError } from "@/lib/api";
import { getSources } from "@/lib/news";
import type { AdminTopic, NewsSource } from "@/lib/news-types";

function errorMessage(err: unknown): string {
  if (err instanceof ApiError) {
    const data = err.data;
    if (typeof data === "object" && data && "message" in data) {
      return String((data as { message: unknown }).message);
    }
  }
  return "Something went wrong.";
}

function TopicCard({
  topic,
  allSources,
  onRename,
  onDelete,
  onAttach,
  onDetach,
}: {
  topic: AdminTopic;
  allSources: NewsSource[];
  onRename: (topicId: number, name: string) => Promise<void>;
  onDelete: (topicId: number) => Promise<void>;
  onAttach: (topicId: number, sourceId: number) => Promise<void>;
  onDetach: (topicId: number, sourceId: number) => Promise<void>;
}) {
  const [name, setName] = useState(topic.name);
  const [addSourceId, setAddSourceId] = useState("");
  const [error, setError] = useState<string | null>(null);

  const assignedIds = new Set(topic.sources.map((s) => s.id));
  const availableSources = allSources.filter((s) => !assignedIds.has(s.id));

  async function handleRename() {
    if (name.trim() === "" || name === topic.name) return;
    setError(null);
    try {
      await onRename(topic.id, name.trim());
    } catch (err) {
      setError(errorMessage(err));
    }
  }

  async function handleAdd(event: FormEvent) {
    event.preventDefault();
    if (addSourceId === "") return;
    setError(null);
    try {
      await onAttach(topic.id, Number(addSourceId));
      setAddSourceId("");
    } catch (err) {
      setError(errorMessage(err));
    }
  }

  return (
    <div className="rounded border border-black/10 p-4 dark:border-white/10">
      <div className="mb-2 flex items-center gap-2">
        <input
          type="text"
          value={name}
          onChange={(e) => setName(e.target.value)}
          className="flex-1 rounded border border-black/10 px-2 py-1 text-sm dark:border-white/10"
        />
        <button
          type="button"
          onClick={handleRename}
          disabled={name.trim() === "" || name === topic.name}
          className="rounded border border-black/10 px-2 py-1 text-xs font-medium disabled:opacity-50 dark:border-white/10"
        >
          Rename
        </button>
        <button
          type="button"
          onClick={() => onDelete(topic.id)}
          className="text-zinc-500 hover:text-red-600"
          aria-label={`Delete topic ${topic.name}`}
        >
          ×
        </button>
      </div>

      {error && <p className="mb-2 text-xs text-red-600">{error}</p>}

      <p className="mb-1 text-xs text-zinc-500">
        {topic.source_count} source{topic.source_count === 1 ? "" : "s"}
      </p>
      <ul className="mb-2 flex flex-col gap-1">
        {topic.sources.map((source) => (
          <li
            key={source.id}
            className="flex items-center justify-between text-sm"
          >
            <span>{source.name}</span>
            <button
              type="button"
              aria-label={`Remove ${source.name} from ${topic.name}`}
              onClick={() => onDetach(topic.id, source.id)}
              className="text-zinc-500 hover:text-red-600"
            >
              ×
            </button>
          </li>
        ))}
        {topic.sources.length === 0 && (
          <li className="text-sm text-zinc-500">No sources assigned.</li>
        )}
      </ul>

      {availableSources.length > 0 && (
        <form onSubmit={handleAdd} className="flex gap-2">
          <select
            value={addSourceId}
            onChange={(e) => setAddSourceId(e.target.value)}
            className="flex-1 rounded border border-black/10 px-2 py-1 text-sm dark:border-white/10"
          >
            <option value="">Add a source...</option>
            {availableSources.map((source) => (
              <option key={source.id} value={source.id}>
                {source.name}
              </option>
            ))}
          </select>
          <button
            type="submit"
            disabled={addSourceId === ""}
            className="rounded border border-black/10 px-2 py-1 text-xs font-medium disabled:opacity-50 dark:border-white/10"
          >
            Add
          </button>
        </form>
      )}
    </div>
  );
}

export function TopicManager() {
  const [topics, setTopics] = useState<AdminTopic[]>([]);
  const [allSources, setAllSources] = useState<NewsSource[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [newTopicName, setNewTopicName] = useState("");
  const [creating, setCreating] = useState(false);

  function load() {
    Promise.all([getAdminTopics(), getSources()])
      .then(([topicsData, sourcesData]) => {
        setTopics(topicsData);
        setAllSources(sourcesData);
        setError(null);
      })
      .catch(() => setError("Couldn't load topics."))
      .finally(() => setLoading(false));
  }

  useEffect(() => {
    load();
  }, []);

  async function handleCreate(event: FormEvent) {
    event.preventDefault();
    const name = newTopicName.trim();
    if (name === "") return;
    setCreating(true);
    setError(null);
    try {
      await createTopic(name);
      setNewTopicName("");
      load();
    } catch (err) {
      setError(errorMessage(err));
    } finally {
      setCreating(false);
    }
  }

  async function handleRename(topicId: number, name: string) {
    await renameTopic(topicId, name);
    load();
  }

  async function handleDelete(topicId: number) {
    await deleteTopic(topicId);
    load();
  }

  async function handleAttach(topicId: number, sourceId: number) {
    await attachSourceToTopic(topicId, sourceId);
    load();
  }

  async function handleDetach(topicId: number, sourceId: number) {
    await detachSourceFromTopic(topicId, sourceId);
    load();
  }

  return (
    <div>
      <h1 className="mb-4 text-xl font-semibold">Admin: Topics</h1>

      <form
        onSubmit={handleCreate}
        className="mb-6 flex gap-2 rounded border border-black/10 p-4 dark:border-white/10"
      >
        <input
          type="text"
          placeholder="New topic name"
          value={newTopicName}
          onChange={(e) => setNewTopicName(e.target.value)}
          disabled={creating}
          className="flex-1 rounded border border-black/10 px-2 py-1 text-sm disabled:opacity-50 dark:border-white/10"
        />
        <button
          type="submit"
          disabled={creating || newTopicName.trim() === ""}
          className="rounded bg-foreground px-3 py-1 text-sm font-medium text-background disabled:opacity-50"
        >
          Create topic
        </button>
      </form>

      {error && <p className="mb-4 text-sm text-red-600">{error}</p>}

      {loading ? (
        <p className="text-sm text-zinc-500">Loading...</p>
      ) : (
        <div className="flex flex-col gap-4">
          {topics.map((topic) => (
            <TopicCard
              key={topic.id}
              topic={topic}
              allSources={allSources}
              onRename={handleRename}
              onDelete={handleDelete}
              onAttach={handleAttach}
              onDetach={handleDetach}
            />
          ))}
          {topics.length === 0 && (
            <p className="text-sm text-zinc-500">No topics yet.</p>
          )}
        </div>
      )}
    </div>
  );
}
