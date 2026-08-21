"use client";

import { useEffect, useState, type FormEvent } from "react";
import { ApiError } from "@/lib/api";
import type {
  GigLead,
  GigLeadDetailsInput,
  GigLeadJobType,
  GigLeadOrigin,
  GigLeadStatus,
} from "@/lib/gig-lead-types";

const JOB_TYPE_LABELS: Record<GigLeadJobType, string> = {
  full_time: "Full time",
  part_time: "Part time",
  short_term_contract: "Short term contract",
  long_term_contract: "Long term contract",
};

const ORIGIN_LABELS: Record<GigLeadOrigin, string> = {
  linkedin: "LinkedIn",
  arc: "Arc",
  indeed: "Indeed",
  gunio: "Gun.io",
  other: "Other",
};

export function GigLeadItem({
  lead,
  selected,
  fetchingDetails,
  detailsError,
  onSelect,
  onSetStatus,
  onDelete,
  onSaveDetails,
}: {
  lead: GigLead;
  selected: boolean;
  fetchingDetails: boolean;
  detailsError: string | null;
  onSelect: (id: number) => void;
  onSetStatus: (id: number, status: GigLeadStatus) => void;
  onDelete: (id: number) => void;
  onSaveDetails: (id: number, input: GigLeadDetailsInput) => Promise<void>;
}) {
  const addedAt = new Date(lead.added_at).toLocaleString();
  const heading = lead.title || lead.url;
  const subline = [
    lead.company,
    lead.country === "usa" ? "USA" : "Canada",
    JOB_TYPE_LABELS[lead.job_type],
    ORIGIN_LABELS[lead.origin],
  ]
    .filter(Boolean)
    .join(" · ");

  const [formTitle, setFormTitle] = useState(lead.title ?? "");
  const [formCompany, setFormCompany] = useState(lead.company ?? "");
  const [formDescription, setFormDescription] = useState(
    lead.description ?? "",
  );
  const [saving, setSaving] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);

  useEffect(() => {
    if (selected) {
      setFormTitle(lead.title ?? "");
      setFormCompany(lead.company ?? "");
      setFormDescription(lead.description ?? "");
      setSaveError(null);
    }
    // Re-sync once a fetch resolves (lead.description flips from null),
    // but not on every unrelated re-render while the panel stays open.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selected, lead.description]);

  async function handleSave(event: FormEvent) {
    event.preventDefault();
    setSaving(true);
    setSaveError(null);
    try {
      await onSaveDetails(lead.id, {
        title: formTitle.trim(),
        company: formCompany.trim(),
        description: formDescription.trim(),
      });
    } catch (err) {
      const message =
        err instanceof ApiError &&
        typeof err.data === "object" &&
        err.data &&
        "message" in err.data
          ? String((err.data as { message: unknown }).message)
          : "Couldn't save those details.";
      setSaveError(message);
    } finally {
      setSaving(false);
    }
  }

  return (
    <li
      className={`rounded border border-black/10 p-3 text-sm dark:border-white/10 ${
        lead.status === "dismissed" ? "opacity-50" : ""
      }`}
    >
      <div className="flex items-start justify-between gap-3">
        <button
          type="button"
          onClick={() => onSelect(lead.id)}
          className="min-w-0 flex-1 text-left"
          aria-expanded={selected}
        >
          <p className="truncate font-medium hover:underline">{heading}</p>
          <p className="truncate text-xs text-zinc-500">{subline}</p>
          {lead.title && (
            <p className="truncate text-xs text-zinc-400">{lead.url}</p>
          )}
          <p className="text-xs text-zinc-400">Added {addedAt}</p>
        </button>
        <div className="flex shrink-0 items-center gap-1 text-xs text-zinc-500">
          <button
            type="button"
            onClick={() => onSelect(lead.id)}
            className="hover:text-foreground"
          >
            Edit details
          </button>
          {lead.status !== "reviewed" && (
            <button
              type="button"
              onClick={() => onSetStatus(lead.id, "reviewed")}
              className="hover:text-foreground"
            >
              Mark reviewed
            </button>
          )}
          {lead.status !== "dismissed" && (
            <button
              type="button"
              onClick={() => onSetStatus(lead.id, "dismissed")}
              className="hover:text-foreground"
            >
              Dismiss
            </button>
          )}
          <button
            type="button"
            onClick={() => onDelete(lead.id)}
            className="hover:text-red-600"
          >
            Delete
          </button>
        </div>
      </div>

      {selected && (
        <div className="mt-3 border-t border-black/10 pt-3 dark:border-white/10">
          <a
            href={lead.url}
            target="_blank"
            rel="noreferrer noopener"
            className="mb-2 inline-block text-xs font-medium hover:underline"
          >
            Open listing ↗
          </a>

          {fetchingDetails ? (
            <p className="text-xs text-zinc-500">Fetching job details...</p>
          ) : (
            <form onSubmit={handleSave} className="flex flex-col gap-2">
              {detailsError && (
                <p className="text-xs text-red-600">
                  Couldn&apos;t auto-fetch details: {detailsError}. Enter them
                  manually below.
                </p>
              )}

              <label className="flex flex-col gap-1 text-xs text-zinc-500">
                Job title
                <input
                  type="text"
                  value={formTitle}
                  onChange={(e) => setFormTitle(e.target.value)}
                  placeholder="Not parsed — enter manually"
                  className="rounded border border-black/10 px-2 py-1 text-sm text-foreground dark:border-white/10"
                />
              </label>

              <label className="flex flex-col gap-1 text-xs text-zinc-500">
                Company
                <input
                  type="text"
                  value={formCompany}
                  onChange={(e) => setFormCompany(e.target.value)}
                  placeholder="Not parsed — enter manually"
                  className="rounded border border-black/10 px-2 py-1 text-sm text-foreground dark:border-white/10"
                />
              </label>

              <label className="flex flex-col gap-1 text-xs text-zinc-500">
                Description
                <textarea
                  value={formDescription}
                  onChange={(e) => setFormDescription(e.target.value)}
                  placeholder="Not parsed — enter manually"
                  rows={5}
                  className="rounded border border-black/10 px-2 py-1 text-sm text-foreground dark:border-white/10"
                />
              </label>

              {saveError && <p className="text-xs text-red-600">{saveError}</p>}

              <button
                type="submit"
                disabled={saving}
                className="self-start rounded bg-foreground px-3 py-1 text-xs font-medium text-background disabled:opacity-50"
              >
                {saving ? "Saving..." : "Save details"}
              </button>
            </form>
          )}
        </div>
      )}
    </li>
  );
}
