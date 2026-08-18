# myeverything.jepflow.io

Personal daily dashboard: a single tabbed web app aggregating news, market
data, weather, a personal to-do list, and gig leads.

## Status

Scaffolded, News Feed and Market Info built, other tabs still stubs. `api/`
is a Laravel 13 app wired to MySQL with Sanctum SPA (cookie) auth:
register/login/logout/me endpoints, `role` column on `users`, and CORS
scoped to the frontend origin. `web/` is a Next.js (App Router) app with an
`AuthProvider`, a tab nav, a login/register page, and stub pages for the
remaining three tabs — `todo` and `gig-leads` are gated behind
`RequireAuth`. Todo, Gig Leads, and Weather are not implemented yet.

Run locally: `php artisan serve --port=8000` in `api/`, `npm run dev` in
`web/` (defaults to port 3000). `api/.env` already points at a local MySQL
database/user created for this project (`myeverything`/`myeverything`).

**This box is also the production host** — there's no separate deploy target,
`api/.env` holds real production config, and the MySQL database it points at
is the live one. `api/config/cors.php` and `SANCTUM_STATEFUL_DOMAINS` accept
a comma-separated list (`FRONTEND_URLS` env) so both
`https://myeverything.jepflow.io` and `http://localhost:3000` work
simultaneously — don't collapse this back to a single origin.

Live at **https://myeverything.jepflow.io** (TLS via certbot, HTTP redirects
to HTTPS). Routing, in `/etc/nginx/sites-available/jepflow.io`:
- `/api/*`, `/sanctum/*`, `/up` → PHP-FPM, `root` = `api/public` (same
  pattern as sibling apps `markets.jepflow.io` / `fit.jepflow.io` on this
  box). The PHP-FPM location must be a **top-level sibling** location, not
  nested inside the path-prefix regex block — `try_files`'s fallback to
  `/index.php` triggers an nginx-internal redirect that re-searches
  locations from the top of the server block, so a nested `\.php$` location
  never gets reached. This is exactly the bug that shipped first; don't
  reintroduce it.
- everything else → `proxy_pass` to the Next.js production server on
  `127.0.0.1:3001`.

Next.js is built (`npm run build`) with `web/.env.production.local` pointing
`NEXT_PUBLIC_API_URL` at `https://myeverything.jepflow.io` — remember this
is inlined into the client bundle at **build time**, so any change to it
needs a rebuild, not just a restart. Served via `systemd` unit
`myeverything-web.service` (`npm run start -- --port 3001`, runs as
`www-data`, `Restart=always`, enabled at boot). After any `web/` change
meant for production: `sudo -u www-data npm run build` then
`systemctl restart myeverything-web`.

The whole `myeverything/` tree is owned by `www-data:www-data` (php-fpm's
user) — if you run composer/npm as root during development, `chown -R
www-data:www-data` before deploying or Laravel can't write to
`storage/`/`bootstrap/cache/`.

