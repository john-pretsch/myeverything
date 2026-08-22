import { apiFetch } from "./api";
import type {
  GigLead,
  GigLeadCompletionStatus,
  GigLeadDetailsInput,
  GigLeadInput,
  GigLeadStatus,
} from "./gig-lead-types";
import type { Resume } from "./resume-types";

export async function getGigLeads(): Promise<GigLead[]> {
  const res = await apiFetch<{ data: GigLead[] }>("/api/gig-leads");
  return res.data;
}

export async function createGigLead(input: GigLeadInput): Promise<GigLead> {
  const res = await apiFetch<{ data: GigLead }>("/api/gig-leads", {
    method: "POST",
    body: input,
  });
  return res.data;
}

export async function updateGigLeadStatus(
  id: number,
  status: GigLeadStatus,
): Promise<GigLead> {
  const res = await apiFetch<{ data: GigLead }>(`/api/gig-leads/${id}`, {
    method: "PATCH",
    body: { status },
  });
  return res.data;
}

export async function updateGigLeadCompletionStatus(
  id: number,
  completion_status: GigLeadCompletionStatus,
): Promise<GigLead> {
  const res = await apiFetch<{ data: GigLead }>(`/api/gig-leads/${id}`, {
    method: "PATCH",
    body: { completion_status },
  });
  return res.data;
}

export function deleteGigLead(id: number): Promise<void> {
  return apiFetch<void>(`/api/gig-leads/${id}`, { method: "DELETE" });
}

export async function fetchGigLeadDetails(id: number): Promise<GigLead> {
  const res = await apiFetch<{ data: GigLead }>(
    `/api/gig-leads/${id}/fetch-details`,
    { method: "POST" },
  );
  return res.data;
}

export async function updateGigLeadDetails(
  id: number,
  input: GigLeadDetailsInput,
): Promise<GigLead> {
  const res = await apiFetch<{ data: GigLead }>(`/api/gig-leads/${id}`, {
    method: "PATCH",
    body: input,
  });
  return res.data;
}

export async function tailorResumePreview(
  gigLeadId: number,
  resumeId: number,
): Promise<string> {
  const res = await apiFetch<{ content: string }>(
    `/api/gig-leads/${gigLeadId}/tailor-resume`,
    { method: "POST", body: { resume_id: resumeId } },
  );
  return res.content;
}

export async function acceptTailoredResume(
  gigLeadId: number,
  resumeId: number,
  content: string,
): Promise<Resume> {
  const res = await apiFetch<{ data: Resume }>(
    `/api/gig-leads/${gigLeadId}/tailor-resume/accept`,
    { method: "POST", body: { resume_id: resumeId, content } },
  );
  return res.data;
}
