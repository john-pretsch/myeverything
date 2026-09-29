import Link from "next/link";
import type { Tag } from "@/lib/news-types";

export function TagManager({
  tags,
  canManage,
  onDelete,
}: {
  tags: Tag[];
  canManage: boolean;
  onDelete: (tagId: number) => Promise<void>;
}) {
  if (!canManage) {
    return (
      <div className="rounded border border-black/10 p-4 text-sm dark:border-white/10">
        <p className="mb-1 font-medium">Tags</p>
        <Link href="/login" className="text-sm font-medium underline">
          Log in to manage tags
        </Link>
      </div>
    );
  }

  const visibleTags = tags.filter((tag) => tag.usage_count !== 0);

  return (
    <div className="rounded border border-black/10 p-4 dark:border-white/10">
      <p className="mb-2 text-sm font-medium">Your tags</p>
      <ul className="flex flex-col gap-1">
        {visibleTags.map((tag) => (
          <li
            key={tag.id}
            className="flex items-center justify-between gap-2 text-sm"
          >
            <span>
              {tag.name}
              {tag.usage_count !== undefined && (
                <span className="ml-1 text-zinc-500">
                  ({tag.usage_count})
                </span>
              )}
            </span>
            <button
              type="button"
              aria-label={`Remove tag ${tag.name} from your articles`}
              onClick={() => onDelete(tag.id)}
              className="text-zinc-500 hover:text-red-600"
            >
              ×
            </button>
          </li>
        ))}
        {visibleTags.length === 0 && (
          <li className="text-sm text-zinc-500">
            You haven&apos;t tagged anything yet.
          </li>
        )}
      </ul>
    </div>
  );
}
