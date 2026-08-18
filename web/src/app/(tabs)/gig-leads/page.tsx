import { RequireAuth } from "@/components/require-auth";

export default function GigLeadsPage() {
  return (
    <RequireAuth>
      <h1 className="mb-2 text-xl font-semibold">Gig Leads</h1>
      <p className="text-sm text-zinc-500">
        Aggregated leads from LinkedIn, Arc, Indeed, and Gun.io. Not wired up
        yet.
      </p>
    </RequireAuth>
  );
}
