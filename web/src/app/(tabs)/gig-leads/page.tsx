import { RequireAuth } from "@/components/require-auth";
import { GigLeadList } from "@/components/gig-leads/gig-lead-list";

export default function GigLeadsPage() {
  return (
    <RequireAuth>
      <GigLeadList />
    </RequireAuth>
  );
}
