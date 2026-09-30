import Link from "next/link";
import type { Tag } from "@/lib/news-types";

export function TagManager({
  tags,
  canManage,
  selectedTagId,
  onSelectTag,
  onDelete,
}: {
  tags: Tag[];
  canManage: boolean;
  selectedTagId: number | null;
  onSelectTag: (tagId: number | null) => void;
  onDelete: (tagId: number) => Promise<void>;
}) {
  const visibleTags = tags.filter((tag) => tag.usage_count !== 0);

  function tagButtonClass(tagId: number) {
    return selectedTagId === tagId
      ? "cursor-pointer font-medium text-foreground"
      : "cursor-pointer text-zinc-500 hover:text-foreground";
  }

  function toggle(tagId: number) {
    onSelectTag(selectedTagId === tagId ? null : tagId);
  }

  if (!canManage) {
    return (
      <div className="rounded border border-black/10 p-4 text-sm dark:border-white/10">
        <p className="mb-1 font-medium">Tags</p>
        <ul className="mb-3 flex flex-col gap-1">
          {visibleTags.map((tag) => (
            <li key={tag.id}>
              <button
                type="button"
                onClick={() => toggle(tag.id)}
                className={tagButtonClass(tag.id)}
              >
                {tag.name}
                {tag.usage_count !== undefined && (
                  <span className="ml-1 text-zinc-500">
                    ({tag.usage_count})
                  </span>
                )}
              </button>
            </li>
          ))}
          {visibleTags.length === 0 && (
            <li className="text-sm text-zinc-500">No tags yet.</li>
          )}
        </ul>
        <Link href="/login" className="text-sm font-medium underline">
          Log in to manage tags
        </Link>
      </div>
    );
  }

  return (
    <div className="rounded border border-black/10 p-4 dark:border-white/10">
      <p className="mb-2 text-sm font-medium">All tags</p>
      <ul className="flex flex-col gap-1">
        {visibleTags.map((tag) => (
          <li
            key={tag.id}
            className="flex items-center justify-between gap-2 text-sm"
          >
            <button
              type="button"
              onClick={() => toggle(tag.id)}
              className={`min-w-0 flex-1 truncate text-left ${tagButtonClass(tag.id)}`}
            >
              {tag.name}
              {tag.usage_count !== undefined && (
                <span className="ml-1 text-zinc-500">
                  ({tag.usage_count})
                </span>
              )}
            </button>
            <button
              type="button"
              aria-label={`Delete tag ${tag.name}`}
              onClick={() => onDelete(tag.id)}
              className="flex-shrink-0 text-zinc-500 hover:text-red-600"
            >
              ×
            </button>
          </li>
        ))}
        {visibleTags.length === 0 && (
          <li className="text-sm text-zinc-500">No tags yet.</li>
        )}
      </ul>
    </div>
  );
}
