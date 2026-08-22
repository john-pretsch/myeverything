import { GigLeadsSubNav } from "@/components/gig-leads/gig-leads-subnav";
import { ResumesSection } from "@/components/gig-leads/resumes-section";
import { RequireAuth } from "@/components/require-auth";

export default function GigLeadsResumesPage() {
  return (
    <RequireAuth>
      <GigLeadsSubNav />
      <ResumesSection />
    </RequireAuth>
  );
}
