import { GigLeadsSubNav } from "@/components/gig-leads/gig-leads-subnav";
import { RequireAuth } from "@/components/require-auth";

export default function GigLeadsInterviewPrepPage() {
  return (
    <RequireAuth>
      <GigLeadsSubNav />
      <div className="rounded border border-black/10 p-4 text-sm dark:border-white/10">
        <p className="font-medium">Interview Prep</p>
        <p className="mt-1 text-zinc-500">Nothing here yet.</p>
      </div>
    </RequireAuth>
  );
}
