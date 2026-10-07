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

const PROFILE_IMAGE_SIZE = 160;

async function resizeToSquarePng(file: File): Promise<Blob> {
  const bitmap = await createImageBitmap(file);
  const side = Math.min(bitmap.width, bitmap.height);
  const canvas = document.createElement("canvas");
  canvas.width = PROFILE_IMAGE_SIZE;
  canvas.height = PROFILE_IMAGE_SIZE;
  const ctx = canvas.getContext("2d");
  if (!ctx) throw new Error("Canvas unavailable");
  ctx.imageSmoothingQuality = "high";
  ctx.drawImage(
    bitmap,
    (bitmap.width - side) / 2,
    (bitmap.height - side) / 2,
    side,
    side,
    0,
    0,
    PROFILE_IMAGE_SIZE,
    PROFILE_IMAGE_SIZE,
  );
  bitmap.close();
  return new Promise((resolve, reject) =>
    canvas.toBlob(
      (blob) => (blob ? resolve(blob) : reject(new Error("Encode failed"))),
      "image/png",
    ),
  );
}

export async function uploadProfileImage(file: File): Promise<void> {
  const formData = new FormData();
  formData.append("image", await resizeToSquarePng(file), "profile.png");
  await apiFetch<unknown>("/api/resumes/profile-image", {
    method: "POST",
    body: formData,
  });
}

export async function convertResume(id: number): Promise<void> {
  await apiFetch<unknown>(`/api/resumes/${id}/convert`, { method: "POST" });
}

export async function makeResumePrimary(id: number): Promise<void> {
  await apiFetch<unknown>(`/api/resumes/${id}/primary`, { method: "PATCH" });
}

export function deleteResume(id: number): Promise<void> {
  return apiFetch<void>(`/api/resumes/${id}`, { method: "DELETE" });
}

export function resumeDownloadUrl(id: number): string {
  return `${API_URL}/api/resumes/${id}/download`;
}
