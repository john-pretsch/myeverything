import { TopicManager } from "@/components/admin/topic-manager";
import { RequireAdmin } from "@/components/require-admin";

export default function AdminPage() {
  return (
    <RequireAdmin>
      <TopicManager />
    </RequireAdmin>
  );
}
