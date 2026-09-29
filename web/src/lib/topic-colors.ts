/**
 * Topics are admin-manageable and arbitrary, so colors are derived
 * deterministically from a topic's id against this fixed palette rather
 * than hardcoded by name — any topic (existing or future) gets a
 * consistent color for free. Keep this the single source of truth so the
 * sub-nav dot and an article's accent stripe always match for the same
 * topic.
 */
const PALETTE = [
  { dot: "bg-blue-500", border: "border-l-blue-500" },
  { dot: "bg-amber-500", border: "border-l-amber-500" },
  { dot: "bg-emerald-500", border: "border-l-emerald-500" },
  { dot: "bg-rose-500", border: "border-l-rose-500" },
  { dot: "bg-violet-500", border: "border-l-violet-500" },
  { dot: "bg-cyan-500", border: "border-l-cyan-500" },
];

export function topicColor(topicId: number) {
  return PALETTE[topicId % PALETTE.length];
}