`php artisan schedule:run` runs every minute via `www-data`'s crontab (not
systemd) — this is what actually fires `news:fetch` every 15 minutes. If a
future scheduled command silently isn't running, check `crontab -u www-data
-l` first.

## Stack

- **API**: PHP 8.5, Laravel 13 (JSON API only — no server-rendered views)
- **Frontend**: Next.js (separate app, consumes the Laravel API)
- **Database**: MySQL 8
- **Auth**: Laravel session/token auth (Sanctum) with basic roles/permissions,
  consumed by Next.js

Keep the two apps in separate directories at the repo root once scaffolded
(e.g. `api/` for Laravel, `web/` for Next.js). Do not mix Blade views into
this project — Next.js owns all rendering.

## Frontend shape

- Single tabbed interface. Tabs load independently (don't block the whole
  dashboard on one slow tab).
- Some tabs are public, some require a logged-in user (see per-tab notes
  below).
- Logged-out users see the public tabs and a login/register prompt on gated
  tabs, not a hard redirect.

## Auth & permissions

- Standard registration/login (Laravel Sanctum-backed).
- Basic role/permission system — a single "user" role is enough to start;
  design the permission checks so an "admin" role can be added later without
  restructuring.
- Gated tabs (require login): **Todo**, **Gig Leads**.
- Public tabs (no login required): **News Feed**, **Market Info**, **Weather**.

## Tabs

### News Feed (public) — implemented
- Seeded sources: WSJ, CBC, BBC, Al Jazeera, Techdirt (`news_sources`,
  `is_default=true`). RSS ingestion via `php artisan news:fetch`
  (`App\Console\Commands\FetchNewsCommand`, scheduled every 15 min in
  `routes/console.php` — needs a real cron entry running
  `php artisan schedule:run` in any deployed environment).
- Per-user source list: `news_source_user` pivot (`user_id`, `news_source_id`,
  `position`). New users are auto-attached to all `is_default` sources on
  register (`AuthController::register`). Add either an existing catalog
  source (`news_source_id`) or a custom RSS URL (`name` + `feed_url`) via
  `POST /api/news/sources`; remove via `DELETE`; reorder (drives `position`)
  via `PATCH /api/news/sources/reorder`.
- Custom `feed_url`s are validated against SSRF (`App\Support\HostSafety`,
  `App\Rules\SafeFeedUrl`): only public http(s) hosts, checked both at
  add-time and again just before each fetch (DNS can change between the
  two). Keep using this guard for any future feature that fetches a
  user-supplied URL server-side.
- Logged-out visitors get the `is_default` sources, recency-sorted only —
  no personalization, no source management UI.
- "More like this" / "Less like this" (`news_article_feedback`: `user_id`,
  `news_article_id`, `news_source_id`, `direction` ±1) is a **per-source**
  affinity signal, not per-article or per-topic: voting on one article
  shifts that article's *source* up or down for that user. Feed ranking
  (`FeedController`) sorts by `published_at` shifted by
  `affinity_score * 6 hours` — no real pagination, just a `limit` query
  param (default 50, max 100) over the last 300 fetched articles per
  selected sources. If a topic/keyword-level signal is wanted later, that's
  a new dimension on top of this, not a replacement.
- `GET /api/news/sources` and `GET /api/news/feed` are intentionally outside
  `auth:sanctum` middleware — `$request->user()` still resolves correctly
  for logged-in requests because `statefulApi()` (in `bootstrap/app.php`)
  starts the session for any request from `SANCTUM_STATEFUL_DOMAINS`, guard
  resolution doesn't require the middleware. Confirmed working via manual
  testing. Keep this pattern for any other route that should personalize
  for logged-in users but stay visible to guests.

### Market Info (public) — implemented
- `App\Services\Market\MarketDataService` (`GET /api/market/overview`,
  no auth) hits three separate free, keyless providers — pick one to swap
  if any of them starts throttling or disappears:
  - Currencies: `api.frankfurter.dev` (ECB rates, base USD, 6 major
    currencies — CAD/EUR/GBP/JPY/AUD/CHF). Note: `api.frankfurter.app`
    (the older domain) 301-redirects here now; use `.dev` directly.
  - Crypto: CoinGecko `simple/price` (BTC/ETH/SOL/XRP/DOGE, USD + 24h
    change). No key needed at this call volume.
  - Indices: Yahoo Finance's **unofficial** `v8/finance/chart/{symbol}`
    endpoint, called once per index (`^GSPTSE` TSX, `^NYA` NYSE, `^IXIC`
    NASDAQ). Their batch `v7/finance/quote` endpoint now 401s without a
    crumb/cookie — don't try to switch to it without also handling that
    auth handshake. This is unofficial/undocumented and could break or get
    rate-limited without notice; there's no equally-easy authenticated
    alternative that's actually free, so this was a deliberate tradeoff for
    a low-traffic personal dashboard, not an oversight.
  - Top movers: same Yahoo Finance unofficial API, but the
    `v1/finance/screener/predefined/saved` endpoint (`scrIds=day_gainers` /
    `day_losers`, `count=5`, `formatted=false` for clean numeric fields).
    **US markets only** (`region=US` — NYSE/NASDAQ-listed, not TSX; Yahoo's
    predefined screeners don't have a TSX-specific gainers/losers list).
    Also unauthenticated for now; same fragility caveat as the indices call.
  - Each of the four sections is cached independently (`Cache::remember`,
    2 min for crypto, 5 min for currencies/indices/movers, via the existing
    `CACHE_STORE=database`) and fails independently — a dead provider
    returns `{items: [], error: "..."}"` (or `{gainers: [], losers: [],
    error: "..."}` for movers) for just that section instead of 500ing the
    whole endpoint. Keep that resilience pattern for anything added later.
- Frontend has a manual "Refresh" button; no polling/auto-refresh.
- Read-only display — no trading, no portfolio tracking, no auth needed at
  all for this tab.

### Todo (requires login)
- Fully user-defined to-do list. No fixed schema beyond standard task fields
  (title, done state, ordering) unless the user asks for more (due dates,
  tags, etc.).
- Scoped per user — no sharing/collaboration implied by the spec.

### Gig Leads (requires login)
- Aggregates leads from LinkedIn, Arc (arc.dev), Indeed, and Gun.io.
- These platforms generally don't offer open public APIs for this use case —
  expect to need scraping, RSS where available, or manual/email-forwarded
  ingestion. Confirm the intended ingestion method per source before
  building, and check each site's ToS before scraping.
- Store enough per-lead metadata (source, title, link, posted date, seen
  date) to dedupe and let the user mark leads reviewed/dismissed.

### Weather (public)
- Basic forecast plus a radar map, sourced from weather.gc.ca.
- weather.gc.ca has no official public JSON API for the site itself —
  Environment Canada does publish data via other channels (e.g. GeoMet /
  MSC datamart). Confirm the specific data source/endpoint before
  implementing.
- Location: single fixed location is fine for v1 unless the user wants
  location switching.

## Open questions to confirm before/while building

These were unclear or cut off in the original spec — check with the user
rather than guessing silently when you hit them:
- Gig Leads ingestion method per source (scrape vs. RSS vs. manual).
- Weather data source/endpoint (GeoMet, MSC datamart, or other).

## Conventions

- Laravel: standard Laravel 13 project layout, PSR-12, form requests for
  validation, API resources for response shaping.
- Next.js: App Router, fetch the Laravel API via a typed client, keep
  per-tab code isolated (e.g. `app/(tabs)/<tab-name>/`).
- No commented-out code, no speculative abstractions for tabs not yet built.
