import { Suspense } from "react";
import { ResetPasswordForm } from "./reset-password-form";

export default function ResetPasswordPage() {
  return (
    <div className="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center px-4 py-16">
      <h1 className="mb-6 text-xl font-semibold">Set a new password</h1>
      <Suspense fallback={<p className="text-sm text-zinc-500">Loading...</p>}>
        <ResetPasswordForm />
      </Suspense>
    </div>
  );
}
