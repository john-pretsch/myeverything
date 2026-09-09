"use client";

import { useEffect, useState } from "react";
import { ApiError } from "@/lib/api";
import {
  acceptTailoredResume,
  createGigLead,
  deleteGigLead,
  fetchGigLeadDetails,
  getGigLeads,
  tailorResumePreview,
  updateGigLeadCompletionStatus,
  updateGigLeadDetails,
  updateGigLeadStatus,
} from "@/lib/gig-leads";
import type {
  GigLead,
  GigLeadCompletionStatus,
  GigLeadDetailsInput,
  GigLeadInput,
  GigLeadStatus,
} from "@/lib/gig-lead-types";
import { getResumes, uploadResume } from "@/lib/resumes";
import type { Resume } from "@/lib/resume-types";
import { GigLeadForm } from "./gig-lead-form";
import { GigLeadItem } from "./gig-lead-item";

export function GigLeadList() {
  const [leads, setLeads] = useState<GigLead[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [requestId, setRequestId] = useState(0);
  const [resolvedId, setResolvedId] = useState(-1);
  const loading = requestId !== resolvedId;

  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [fetchingId, setFetchingId] = useState<number | null>(null);
  const [detailsError, setDetailsError] = useState<string | null>(null);
  const [resumes, setResumes] = useState<Resume[]>([]);

  useEffect(() => {
    let ignore = false;

    getGigLeads()
      .then((data) => {
        if (ignore) return;
        setLeads(data);
        setError(null);
      })
      .catch(() => {
        if (!ignore) setError("Couldn't load your gig leads.");
      })
      .finally(() => {
        if (!ignore) setResolvedId(requestId);
      });

    return () => {
      ignore = true;
    };
  }, [requestId]);

  useEffect(() => {
    getResumes()
      .then(setResumes)
      .catch(() => {});
  }, []);

  function reload() {
    setRequestId((n) => n + 1);
  }

  async function handleCreate(input: GigLeadInput) {
    await createGigLead(input);
    reload();
  }

  async function handleSetStatus(id: number, status: GigLeadStatus) {
    await updateGigLeadStatus(id, status);
    reload();
  }

  async function handleSetCompletionStatus(
    id: number,
    completionStatus: GigLeadCompletionStatus,
  ) {
    const updated = await updateGigLeadCompletionStatus(id, completionStatus);
    setLeads((prev) => prev.map((l) => (l.id === id ? updated : l)));
  }

  async function handleDelete(id: number) {
    await deleteGigLead(id);
    if (selectedId === id) setSelectedId(null);
    reload();
  }

  async function handleSelect(id: number) {
    if (selectedId === id) {
      setSelectedId(null);
      return;
    }

    setSelectedId(id);
    setDetailsError(null);

    const lead = leads.find((l) => l.id === id);
    if (!lead || lead.description !== null) return;

    setFetchingId(id);
    try {
      const updated = await fetchGigLeadDetails(id);
      setLeads((prev) => prev.map((l) => (l.id === id ? updated : l)));
    } catch (err) {
      const message =
        err instanceof ApiError &&
        typeof err.data === "object" &&
        err.data &&
        "message" in err.data
          ? String((err.data as { message: unknown }).message)
          : "Couldn't fetch the job details.";
      setDetailsError(message);
    } finally {
      setFetchingId(null);
    }
  }

  async function handleSaveDetails(id: number, input: GigLeadDetailsInput) {
    const updated = await updateGigLeadDetails(id, input);
    setLeads((prev) => prev.map((l) => (l.id === id ? updated : l)));
  }

  async function handleTailorResume(
    gigLeadId: number,
    resumeId: number,
  ): Promise<string> {
    return tailorResumePreview(gigLeadId, resumeId);
  }

  async function handleAcceptTailoredResume(
    gigLeadId: number,
    resumeId: number,
    content: string,
  ): Promise<void> {
    const newResume = await acceptTailoredResume(gigLeadId, resumeId, content);
    setResumes((prev) => [newResume, ...prev]);
  }

  async function handleUploadResume(file: File): Promise<Resume> {
    const newResume = await uploadResume(file);
    setResumes((prev) => [newResume, ...prev]);
    return newResume;
  }

  return (
    <div>
      <h1 className="mb-4 text-xl font-semibold">Gig Leads</h1>

      <div className="mb-4">
        <GigLeadForm onSubmit={handleCreate} />
      </div>

      {error && <p className="mb-4 text-sm text-red-600">{error}</p>}

      {loading && leads.length === 0 ? (
        <p className="text-sm text-zinc-500">Loading...</p>
      ) : leads.length === 0 ? (
        <p className="text-sm text-zinc-500">No leads yet.</p>
      ) : (
        <ul className="flex flex-col gap-2">
          {leads.map((lead) => (
            <GigLeadItem
              key={lead.id}
              lead={lead}
              selected={selectedId === lead.id}
              fetchingDetails={fetchingId === lead.id}
              detailsError={selectedId === lead.id ? detailsError : null}
              resumes={resumes}
              onSelect={handleSelect}
              onSetStatus={handleSetStatus}
              onSetCompletionStatus={handleSetCompletionStatus}
              onDelete={handleDelete}
              onSaveDetails={handleSaveDetails}
              onTailorResume={handleTailorResume}
              onAcceptTailoredResume={handleAcceptTailoredResume}
              onUploadResume={handleUploadResume}
            />
          ))}
        </ul>
      )}
    </div>
  );
}
