"use client";

import {
  useEffect,
  useRef,
  useState,
  type ChangeEvent,
  type FormEvent,
} from "react";
import { ApiError } from "@/lib/api";
import type {
  GigLead,
  GigLeadCompletionStatus,
  GigLeadDetailsInput,
  GigLeadJobType,
  GigLeadOrigin,
  GigLeadStatus,
} from "@/lib/gig-lead-types";
import type { Resume } from "@/lib/resume-types";

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

const COMPLETION_STATUS_LABELS: Record<GigLeadCompletionStatus, string> = {
  started: "Started",
  complete: "Complete",
  applied: "Applied",
};

function errorMessage(err: unknown, fallback: string): string {
  return err instanceof ApiError &&
    typeof err.data === "object" &&
    err.data &&
    "message" in err.data
    ? String((err.data as { message: unknown }).message)
    : fallback;
}

export function GigLeadItem({
  lead,
  selected,
  fetchingDetails,
  detailsError,
  resumes,
  onSelect,
  onSetStatus,
  onSetCompletionStatus,
  onDelete,
  onSaveDetails,
  onTailorResume,
  onAcceptTailoredResume,
  onUploadResume,
}: {
  lead: GigLead;
  selected: boolean;
  fetchingDetails: boolean;
  detailsError: string | null;
  resumes: Resume[];
  onSelect: (id: number) => void;
  onSetStatus: (id: number, status: GigLeadStatus) => void;
  onSetCompletionStatus: (
    id: number,
    completionStatus: GigLeadCompletionStatus,
  ) => Promise<void>;
  onDelete: (id: number) => void;
  onSaveDetails: (id: number, input: GigLeadDetailsInput) => Promise<void>;
  onTailorResume: (gigLeadId: number, resumeId: number) => Promise<string>;
  onAcceptTailoredResume: (
    gigLeadId: number,
    resumeId: number,
    content: string,
  ) => Promise<void>;
  onUploadResume: (file: File) => Promise<Resume>;
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

  const tailorableResumes = resumes.filter((r) => r.content);
  const [selectedResumeId, setSelectedResumeId] = useState<number | null>(
    null,
  );
  const [tailoring, setTailoring] = useState(false);
  const [tailorError, setTailorError] = useState<string | null>(null);
  const [previewContent, setPreviewContent] = useState<string | null>(null);
  const [accepting, setAccepting] = useState(false);
  const [acceptError, setAcceptError] = useState<string | null>(null);
  const [accepted, setAccepted] = useState(false);

  const uploadInputRef = useRef<HTMLInputElement>(null);
  const [uploading, setUploading] = useState(false);
  const [uploadError, setUploadError] = useState<string | null>(null);

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

  useEffect(() => {
    if (selected && selectedResumeId === null && tailorableResumes.length > 0) {
      setSelectedResumeId(tailorableResumes[0].id);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selected, tailorableResumes.length]);

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
      setSaveError(errorMessage(err, "Couldn't save those details."));
    } finally {
      setSaving(false);
    }
  }

  async function handleTailor() {
    if (!selectedResumeId) return;

    setTailoring(true);
    setTailorError(null);
    setAcceptError(null);
    setAccepted(false);
    try {
      const content = await onTailorResume(lead.id, selectedResumeId);
      setPreviewContent(content);
    } catch (err) {
      setTailorError(errorMessage(err, "Couldn't customize that resume."));
    } finally {
      setTailoring(false);
    }
  }

  async function handleAccept() {
    if (!selectedResumeId || previewContent === null) return;

    setAccepting(true);
    setAcceptError(null);
    try {
      await onAcceptTailoredResume(lead.id, selectedResumeId, previewContent);
      setPreviewContent(null);
      setAccepted(true);
    } catch (err) {
      setAcceptError(errorMessage(err, "Couldn't save the tailored resume."));
    } finally {
      setAccepting(false);
    }
  }

  async function handleUpload(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0];
    event.target.value = "";
    if (!file) return;

    setUploading(true);
    setUploadError(null);
    try {
      const resume = await onUploadResume(file);
      if (resume.content) setSelectedResumeId(resume.id);
    } catch (err) {
      setUploadError(errorMessage(err, "Couldn't upload that resume."));
    } finally {
      setUploading(false);
    }
  }

  function handleDiscard() {
    setPreviewContent(null);
    setTailorError(null);
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
          <p className="truncate text-xs text-zinc-500">
            {subline}
            {subline && " · "}
            {COMPLETION_STATUS_LABELS[lead.completion_status]}
          </p>
          {lead.title && (
            <p className="truncate text-xs text-zinc-400">{lead.url}</p>
          )}
          <p className="text-xs text-zinc-400">Added {addedAt}</p>
        </button>
        <div className="flex shrink-0 items-center gap-1 text-xs text-zinc-500">
          <select
            value={lead.completion_status}
            onChange={(e) =>
              onSetCompletionStatus(
                lead.id,
                e.target.value as GigLeadCompletionStatus,
              )
            }
            className="rounded border border-black/10 bg-background px-1 py-0.5 text-foreground dark:border-white/10"
          >
            {Object.entries(COMPLETION_STATUS_LABELS).map(([value, label]) => (
              <option key={value} value={value}>
                {label}
              </option>
            ))}
          </select>
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

          {lead.description && (
            <div className="mt-3 border-t border-black/10 pt-3 dark:border-white/10">
              <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                <p className="text-xs font-medium">
                  Tailor a resume for this job
                </p>
                <input
                  ref={uploadInputRef}
                  type="file"
                  accept=".pdf,.txt,application/pdf,text/plain"
                  onChange={handleUpload}
                  disabled={uploading}
                  className="hidden"
                />
                <button
                  type="button"
                  onClick={() => uploadInputRef.current?.click()}
                  disabled={uploading}
                  className="rounded border border-black/10 px-3 py-1 text-xs font-medium disabled:opacity-50 dark:border-white/10"
                >
                  {uploading ? "Uploading..." : "Upload resume"}
                </button>
              </div>

              {uploadError && (
                <p className="mb-2 text-xs text-red-600">{uploadError}</p>
              )}

              {tailorableResumes.length === 0 ? (
                <p className="text-xs text-zinc-500">
                  Upload a resume with parsed text to enable this.
                </p>
              ) : (
                <div className="flex flex-wrap items-center gap-2">
                  <select
                    value={selectedResumeId ?? ""}
                    onChange={(e) => setSelectedResumeId(Number(e.target.value))}
                    className="rounded border border-black/10 bg-background px-2 py-1 text-xs text-foreground dark:border-white/10"
                  >
                    {tailorableResumes.map((resume) => (
                      <option key={resume.id} value={resume.id}>
                        {resume.filename}
                      </option>
                    ))}
                  </select>
                  <button
                    type="button"
                    onClick={handleTailor}
                    disabled={tailoring || !selectedResumeId}
                    className="rounded bg-foreground px-3 py-1 text-xs font-medium text-background disabled:opacity-50"
                  >
                    {tailoring ? "Customizing..." : "Customize with ChatGPT"}
                  </button>
                </div>
              )}

              {tailorError && (
                <p className="mt-2 text-xs text-red-600">{tailorError}</p>
              )}

              {accepted && (
                <p className="mt-2 text-xs text-green-600">
                  Saved as a new resume.
                </p>
              )}

              {previewContent !== null && (
                <div className="mt-2 rounded border border-black/10 p-2 dark:border-white/10">
                  <p className="mb-1 text-xs font-medium">Preview</p>
                  <p className="max-h-64 overflow-y-auto whitespace-pre-line text-xs text-foreground">
                    {previewContent}
                  </p>

                  {acceptError && (
                    <p className="mt-2 text-xs text-red-600">{acceptError}</p>
                  )}

                  <div className="mt-2 flex gap-2">
                    <button
                      type="button"
                      onClick={handleAccept}
                      disabled={accepting}
                      className="rounded bg-foreground px-3 py-1 text-xs font-medium text-background disabled:opacity-50"
                    >
                      {accepting ? "Saving..." : "Accept & save"}
                    </button>
                    <button
                      type="button"
                      onClick={handleDiscard}
                      disabled={accepting}
                      className="rounded border border-black/10 px-3 py-1 text-xs dark:border-white/10"
                    >
                      Discard
                    </button>
                  </div>
                </div>
              )}
            </div>
          )}
        </div>
      )}
    </li>
  );
}
