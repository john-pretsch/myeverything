import { API_URL, apiFetch } from "./api";
import type { Resume } from "./resume-types";

export async function getResumes(): Promise<Resume[]> {
  const res = await apiFetch<{ data: Resume[] }>("/api/resumes");
  return res.data;
}

export async function uploadResume(file: File): Promise<Resume> {
  const formData = new FormData();
  formData.append("file", file);
  const res = await apiFetch<{ data: Resume }>("/api/resumes", {
    method: "POST",
    body: formData,
  });
  return res.data;
}

export function deleteResume(id: number): Promise<void> {
  return apiFetch<void>(`/api/resumes/${id}`, { method: "DELETE" });
}

export function resumeDownloadUrl(id: number): string {
  return `${API_URL}/api/resumes/${id}/download`;
}
