"use client";

import { useEffect, useState } from "react";
import { ApiError } from "@/lib/api";
import {
  createGigLead,
  deleteGigLead,
  fetchGigLeadDetails,
  getGigLeads,
  updateGigLeadDetails,
  updateGigLeadStatus,
} from "@/lib/gig-leads";
import type {
  GigLead,
  GigLeadDetailsInput,
  GigLeadInput,
  GigLeadStatus,
} from "@/lib/gig-lead-types";
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
              onSelect={handleSelect}
              onSetStatus={handleSetStatus}
              onDelete={handleDelete}
              onSaveDetails={handleSaveDetails}
            />
          ))}
        </ul>
      )}
    </div>
  );
}
