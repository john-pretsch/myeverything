"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

const SUB_TABS = [
  { href: "/gig-leads", label: "Leads" },
  { href: "/gig-leads/resumes", label: "Resumes" },
] as const;

export function GigLeadsSubNav() {
  const pathname = usePathname();

  return (
    <nav className="mb-4 flex gap-1 border-b border-black/10 dark:border-white/10">
      {SUB_TABS.map((tab) => {
        const active = pathname === tab.href;
        return (
          <Link
            key={tab.href}
            href={tab.href}
            className={`border-b-2 px-3 py-2 text-sm font-medium ${
              active
                ? "border-foreground text-foreground"
                : "border-transparent text-zinc-500 hover:text-foreground"
            }`}
          >
            {tab.label}
          </Link>
        );
      })}
    </nav>
  );
}
