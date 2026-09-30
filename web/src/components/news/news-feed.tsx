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
  getTopics,
  removeSource,
  reorderSources,
  voteTag,
} from "@/lib/news";
import {
  type NewsArticle,
  type NewsSource,
  type Tag,
  type Topic,
} from "@/lib/news-types";
import {
  addRecentSearch,
  clearRecentSearches,
  getRecentSearches,
} from "@/lib/recent-searches";
import { topicColor } from "@/lib/topic-colors";
import { ArticleCard } from "./article-card";
import { NewsSearch } from "./news-search";
import { SourceManager } from "./source-manager";
import { TagManager } from "./tag-manager";

export function NewsFeed() {
  const { status } = useAuth();
  const [sources, setSources] = useState<NewsSource[]>([]);
  const [articles, setArticles] = useState<NewsArticle[]>([]);
  const [tags, setTags] = useState<Tag[]>([]);
  const [topics, setTopics] = useState<Topic[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [requestId, setRequestId] = useState(0);
  const [resolvedId, setResolvedId] = useState(-1);
  const [searchQuery, setSearchQuery] = useState("");
  const [recentSearches, setRecentSearches] = useState<string[]>([]);
  const [topicId, setTopicId] = useState<number | null>(null);
  const [selectedSourceId, setSelectedSourceId] = useState<number | null>(
    null,
  );
  const [selectedTagId, setSelectedTagId] = useState<number | null>(null);
  const loading = requestId !== resolvedId;

  useEffect(() => {
    setRecentSearches(getRecentSearches());
  }, []);

  useEffect(() => {
    if (status === "loading") return;

    let ignore = false;

    Promise.all([
      getSources(),
      getFeed(50, searchQuery, topicId, selectedSourceId, selectedTagId),
      getTags(),
      getTopics(),
    ])
      .then(([sourcesData, articlesData, tagsData, topicsData]) => {
        if (ignore) return;
        setSources(sourcesData);
        setArticles(articlesData);
        setTags(tagsData);
        setTopics(topicsData);
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
  }, [status, requestId, searchQuery, topicId, selectedSourceId, selectedTagId]);

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

  function handleSelectTag(tagId: number | null) {
    setSelectedTagId(tagId);
    window.scrollTo({ top: 0, behavior: "smooth" });
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
            onClick={() => setTopicId(null)}
            className={`rounded px-3 py-1.5 text-sm font-medium ${
              topicId === null
                ? "bg-foreground text-background"
                : "border border-black/10 text-zinc-500 dark:border-white/10"
            }`}
          >
            All
          </button>
          {topics.map((t) => (
            <button
              key={t.id}
              type="button"
              onClick={() => setTopicId(t.id)}
              className={`flex items-center gap-1.5 rounded px-3 py-1.5 text-sm font-medium ${
                topicId === t.id
                  ? "bg-foreground text-background"
                  : "border border-black/10 text-zinc-500 dark:border-white/10"
              }`}
            >
              <span
                className={`h-2 w-2 rounded-full ${topicColor(t.id).dot}`}
              />
              {t.name}
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
        {selectedSourceId !== null && (
          <p className="mb-4 text-sm text-zinc-500">
            Showing{" "}
            {sources.find((s) => s.id === selectedSourceId)?.name ??
              "this source"}{" "}
            only.{" "}
            <button
              type="button"
              onClick={() => setSelectedSourceId(null)}
              className="font-medium underline"
            >
              Clear
            </button>
          </p>
        )}
        {selectedTagId !== null && (
          <p className="mb-4 text-sm text-zinc-500">
            Showing articles tagged{" "}
            {tags.find((t) => t.id === selectedTagId)?.name ?? "this tag"}.{" "}
            <button
              type="button"
              onClick={() => setSelectedTagId(null)}
              className="font-medium underline"
            >
              Clear
            </button>
          </p>
        )}
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
                showTopicDot={topicId === null}
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
              selectedSourceId={selectedSourceId}
              onSelectSource={setSelectedSourceId}
              onAddExisting={handleAddExisting}
              onAddCustom={handleAddCustom}
              onRemove={handleRemove}
              onReorder={handleReorder}
            />
            <TagManager
              tags={tags}
              canManage={status === "authenticated"}
              selectedTagId={selectedTagId}
              onSelectTag={handleSelectTag}
              onDelete={handleDeleteTag}
            />
          </>
        )}
      </div>
    </div>
  );
}
