"use client";

import { useEffect, useState } from "react";
import QRCode from "qrcode";
import { RequireAuth } from "@/components/require-auth";
import { ApiError } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { getConfig } from "@/lib/config";
import {
  confirmTwoFactor,
  disableTwoFactor,
  enableTwoFactor,
  getRecoveryCodes,
  regenerateRecoveryCodes,
  type TwoFactorSetup,
} from "@/lib/two-factor";

function errorMessage(err: unknown): string {
  if (err instanceof ApiError) {
    return typeof err.data === "object" && err.data && "message" in err.data
      ? String((err.data as { message: unknown }).message)
      : "Something went wrong.";
  }
  return "Something went wrong.";
}

function TwoFactorSection() {
  const { user, refresh } = useAuth();
  const [featureEnabled, setFeatureEnabled] = useState<boolean | null>(null);
  const [setup, setSetup] = useState<TwoFactorSetup | null>(null);
  const [qrDataUrl, setQrDataUrl] = useState<string | null>(null);
  const [confirmationCode, setConfirmationCode] = useState("");
  const [recoveryCodes, setRecoveryCodes] = useState<string[] | null>(null);
  const [currentPassword, setCurrentPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    getConfig()
      .then((config) => setFeatureEnabled(config.features.two_factor_auth))
      .catch(() => setFeatureEnabled(false));
  }, []);

  useEffect(() => {
    if (!setup) return;

    let cancelled = false;
    QRCode.toDataURL(setup.qr_code_url)
      .then((url) => {
        if (!cancelled) setQrDataUrl(url);
      })
      .catch(() => {
        if (!cancelled) setQrDataUrl(null);
      });

    return () => {
      cancelled = true;
    };
  }, [setup]);

  async function handleEnable() {
    setError(null);
    setSubmitting(true);
    try {
      const result = await enableTwoFactor();
      setSetup(result);
      setQrDataUrl(null);
      setRecoveryCodes(null);
    } catch (err) {
      setError(errorMessage(err));
    } finally {
      setSubmitting(false);
    }
  }

  async function handleConfirm() {
    setError(null);
    setSubmitting(true);
    try {
      const result = await confirmTwoFactor(confirmationCode);
      setRecoveryCodes(result.recovery_codes);
      setSetup(null);
      setQrDataUrl(null);
      setConfirmationCode("");
      await refresh();
    } catch (err) {
      setError(errorMessage(err));
    } finally {
      setSubmitting(false);
    }
  }

  async function handleDisable() {
    setError(null);
    setSubmitting(true);
    try {
      await disableTwoFactor(currentPassword);
      setCurrentPassword("");
      setRecoveryCodes(null);
      await refresh();
    } catch (err) {
      setError(errorMessage(err));
    } finally {
      setSubmitting(false);
    }
  }

  async function handleViewRecoveryCodes() {
    setError(null);
    setSubmitting(true);
    try {
      const result = await getRecoveryCodes();
      setRecoveryCodes(result.recovery_codes);
    } catch (err) {
      setError(errorMessage(err));
    } finally {
      setSubmitting(false);
    }
  }

  async function handleRegenerateRecoveryCodes() {
    setError(null);
    setSubmitting(true);
    try {
      const result = await regenerateRecoveryCodes();
      setRecoveryCodes(result.recovery_codes);
    } catch (err) {
      setError(errorMessage(err));
    } finally {
      setSubmitting(false);
    }
  }

  if (featureEnabled === null) {
    return <p className="text-sm text-zinc-500">Loading...</p>;
  }

  if (!featureEnabled) {
    return (
      <div className="rounded border border-black/10 p-4 text-sm dark:border-white/10">
        <p className="font-medium">Two-factor authentication</p>
        <p className="mt-1 text-zinc-500">
          This feature is built but not yet turned on for this app.
        </p>
      </div>
    );
  }

  return (
    <div className="rounded border border-black/10 p-4 text-sm dark:border-white/10">
      <p className="font-medium">Two-factor authentication</p>

      {error && <p className="mt-2 text-red-600">{error}</p>}

      {user?.two_factor_enabled ? (
        <div className="mt-3 flex flex-col gap-3">
          <p className="text-zinc-500">Two-factor authentication is enabled on your account.</p>

          {recoveryCodes && (
            <div className="rounded bg-black/5 p-3 font-mono text-xs dark:bg-white/10">
              {recoveryCodes.map((code) => (
                <div key={code}>{code}</div>
              ))}
            </div>
          )}

          <div className="flex flex-wrap gap-3">
            <button
              type="button"
              onClick={handleViewRecoveryCodes}
              disabled={submitting}
              className="text-sm font-medium underline disabled:opacity-50"
            >
              View recovery codes
            </button>
            <button
              type="button"
              onClick={handleRegenerateRecoveryCodes}
              disabled={submitting}
              className="text-sm font-medium underline disabled:opacity-50"
            >
              Regenerate recovery codes
            </button>
          </div>

          <div className="flex items-end gap-3">
            <label className="flex flex-col gap-1 text-sm">
              Current password
              <input
                type="password"
                value={currentPassword}
                onChange={(e) => setCurrentPassword(e.target.value)}
                className="rounded border border-black/10 px-3 py-2 dark:border-white/10"
              />
            </label>
            <button
              type="button"
              onClick={handleDisable}
              disabled={submitting || !currentPassword}
              className="rounded border border-red-600 px-4 py-2 text-sm font-medium text-red-600 disabled:opacity-50"
            >
              Disable
            </button>
          </div>
        </div>
      ) : setup ? (
        <div className="mt-3 flex flex-col gap-3">
          <p className="text-zinc-500">
            Scan this QR code with your authenticator app, or enter the secret
            manually.
          </p>
          {qrDataUrl && (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={qrDataUrl} alt="Two-factor setup QR code" className="h-40 w-40" />
          )}
          <p className="break-all font-mono text-xs text-zinc-500">{setup.secret}</p>

          <div className="rounded bg-black/5 p-3 text-xs dark:bg-white/10">
            <p className="mb-1 font-medium">Save these recovery codes</p>
            <p className="mb-2 text-zinc-500">
              Each can be used once if you lose access to your authenticator.
            </p>
            <div className="font-mono">
              {setup.recovery_codes.map((code) => (
                <div key={code}>{code}</div>
              ))}
            </div>
          </div>

          <label className="flex flex-col gap-1 text-sm">
            Enter the 6-digit code from your app
            <input
              type="text"
              value={confirmationCode}
              onChange={(e) => setConfirmationCode(e.target.value)}
              className="rounded border border-black/10 px-3 py-2 dark:border-white/10"
            />
          </label>

          <div className="flex gap-3">
            <button
              type="button"
              onClick={handleConfirm}
              disabled={submitting || !confirmationCode}
              className="rounded bg-foreground px-4 py-2 text-sm font-medium text-background disabled:opacity-50"
            >
              Confirm
            </button>
            <button
              type="button"
              onClick={() => {
                setSetup(null);
                setQrDataUrl(null);
              }}
              disabled={submitting}
              className="text-sm text-zinc-500 hover:underline"
            >
              Cancel
            </button>
          </div>
        </div>
      ) : (
        <div className="mt-3">
          <p className="mb-3 text-zinc-500">
            Add an extra layer of security to your account using an
            authenticator app.
          </p>
          <button
            type="button"
            onClick={handleEnable}
            disabled={submitting}
            className="rounded bg-foreground px-4 py-2 text-sm font-medium text-background disabled:opacity-50"
          >
            Enable two-factor authentication
          </button>
        </div>
      )}
    </div>
  );
}

export default function AccountPage() {
  const { user } = useAuth();

  return (
    <RequireAuth>
      <div className="flex flex-col gap-6">
        <div>
          <h1 className="text-xl font-semibold">Account</h1>
          {user && <p className="text-sm text-zinc-500">{user.email}</p>}
        </div>
        <TwoFactorSection />
      </div>
    </RequireAuth>
  );
}
