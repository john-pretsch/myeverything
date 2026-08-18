import type { NewsArticle } from "@/lib/news-types";

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
  canRate,
  onFeedback,
}: {
  article: NewsArticle;
  canRate: boolean;
  onFeedback: (articleId: number, direction: 1 | -1) => void;
}) {
  return (
    <li className="flex gap-4 rounded border border-black/10 p-4 dark:border-white/10">
      {article.image_url && (
        // eslint-disable-next-line @next/next/no-img-element
        <img
          src={article.image_url}
          alt=""
          className="hidden h-20 w-28 flex-shrink-0 rounded object-cover sm:block"
        />
      )}
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
        {canRate && (
          <div className="mt-2 flex gap-2 text-xs">
            <button
              type="button"
              onClick={() => onFeedback(article.id, 1)}
              className={`rounded border px-2 py-1 ${
                article.feedback === 1
                  ? "border-foreground font-medium"
                  : "border-black/10 text-zinc-500 hover:text-foreground dark:border-white/10"
              }`}
            >
              More like this
            </button>
            <button
              type="button"
              onClick={() => onFeedback(article.id, -1)}
              className={`rounded border px-2 py-1 ${
                article.feedback === -1
                  ? "border-foreground font-medium"
                  : "border-black/10 text-zinc-500 hover:text-foreground dark:border-white/10"
              }`}
            >
              Less like this
            </button>
          </div>
        )}
      </div>
    </li>
  );
}
