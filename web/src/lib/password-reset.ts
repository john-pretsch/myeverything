import { apiFetch } from "./api";

export function requestPasswordReset(email: string): Promise<{ message: string }> {
  return apiFetch("/api/forgot-password", {
    method: "POST",
    body: { email },
  });
}

export function resetPassword(input: {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}): Promise<{ message: string }> {
  return apiFetch("/api/reset-password", {
    method: "POST",
    body: input,
  });
}
