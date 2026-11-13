import { apiFetch } from "./api";

export type Alert = {
  id: string;
  kind: "security" | "news";
  severity: "critical" | "high" | "info";
  title: string;
  summary: string | null;
  url: string | null;
  source: string;
  published_at: string | null;
};

export async function getAlerts(): Promise<Alert[]> {
  const res = await apiFetch<{ data: Alert[] }>("/api/dashboard/alerts");
  return res.data;
}
