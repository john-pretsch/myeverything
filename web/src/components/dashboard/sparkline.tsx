const WIDTH = 120;
const HEIGHT = 36;
const PAD = 2;

export function Sparkline({ values }: { values: number[] }) {
  if (values.length < 2) return null;

  const min = Math.min(...values);
  const max = Math.max(...values);
  const span = max - min || 1;
  const points = values
    .map((v, i) => {
      const x = PAD + (i / (values.length - 1)) * (WIDTH - PAD * 2);
      const y = HEIGHT - PAD - ((v - min) / span) * (HEIGHT - PAD * 2);
      return `${x.toFixed(1)},${y.toFixed(1)}`;
    })
    .join(" ");
  const up = values[values.length - 1] >= values[0];

  return (
    <svg
      viewBox={`0 0 ${WIDTH} ${HEIGHT}`}
      className={`h-9 w-full ${up ? "text-green-600 dark:text-green-500" : "text-red-600 dark:text-red-500"}`}
      role="img"
      aria-label={up ? "Trending up" : "Trending down"}
      preserveAspectRatio="none"
    >
      <polyline
        points={points}
        fill="none"
        stroke="currentColor"
        strokeWidth="1.5"
        strokeLinejoin="round"
        strokeLinecap="round"
        vectorEffect="non-scaling-stroke"
      />
    </svg>
  );
}
