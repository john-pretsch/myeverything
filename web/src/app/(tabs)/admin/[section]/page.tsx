import { notFound } from "next/navigation";
import { ADMIN_SECTIONS } from "@/components/admin/admin-subnav";

export default async function AdminSectionPage({
  params,
}: {
  params: Promise<{ section: string }>;
}) {
  const { section } = await params;
  const match = ADMIN_SECTIONS.find((s) => s.slug === section);
  if (!match) notFound();

  return (
    <div className="rounded border border-black/10 p-4 text-sm dark:border-white/10">
      <p className="font-medium">{match.label}</p>
      <p className="mt-1 text-zinc-500">No admin settings yet.</p>
    </div>
  );
}
