"use client";

import { useState, type FormEvent } from "react";
import { ApiError } from "@/lib/api";
import type {
  GigLeadCountry,
  GigLeadInput,
  GigLeadJobType,
  GigLeadOrigin,
} from "@/lib/gig-lead-types";

function isValidUrl(value: string): boolean {
  try {
    const url = new URL(value);
    return url.protocol === "http:" || url.protocol === "https:";
  } catch {
    return false;
  }
}

export function GigLeadForm({
  onSubmit,
}: {
  onSubmit: (input: GigLeadInput) => Promise<void>;
}) {
  const [url, setUrl] = useState("");
  const [country, setCountry] = useState<GigLeadCountry>("usa");
  const [jobType, setJobType] = useState<GigLeadJobType>("full_time");
  const [origin, setOrigin] = useState<GigLeadOrigin>("linkedin");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);

    if (!isValidUrl(url)) {
      setError("Enter a valid http(s) URL.");
      return;
    }

    setSubmitting(true);
    try {
      await onSubmit({ url, country, job_type: jobType, origin });
      setUrl("");
    } catch (err) {
      if (err instanceof ApiError) {
        const message =
          typeof err.data === "object" && err.data && "message" in err.data
            ? String((err.data as { message: unknown }).message)
            : "Couldn't save that lead.";
        setError(message);
      } else {
        setError("Couldn't save that lead.");
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="flex flex-col gap-3 rounded border border-black/10 p-4 dark:border-white/10"
    >
      <input
        type="url"
        placeholder="https://..."
        required
        value={url}
        onChange={(e) => setUrl(e.target.value)}
        className="rounded border border-black/10 px-2 py-1 text-sm dark:border-white/10"
      />

      <div className="flex flex-wrap gap-3 text-sm">
        <label className="flex items-center gap-1">
          Country
          <select
            value={country}
            onChange={(e) => setCountry(e.target.value as GigLeadCountry)}
            className="rounded border border-black/10 bg-background px-2 py-1 text-foreground dark:border-white/10"
          >
            <option value="usa">USA</option>
            <option value="canada">Canada</option>
          </select>
        </label>

        <label className="flex items-center gap-1">
          Job type
          <select
            value={jobType}
            onChange={(e) => setJobType(e.target.value as GigLeadJobType)}
            className="rounded border border-black/10 bg-background px-2 py-1 text-foreground dark:border-white/10"
          >
            <option value="full_time">Full time</option>
            <option value="part_time">Part time</option>
            <option value="short_term_contract">Short term contract</option>
            <option value="long_term_contract">Long term contract</option>
          </select>
        </label>

        <label className="flex items-center gap-1">
          Origin
          <select
            value={origin}
            onChange={(e) => setOrigin(e.target.value as GigLeadOrigin)}
            className="rounded border border-black/10 bg-background px-2 py-1 text-foreground dark:border-white/10"
          >
            <option value="linkedin">LinkedIn</option>
            <option value="arc">Arc</option>
            <option value="indeed">Indeed</option>
            <option value="gunio">Gun.io</option>
            <option value="other">Other</option>
          </select>
        </label>
      </div>

      {error && <p className="text-sm text-red-600">{error}</p>}

      <button
        type="submit"
        disabled={submitting}
        className="self-start rounded bg-foreground px-3 py-1 text-sm font-medium text-background disabled:opacity-50"
      >
        Add lead
      </button>
    </form>
  );
}
