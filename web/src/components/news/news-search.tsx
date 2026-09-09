"use client";

import { useState, type FormEvent } from "react";

export function NewsSearch({
  query,
  recentSearches,
  onSearch,
  onClear,
  onClearRecent,
}: {
  query: string;
  recentSearches: string[];
  onSearch: (topic: string) => void;
  onClear: () => void;
  onClearRecent: () => void;
}) {
  const [input, setInput] = useState(query);

  function handleSubmit(event: FormEvent) {
    event.preventDefault();
    onSearch(input);
  }

  function handleClear() {
    setInput("");
    onClear();
  }

  function handleChip(topic: string) {
    setInput(topic);
    onSearch(topic);
  }

  return (
    <div className="mb-4 flex flex-col gap-2">
      <form onSubmit={handleSubmit} className="flex gap-2">
        <input
          type="search"
          placeholder="Search news by topic..."
          value={input}
          onChange={(e) => setInput(e.target.value)}
          className="w-full max-w-sm rounded border border-black/10 px-2 py-1 text-sm dark:border-white/10"
        />
        <button
          type="submit"
          className="rounded bg-foreground px-3 py-1 text-sm font-medium text-background"
        >
          Search
        </button>
        {query && (
          <button
            type="button"
            onClick={handleClear}
            className="rounded border border-black/10 px-3 py-1 text-sm text-zinc-500 hover:text-foreground dark:border-white/10"
          >
            Clear
          </button>
        )}
      </form>

      {recentSearches.length > 0 && (
        <div className="flex flex-wrap items-center gap-2 text-xs text-zinc-500">
          <span>Recent:</span>
          {recentSearches.map((topic) => (
            <button
              key={topic}
              type="button"
              onClick={() => handleChip(topic)}
              className="rounded-full border border-black/10 px-2 py-0.5 hover:text-foreground dark:border-white/10"
            >
              {topic}
            </button>
          ))}
          <button
            type="button"
            onClick={onClearRecent}
            className="underline hover:text-foreground"
          >
            clear
          </button>
        </div>
      )}
    </div>
  );
}
