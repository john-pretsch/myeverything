import { apiFetch } from "./api";

export type TwoFactorSetup = {
  secret: string;
  qr_code_url: string;
  recovery_codes: string[];
};

export function enableTwoFactor(): Promise<TwoFactorSetup> {
  return apiFetch<TwoFactorSetup>("/api/user/two-factor-authentication", {
    method: "POST",
  });
}

export function confirmTwoFactor(
  code: string,
): Promise<{ message: string; recovery_codes: string[] }> {
  return apiFetch("/api/user/two-factor-authentication/confirm", {
    method: "POST",
    body: { code },
  });
}

export function disableTwoFactor(currentPassword: string): Promise<void> {
  return apiFetch<void>("/api/user/two-factor-authentication", {
    method: "DELETE",
    body: { current_password: currentPassword },
  });
}

export function getRecoveryCodes(): Promise<{ recovery_codes: string[] }> {
  return apiFetch("/api/user/two-factor-authentication/recovery-codes");
}

export function regenerateRecoveryCodes(): Promise<{ recovery_codes: string[] }> {
  return apiFetch("/api/user/two-factor-authentication/recovery-codes", {
    method: "POST",
  });
}
