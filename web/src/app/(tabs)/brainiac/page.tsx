import { BrainiacAssessment } from "@/components/brainiac/brainiac-assessment";
import { RequireAuth } from "@/components/require-auth";

export default function BrainiacPage() {
  return (
    <RequireAuth>
      <BrainiacAssessment />
    </RequireAuth>
  );
}
