"use client";

import { useEffect, useState } from "react";
import { useAuth } from "@/lib/auth-context";
import {
  addCustomSource,
  addExistingSource,
  applyTagToArticle,
  clearTagVote,
  deleteTag,
  detachTagFromArticle,
  getFeed,
  getSources,
  getTags,
  removeSource,
  reorderSources,
  voteTag,
} from "@/lib/news";
import {
  NEWS_TOPICS,
  type NewsArticle,
  type NewsSource,
  type Tag,
} from "@/lib/news-types";
import {
  addRecentSearch,
  clearRecentSearches,
  getRecentSearches,
} from "@/lib/recent-searches";
import { ArticleCard } from "./article-card";
import { NewsSearch } from "./news-search";
import { SourceManager } from "./source-manager";
import { TagManager } from "./tag-manager";

export function NewsFeed() {
  const { status } = useAuth();
  const [sources, setSources] = useState<NewsSource[]>([]);
  const [articles, setArticles] = useState<NewsArticle[]>([]);
  const [tags, setTags] = useState<Tag[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [requestId, setRequestId] = useState(0);
  const [resolvedId, setResolvedId] = useState(-1);
  const [searchQuery, setSearchQuery] = useState("");
  const [recentSearches, setRecentSearches] = useState<string[]>([]);
  const [topic, setTopic] = useState("");
  const loading = requestId !== resolvedId;

  useEffect(() => {
    setRecentSearches(getRecentSearches());
  }, []);

  useEffect(() => {
    if (status === "loading") return;

    let ignore = false;

    Promise.all([getSources(), getFeed(50, searchQuery, topic), getTags()])
      .then(([sourcesData, articlesData, tagsData]) => {
        if (ignore) return;
        setSources(sourcesData);
        setArticles(articlesData);
        setTags(tagsData);
        setError(null);
      })
      .catch(() => {
        if (!ignore) setError("Couldn't load the news feed.");
      })
      .finally(() => {
        if (!ignore) setResolvedId(requestId);
      });

    return () => {
      ignore = true;
    };
  }, [status, requestId, searchQuery, topic]);

  function reload() {
    setRequestId((n) => n + 1);
  }

  function handleSearch(topic: string) {
    const trimmed = topic.trim();
    if (trimmed !== "") setRecentSearches(addRecentSearch(trimmed));
    setSearchQuery(trimmed);
  }

  function handleClearSearch() {
    setSearchQuery("");
  }

  function handleClearRecent() {
    setRecentSearches(clearRecentSearches());
  }

  async function handleAddExisting(sourceId: number) {
    await addExistingSource(sourceId);
    reload();
  }

  async function handleAddCustom(name: string, feedUrl: string) {
    await addCustomSource(name, feedUrl);
    reload();
  }

  async function handleRemove(sourceId: number) {
    await removeSource(sourceId);
    reload();
  }

  async function handleReorder(sourceIds: number[]) {
    const updated = await reorderSources(sourceIds);
    setSources(updated);
  }

  async function handleVoteTag(tagId: number, direction: 1 | -1) {
    const currentVote = articles
      .flatMap((a) => a.tags)
      .find((t) => t.id === tagId)?.viewer_vote;
    if (currentVote === direction) {
      await clearTagVote(tagId);
    } else {
      await voteTag(tagId, direction);
    }
    reload();
  }

  async function handleApplyTag(articleId: number, name: string) {
    await applyTagToArticle(articleId, { name });
    reload();
  }

  async function handleDetachTag(articleId: number, tagId: number) {
    await detachTagFromArticle(articleId, tagId);
    setArticles((prev) =>
      prev.map((a) =>
        a.id === articleId
          ? { ...a, tags: a.tags.filter((t) => t.id !== tagId) }
          : a,
      ),
    );
  }

  async function handleDeleteTag(tagId: number) {
    await deleteTag(tagId);
    setTags((prev) => prev.filter((t) => t.id !== tagId));
    setArticles((prev) =>
      prev.map((a) => ({ ...a, tags: a.tags.filter((t) => t.id !== tagId) })),
    );
  }

  const addedSources = sources.filter((s) => s.is_added);
  const catalogSources = sources.filter((s) => !s.is_added);

  return (
    <div className="flex flex-col gap-6 lg:flex-row">
      <div className="flex-1">
        <h1 className="mb-4 text-xl font-semibold">News Feed</h1>
        <div className="mb-4 flex gap-1">
          <button
            type="button"
            onClick={() => setTopic("")}
            className={`rounded px-3 py-1.5 text-sm font-medium ${
              topic === ""
                ? "bg-foreground text-background"
                : "border border-black/10 text-zinc-500 dark:border-white/10"
            }`}
          >
            All
          </button>
          {NEWS_TOPICS.map((t) => (
            <button
              key={t.value}
              type="button"
              onClick={() => setTopic(t.value)}
              className={`rounded px-3 py-1.5 text-sm font-medium ${
                topic === t.value
                  ? "bg-foreground text-background"
                  : "border border-black/10 text-zinc-500 dark:border-white/10"
              }`}
            >
              {t.label}
            </button>
          ))}
        </div>
        <NewsSearch
          query={searchQuery}
          recentSearches={recentSearches}
          onSearch={handleSearch}
          onClear={handleClearSearch}
          onClearRecent={handleClearRecent}
        />
        {error && <p className="mb-4 text-sm text-red-600">{error}</p>}
        {loading ? (
          <p className="text-sm text-zinc-500">Loading...</p>
        ) : articles.length === 0 ? (
          <p className="text-sm text-zinc-500">
            {searchQuery
              ? `No articles matching "${searchQuery}".`
              : "No articles yet."}
          </p>
        ) : (
          <ul className="flex flex-col gap-4">
            {articles.map((article) => (
              <ArticleCard
                key={article.id}
                article={article}
                canTag={status === "authenticated"}
                onApplyTag={handleApplyTag}
                onDetachTag={handleDetachTag}
                onVoteTag={handleVoteTag}
              />
            ))}
          </ul>
        )}
      </div>
      <div className="flex w-full flex-col gap-4 lg:w-72 lg:flex-shrink-0">
        {!loading && (
          <>
            <SourceManager
              addedSources={addedSources}
              catalogSources={catalogSources}
              canManage={status === "authenticated"}
              onAddExisting={handleAddExisting}
              onAddCustom={handleAddCustom}
              onRemove={handleRemove}
              onReorder={handleReorder}
            />
            <TagManager
              tags={tags}
              canManage={status === "authenticated"}
              onDelete={handleDeleteTag}
            />
          </>
        )}
      </div>
    </div>
  );
}
