"use client";

import { useState } from "react";
import type { StockChart } from "@/lib/market-types";

const WIDTH = 640;
const HEIGHT = 240;
const PAD = { top: 10, right: 8, bottom: 22, left: 8 };

function formatDate(t: number, range: StockChart["range"]): string {
  const date = new Date(t * 1000);
  if (range === "1d") {
    return date.toLocaleTimeString([], { hour: "numeric", minute: "2-digit" });
  }
  if (range === "5d") {
    return date.toLocaleString([], {
      month: "short",
      day: "numeric",
      hour: "numeric",
      minute: "2-digit",
    });
  }
  return date.toLocaleDateString([], {
    year: range === "1mo" || range === "3mo" ? undefined : "numeric",
    month: "short",
    day: "numeric",
  });
}

function formatValue(value: number): string {
  return value.toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

export function PriceChart({ chart }: { chart: StockChart }) {
  const [hover, setHover] = useState<number | null>(null);
  const { points } = chart;

  const closes = points.map((p) => p.c);
  const min = Math.min(...closes);
  const max = Math.max(...closes);
  const span = max - min || 1;
  const innerW = WIDTH - PAD.left - PAD.right;
  const innerH = HEIGHT - PAD.top - PAD.bottom;

  const x = (i: number) =>
    PAD.left + (points.length === 1 ? 0 : (i / (points.length - 1)) * innerW);
  const y = (c: number) => PAD.top + (1 - (c - min) / span) * innerH;

  const path = points
    .map((p, i) => `${i === 0 ? "M" : "L"}${x(i).toFixed(1)},${y(p.c).toFixed(1)}`)
    .join(" ");

  const first = points[0];
  const last = points[points.length - 1];
  const up = last.c >= first.c;
  const stroke = up ? "#16a34a" : "#dc2626";
  const active = hover !== null ? points[hover] : last;
  const changePct = ((active.c - first.c) / first.c) * 100;

  function handleMove(event: React.MouseEvent<SVGSVGElement>) {
    const rect = event.currentTarget.getBoundingClientRect();
    const ratio = (event.clientX - rect.left) / rect.width;
    const index = Math.round(
      ((ratio * WIDTH - PAD.left) / innerW) * (points.length - 1),
    );
    setHover(Math.max(0, Math.min(points.length - 1, index)));
  }

  return (
    <div>
      <div className="mb-1 flex items-baseline justify-between text-sm">
        <span className="font-medium">
          {formatValue(active.c)}
          {chart.currency ? ` ${chart.currency}` : ""}
          <span
            className={`ml-2 text-xs ${up ? "text-green-600" : "text-red-600"}`}
          >
            {changePct > 0 ? "+" : ""}
            {changePct.toFixed(2)}%
          </span>
        </span>
        <span className="text-xs text-zinc-500">
          {formatDate(active.t, chart.range)}
        </span>
      </div>
      <svg
        viewBox={`0 0 ${WIDTH} ${HEIGHT}`}
        className="w-full"
        role="img"
        aria-label={`${chart.symbol} price chart`}
        onMouseMove={handleMove}
        onMouseLeave={() => setHover(null)}
      >
        <path d={path} fill="none" stroke={stroke} strokeWidth="1.5" />
        <text x={PAD.left} y={HEIGHT - 6} fontSize="10" fill="currentColor" opacity="0.5">
          {formatDate(first.t, chart.range)}
        </text>
        <text
          x={WIDTH - PAD.right}
          y={HEIGHT - 6}
          fontSize="10"
          fill="currentColor"
          opacity="0.5"
          textAnchor="end"
        >
          {formatDate(last.t, chart.range)}
        </text>
        <text x={PAD.left} y={PAD.top + 8} fontSize="10" fill="currentColor" opacity="0.5">
          High {formatValue(max)}
        </text>
        <text
          x={PAD.left}
          y={PAD.top + innerH - 4}
          fontSize="10"
          fill="currentColor"
          opacity="0.5"
        >
          Low {formatValue(min)}
        </text>
        {hover !== null && (
          <>
            <line
              x1={x(hover)}
              x2={x(hover)}
              y1={PAD.top}
              y2={PAD.top + innerH}
              stroke="currentColor"
              opacity="0.25"
            />
            <circle cx={x(hover)} cy={y(points[hover].c)} r="3.5" fill={stroke} />
          </>
        )}
      </svg>
    </div>
  );
}
