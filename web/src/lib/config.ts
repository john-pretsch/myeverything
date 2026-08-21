import { apiFetch } from "./api";

export type AppConfig = {
  features: {
    two_factor_auth: boolean;
  };
};

export function getConfig(): Promise<AppConfig> {
  return apiFetch<AppConfig>("/api/config");
}
