import type { ReactNode } from "react";
import { AdminSubNav } from "@/components/admin/admin-subnav";
import { RequireAdmin } from "@/components/require-admin";

export default function AdminLayout({ children }: { children: ReactNode }) {
  return (
    <RequireAdmin>
      <AdminSubNav />
      {children}
    </RequireAdmin>
  );
}
