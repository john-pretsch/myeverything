const STORAGE_KEY = "news.recentSearches";
const MAX_ENTRIES = 8;

export function getRecentSearches(): string[] {
  if (typeof window === "undefined") return [];
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed.filter((v) => typeof v === "string") : [];
  } catch {
    return [];
  }
}

export function addRecentSearch(topic: string): string[] {
  const trimmed = topic.trim();
  if (trimmed === "" || typeof window === "undefined") return getRecentSearches();

  const existing = getRecentSearches().filter(
    (t) => t.toLowerCase() !== trimmed.toLowerCase(),
  );
  const next = [trimmed, ...existing].slice(0, MAX_ENTRIES);

  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
  } catch {
    // localStorage unavailable (private mode, quota, etc.) — not critical
  }

  return next;
}

export function clearRecentSearches(): string[] {
  if (typeof window !== "undefined") {
    try {
      window.localStorage.removeItem(STORAGE_KEY);
    } catch {
      // ignore
    }
  }
  return [];
}
