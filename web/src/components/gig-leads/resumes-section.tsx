"use client";

import { useEffect, useRef, useState, type ChangeEvent } from "react";
import { ApiError } from "@/lib/api";
import {
  deleteResume,
  getResumes,
  resumeDownloadUrl,
  uploadResume,
} from "@/lib/resumes";
import type { Resume } from "@/lib/resume-types";

function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function errorMessage(err: unknown): string {
  if (err instanceof ApiError) {
    return typeof err.data === "object" && err.data && "message" in err.data
      ? String((err.data as { message: unknown }).message)
      : "Something went wrong.";
  }
  return "Something went wrong.";
}

export function ResumesSection() {
  const [resumes, setResumes] = useState<Resume[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [uploading, setUploading] = useState(false);
  const [expandedId, setExpandedId] = useState<number | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  function reload() {
    setLoading(true);
    getResumes()
      .then((data) => {
        setResumes(data);
        setError(null);
      })
      .catch(() => setError("Couldn't load your resumes."))
      .finally(() => setLoading(false));
  }

  useEffect(() => {
    reload();
  }, []);

  async function handleFileChange(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0];
    event.target.value = "";
    if (!file) return;

    setUploading(true);
    setError(null);
    try {
      await uploadResume(file);
      reload();
    } catch (err) {
      setError(errorMessage(err));
    } finally {
      setUploading(false);
    }
  }

  async function handleDelete(id: number) {
    try {
      await deleteResume(id);
      setResumes((prev) => prev.filter((r) => r.id !== id));
    } catch (err) {
      setError(errorMessage(err));
    }
  }

  return (
    <div className="rounded border border-black/10 p-4 text-sm dark:border-white/10">
      <p className="font-medium">Resumes</p>
      <p className="mt-1 text-zinc-500">
        Upload PDF or plain text resumes to keep on hand.
      </p>

      {error && <p className="mt-2 text-red-600">{error}</p>}

      <div className="mt-3">
        <input
          ref={fileInputRef}
          type="file"
          accept=".pdf,.txt,application/pdf,text/plain"
          onChange={handleFileChange}
          disabled={uploading}
          className="hidden"
        />
        <button
          type="button"
          onClick={() => fileInputRef.current?.click()}
          disabled={uploading}
          className="rounded bg-foreground px-3 py-1 text-sm font-medium text-background disabled:opacity-50"
        >
          {uploading ? "Uploading..." : "Upload resume"}
        </button>
      </div>

      {loading ? (
        <p className="mt-3 text-zinc-500">Loading...</p>
      ) : resumes.length === 0 ? (
        <p className="mt-3 text-zinc-500">No resumes uploaded yet.</p>
      ) : (
        <ul className="mt-3 flex flex-col gap-2">
          {resumes.map((resume) => (
            <li
              key={resume.id}
              className="rounded border border-black/10 p-2 dark:border-white/10"
            >
              <div className="flex items-center justify-between gap-3">
                <div className="min-w-0">
                  <a
                    href={resumeDownloadUrl(resume.id)}
                    className="truncate font-medium hover:underline"
                  >
                    {resume.filename}
                  </a>
                  <p className="text-xs text-zinc-500">
                    {formatSize(resume.size)} ·{" "}
                    {new Date(resume.uploaded_at).toLocaleString()}
                    {resume.organization && ` · Tailored for ${resume.organization}`}
                  </p>
                </div>
                <div className="flex shrink-0 items-center gap-3 text-xs text-zinc-500">
                  {resume.mime_type === "application/pdf" && (
                    <a
                      href={resume.view_url}
                      target="_blank"
                      rel="noopener"
                      className="hover:text-foreground"
                    >
                      View PDF
                    </a>
                  )}
                  <button
                    type="button"
                    onClick={() =>
                      setExpandedId((id) =>
                        id === resume.id ? null : resume.id,
                      )
                    }
                    className="hover:text-foreground"
                  >
                    {expandedId === resume.id ? "Hide text" : "View text"}
                  </button>
                  <button
                    type="button"
                    onClick={() => handleDelete(resume.id)}
                    className="hover:text-red-600"
                  >
                    Delete
                  </button>
                </div>
              </div>

              {expandedId === resume.id && (
                <div className="mt-2 border-t border-black/10 pt-2 dark:border-white/10">
                  {resume.content === null ? (
                    <p className="text-xs text-zinc-500">
                      Couldn&apos;t parse this file into plain text.
                    </p>
                  ) : resume.content === "" ? (
                    <p className="text-xs text-zinc-500">
                      No text found in this file.
                    </p>
                  ) : (
                    <p className="max-h-64 overflow-y-auto whitespace-pre-line text-xs text-foreground">
                      {resume.content}
                    </p>
                  )}
                </div>
              )}
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
