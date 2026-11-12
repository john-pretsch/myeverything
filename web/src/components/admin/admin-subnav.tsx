"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

export const ADMIN_SECTIONS = [
  { slug: "news", label: "News Feed" },
  { slug: "market", label: "Market Info" },
  { slug: "todo", label: "Todo" },
  { slug: "pro-resources", label: "Pro Resources" },
  { slug: "weather", label: "Weather" },
  { slug: "brainiac", label: "Brainiac" },
] as const;

export function AdminSubNav() {
  const pathname = usePathname();

  return (
    <nav className="mb-4 flex flex-wrap gap-1 border-b border-black/10 dark:border-white/10">
      {ADMIN_SECTIONS.map((section) => {
        const href = `/admin/${section.slug}`;
        const active = pathname === href;
        return (
          <Link
            key={section.slug}
            href={href}
            className={`border-b-2 px-3 py-2 text-sm font-medium ${
              active
                ? "border-foreground text-foreground"
                : "border-transparent text-zinc-500 hover:text-foreground"
            }`}
          >
            {section.label}
          </Link>
        );
      })}
    </nav>
  );
}
