"use client";

import Link from "next/link";
import { useState, type FormEvent, type ReactNode } from "react";
import { ApiError } from "@/lib/api";
import { searchFeeds, type FeedSearchResult } from "@/lib/news";
import type { NewsSource } from "@/lib/news-types";

function SourceDescription({
  source,
  children,
}: {
  source: NewsSource;
  children: ReactNode;
}) {
  if (!source.description) return <>{children}</>;
  return (
    <span className="group relative inline-block">
      {children}
      <span
        role="tooltip"
        className="pointer-events-none invisible absolute left-0 top-full z-20 mt-1 w-64 rounded border border-black/10 bg-background p-2 text-xs font-normal text-foreground opacity-0 shadow-lg transition-opacity group-focus-within:visible group-focus-within:opacity-100 group-hover:visible group-hover:opacity-100 dark:border-white/10"
      >
        {source.description}
      </span>
    </span>
  );
}

export function SourceManager({
  addedSources,
  catalogSources,
  canManage,
  selectedSourceId,
  onSelectSource,
  onAddExisting,
  onAddCustom,
  onRemove,
  onReorder,
  refreshingId,
  onRefresh,
}: {
  addedSources: NewsSource[];
  catalogSources: NewsSource[];
  canManage: boolean;
  selectedSourceId: number | null;
  onSelectSource: (sourceId: number | null) => void;
  onAddExisting: (sourceId: number) => Promise<void>;
  onAddCustom: (
    name: string,
    feedUrl: string,
    description: string | null,
  ) => Promise<void>;
  onRemove: (sourceId: number) => Promise<void>;
  onReorder: (sourceIds: number[]) => Promise<void>;
  refreshingId: number | null;
  onRefresh: (sourceId: number) => Promise<void>;
}) {
  const [customName, setCustomName] = useState("");
  const [customFeedUrl, setCustomFeedUrl] = useState("");
  const [customDescription, setCustomDescription] = useState<string | null>(
    null,
  );
  const [customError, setCustomError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [showAddPanel, setShowAddPanel] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [searchResults, setSearchResults] = useState<FeedSearchResult[] | null>(
    null,
  );
  const [searchError, setSearchError] = useState<string | null>(null);
  const [searching, setSearching] = useState(false);

  async function handleSearch(event: FormEvent) {
    event.preventDefault();
    setSearchError(null);
    setSearching(true);
    try {
      setSearchResults(await searchFeeds(searchQuery.trim()));
    } catch {
      setSearchResults(null);
      setSearchError("Feed search failed. Try again in a moment.");
    } finally {
      setSearching(false);
    }
  }

  function pickResult(result: FeedSearchResult) {
    setCustomName(result.title);
    setCustomFeedUrl(result.feed_url);
    setCustomDescription(result.description);
  }

  function handleRemove(source: NewsSource) {
    if (window.confirm(`Remove ${source.name} from your sources?`)) {
      onRemove(source.id);
    }
  }

  if (!canManage) {
    return (
      <div className="rounded border border-black/10 p-4 text-sm dark:border-white/10">
        <p className="mb-1 font-medium">Sources</p>
        <ul className="mb-3 flex flex-col gap-1">
          {addedSources.map((s) => (
            <li key={s.id}>
              <SourceDescription source={s}>
                <button
                  type="button"
                  onClick={() =>
                    onSelectSource(selectedSourceId === s.id ? null : s.id)
                  }
                  className={
                    selectedSourceId === s.id
                      ? "cursor-pointer font-medium text-foreground"
                      : "cursor-pointer text-zinc-500 hover:text-foreground"
                  }
                >
                  {s.name}
                </button>
              </SourceDescription>
            </li>
          ))}
        </ul>
        <Link href="/login" className="text-sm font-medium underline">
          Log in to customize your sources
        </Link>
      </div>
    );
  }

  function move(index: number, delta: number) {
    const next = [...addedSources];
    const target = index + delta;
    if (target < 0 || target >= next.length) return;
    [next[index], next[target]] = [next[target], next[index]];
    onReorder(next.map((s) => s.id));
  }

  async function handleAddCustom(event: FormEvent) {
    event.preventDefault();
    setCustomError(null);
    setSubmitting(true);
    try {
      await onAddCustom(customName, customFeedUrl, customDescription);
      setCustomName("");
      setCustomFeedUrl("");
      setCustomDescription(null);
    } catch (err) {
      if (err instanceof ApiError) {
        const message =
          typeof err.data === "object" && err.data && "message" in err.data
            ? String((err.data as { message: unknown }).message)
            : "Couldn't add that source.";
        setCustomError(message);
      } else {
        setCustomError("Couldn't add that source.");
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="flex flex-col gap-4">
      <div className="rounded border border-black/10 p-4 dark:border-white/10">
        <div className="mb-2 flex items-center justify-between">
          <p className="text-sm font-medium">Your sources</p>
          <button
            type="button"
            onClick={() => setShowAddPanel((v) => !v)}
            className="text-sm font-medium text-foreground hover:underline"
          >
            {showAddPanel ? "Close" : "+ Add source"}
          </button>
        </div>
        <ul className="flex flex-col gap-1">
          {addedSources.map((source, index) => (
            <li
              key={source.id}
              className="flex items-center justify-between gap-2 text-sm"
            >
              <SourceDescription source={source}>
                <button
                  type="button"
                  onClick={() =>
                    onSelectSource(
                      selectedSourceId === source.id ? null : source.id,
                    )
                  }
                  className={
                    selectedSourceId === source.id
                      ? "cursor-pointer font-medium text-foreground"
                      : "cursor-pointer hover:text-foreground"
                  }
                >
                  {source.name}
                </button>
              </SourceDescription>
              <span className="flex items-center gap-1">
                <button
                  type="button"
                  aria-label={`Refresh ${source.name}`}
                  title={`Refresh ${source.name}`}
                  disabled={refreshingId !== null}
                  onClick={() => onRefresh(source.id)}
                  className={`text-zinc-500 hover:text-foreground disabled:opacity-50 ${
                    refreshingId === source.id ? "animate-spin" : ""
                  }`}
                >
                  ↻
                </button>
                <button
                  type="button"
                  aria-label={`Move ${source.name} up`}
                  disabled={index === 0}
                  onClick={() => move(index, -1)}
                  className="text-zinc-500 hover:text-foreground disabled:opacity-30"
                >
                  ↑
                </button>
                <button
                  type="button"
                  aria-label={`Move ${source.name} down`}
                  disabled={index === addedSources.length - 1}
                  onClick={() => move(index, 1)}
                  className="text-zinc-500 hover:text-foreground disabled:opacity-30"
                >
                  ↓
                </button>
                <button
                  type="button"
                  aria-label={`Remove ${source.name}`}
                  onClick={() => handleRemove(source)}
                  className="ml-1 text-zinc-500 hover:text-red-600"
                >
                  ×
                </button>
              </span>
            </li>
          ))}
          {addedSources.length === 0 && (
            <li className="text-sm text-zinc-500">No sources added.</li>
          )}
        </ul>
      </div>

      {showAddPanel && (
        <>
          {catalogSources.length > 0 && (
            <div className="rounded border border-black/10 p-4 dark:border-white/10">
              <p className="mb-2 text-sm font-medium">Catalog sources</p>
              <ul className="flex flex-col gap-1">
                {catalogSources.map((source) => (
                  <li key={source.id} className="text-sm">
                    <SourceDescription source={source}>
                      <button
                        type="button"
                        onClick={() => onAddExisting(source.id)}
                        className="cursor-pointer text-zinc-500 hover:text-foreground"
                      >
                        {source.name}
                      </button>
                    </SourceDescription>
                  </li>
                ))}
              </ul>
            </div>
          )}

          <form
            onSubmit={handleAddCustom}
            className="rounded border border-black/10 p-4 dark:border-white/10"
          >
            <p className="mb-2 text-sm font-medium">Add a custom RSS source</p>
            <div className="mb-3 flex flex-col gap-2">
              <div className="flex gap-2">
                <input
                  type="search"
                  placeholder="Search feeds by name or topic"
                  aria-label="Search feeds by name or topic"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  onKeyDown={(e) => {
                    if (e.key === "Enter") handleSearch(e);
                  }}
                  className="min-w-0 flex-1 rounded border border-black/10 px-2 py-1 text-sm dark:border-white/10"
                />
                <button
                  type="button"
                  disabled={searching || searchQuery.trim().length < 2}
                  onClick={handleSearch}
                  className="rounded border border-black/10 px-3 py-1 text-sm font-medium disabled:opacity-50 dark:border-white/10"
                >
                  {searching ? "Searching…" : "Search"}
                </button>
              </div>
              {searchError && (
                <p className="text-xs text-red-600">{searchError}</p>
              )}
              {searchResults !== null && searchResults.length === 0 && (
                <p className="text-xs text-zinc-500">No feeds found.</p>
              )}
              {searchResults !== null && searchResults.length > 0 && (
                <ul className="flex max-h-64 flex-col gap-1 overflow-y-auto">
                  {searchResults.map((r) => (
                    <li key={r.feed_url}>
                      <button
                        type="button"
                        onClick={() => pickResult(r)}
                        className={`w-full cursor-pointer rounded border px-2 py-1 text-left text-sm hover:bg-black/5 dark:hover:bg-white/5 ${
                          customFeedUrl === r.feed_url
                            ? "border-foreground"
                            : "border-black/10 dark:border-white/10"
                        }`}
                      >
                        <span className="font-medium">{r.title}</span>
                        {r.subscribers > 0 && (
                          <span className="ml-2 text-xs text-zinc-500">
                            {r.subscribers.toLocaleString()} subscribers
                          </span>
                        )}
                        {r.description && (
                          <span className="block text-xs text-zinc-500">
                            {r.description}
                          </span>
                        )}
                      </button>
                    </li>
                  ))}
                </ul>
              )}
            </div>
            <div className="flex flex-col gap-2">
              <input
                type="text"
                placeholder="Name"
                required
                value={customName}
                onChange={(e) => setCustomName(e.target.value)}
                className="rounded border border-black/10 px-2 py-1 text-sm dark:border-white/10"
              />
              <input
                type="url"
                placeholder="Feed URL"
                required
                value={customFeedUrl}
                onChange={(e) => setCustomFeedUrl(e.target.value)}
                className="rounded border border-black/10 px-2 py-1 text-sm dark:border-white/10"
              />
              {customError && (
                <p className="text-xs text-red-600">{customError}</p>
              )}
              <button
                type="submit"
                disabled={submitting}
                className="self-start rounded bg-foreground px-3 py-1 text-sm font-medium text-background disabled:opacity-50"
              >
                Add source
              </button>
            </div>
          </form>
        </>
      )}
    </div>
  );
}
