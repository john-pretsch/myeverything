import { useState, type FormEvent } from "react";
import type { NewsArticle } from "@/lib/news-types";
import { topicColor } from "@/lib/topic-colors";

function formatPublished(published_at: string | null): string {
  if (!published_at) return "";
  const date = new Date(published_at);
  const diffMs = Date.now() - date.getTime();
  const diffHours = Math.round(diffMs / 3_600_000);

  if (diffHours < 1) return "just now";
  if (diffHours < 24) return `${diffHours}h ago`;
  return `${Math.round(diffHours / 24)}d ago`;
}

export function ArticleCard({
  article,
  canTag,
  onApplyTag,
  onDetachTag,
  onVoteTag,
}: {
  article: NewsArticle;
  canTag: boolean;
  onApplyTag: (articleId: number, name: string) => Promise<void>;
  onDetachTag: (articleId: number, tagId: number) => void;
  onVoteTag: (tagId: number, direction: 1 | -1) => void;
}) {
  const [newTag, setNewTag] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const primaryTopic = article.source.topics[0];
  const accentBorder = primaryTopic ? topicColor(primaryTopic.id).border : "";

  async function handleAddTag(event: FormEvent) {
    event.preventDefault();
    const name = newTag.trim();
    if (name === "") return;
    setSubmitting(true);
    try {
      await onApplyTag(article.id, name);
      setNewTag("");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <li
      className={`flex gap-4 rounded border border-black/10 p-4 dark:border-white/10 ${accentBorder ? `border-l-4 ${accentBorder}` : ""}`}
    >
      <div className="flex-1">
        <div className="mb-1 flex items-center gap-2 text-xs text-zinc-500">
          <span className="font-medium">{article.source.name}</span>
          {article.published_at && (
            <span>{formatPublished(article.published_at)}</span>
          )}
        </div>
        <a
          href={article.url}
          target="_blank"
          rel="noopener noreferrer"
          className="font-medium hover:underline"
        >
          {article.title}
        </a>
        {article.summary && (
          <p className="mt-1 line-clamp-2 text-sm text-zinc-500">
            {article.summary}
          </p>
        )}
        {(article.tags.length > 0 || canTag) && (
          <div className="mt-2 flex flex-wrap items-center gap-1">
            {article.tags.map((tag) => (
              <span
                key={tag.id}
                className="flex items-center gap-1 rounded-full border border-black/10 px-2 py-0.5 text-xs text-zinc-500 dark:border-white/10"
              >
                {canTag && (
                  <button
                    type="button"
                    aria-label={`Upvote tag ${tag.name}`}
                    onClick={() => onVoteTag(tag.id, 1)}
                    className={
                      tag.viewer_vote === 1
                        ? "font-medium text-foreground"
                        : "hover:text-foreground"
                    }
                  >
                    ▲
                  </button>
                )}
                {tag.name}
                {canTag && (
                  <button
                    type="button"
                    aria-label={`Downvote tag ${tag.name}`}
                    onClick={() => onVoteTag(tag.id, -1)}
                    className={
                      tag.viewer_vote === -1
                        ? "font-medium text-foreground"
                        : "hover:text-foreground"
                    }
                  >
                    ▼
                  </button>
                )}
                {canTag && (
                  <button
                    type="button"
                    aria-label={`Remove tag ${tag.name}`}
                    onClick={() => onDetachTag(article.id, tag.id)}
                    className="hover:text-red-600"
                  >
                    ×
                  </button>
                )}
              </span>
            ))}
            {canTag && (
              <form onSubmit={handleAddTag} className="inline-flex">
                <input
                  type="text"
                  placeholder="+ tag"
                  value={newTag}
                  onChange={(e) => setNewTag(e.target.value)}
                  disabled={submitting}
                  className="w-16 rounded-full border border-dashed border-black/10 px-2 py-0.5 text-xs disabled:opacity-50 dark:border-white/20"
                />
              </form>
            )}
          </div>
        )}
      </div>
    </li>
  );
}
