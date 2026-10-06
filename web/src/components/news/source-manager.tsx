"use client";

import Link from "next/link";
import { useState, type FormEvent } from "react";
import { ApiError } from "@/lib/api";
import type { NewsSource } from "@/lib/news-types";

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
}: {
  addedSources: NewsSource[];
  catalogSources: NewsSource[];
  canManage: boolean;
  selectedSourceId: number | null;
  onSelectSource: (sourceId: number | null) => void;
  onAddExisting: (sourceId: number) => Promise<void>;
  onAddCustom: (name: string, feedUrl: string) => Promise<void>;
  onRemove: (sourceId: number) => Promise<void>;
  onReorder: (sourceIds: number[]) => Promise<void>;
}) {
  const [customName, setCustomName] = useState("");
  const [customFeedUrl, setCustomFeedUrl] = useState("");
  const [customError, setCustomError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [showAddPanel, setShowAddPanel] = useState(false);

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
      await onAddCustom(customName, customFeedUrl);
      setCustomName("");
      setCustomFeedUrl("");
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
              <span className="flex items-center gap-1">
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
                    <button
                      type="button"
                      onClick={() => onAddExisting(source.id)}
                      className="cursor-pointer text-zinc-500 hover:text-foreground"
                    >
                      {source.name}
                    </button>
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
