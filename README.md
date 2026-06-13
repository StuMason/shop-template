# Shop Template

A blank-slate Laravel shop you can clone, brand, and ship in an afternoon.
Sell any physical product: SSR storefront, pay-by-bank checkout, full admin,
and first-class support for AI agents as customers.

> **[`CAPABILITIES.md`](CAPABILITIES.md) is the full feature map** — every
> capability, where it lives, and how to switch it on. Start there.

## What's in the box

- **Storefront** — server-rendered (Inertia v3 SSR), SEO-first: per-page meta,
  Product/Offer/Breadcrumb JSON-LD, sitemap, canonical URLs. React 19 +
  Tailwind 4.
- **Catalogue** — products with options/variants (per-variant SKU, price,
  stock), categories, WebP responsive images (spatie/medialibrary), search
  (Laravel Scout, database engine — swap to Meilisearch with one env var).
- **Basket & checkout** — guest + account baskets that merge on login,
  stock-safe order creation (locked decrements, idempotent submits), flat-rate
  shipping zones with free-over thresholds, guest checkout.
- **Pay by bank** — GoCardless Instant Bank Pay via hosted Billing Request
  Flows. No card forms, no PCI scope. The gateway is a driver
  (`PAYMENT_GATEWAY=gocardless|fake`); add Stripe et al by implementing one
  interface.
- **Admin** — same Inertia/React stack, behind spatie RBAC (admin/staff):
  products with a variant editor and image uploads, orders with a guarded
  status machine and manual refunds, categories, shipping, support, settings.
- **Customer account** — order history, address book, notifications
  (mail + in-app), support tickets.
- **AI-ready** — `/llms.txt`, `/llms-full.txt`, `/products/{slug}.md`, an
  AI-welcoming robots.txt, and an **MCP server at `/mcp/shop`**: agents can
  search, build a basket, and get a checkout link — but a human always
  authorises payment in their own banking app.
- **Production-ready** — Dockerfile + supervisord (php-fpm, nginx, SSR, queue,
  scheduler) for Coolify, CI (tests on PHP 8.3–8.5, larastan level 7, pint,
  eslint, tsc, SSR build), dependabot with auto-merge, security audits.

## Quickstart

```bash
# 1. Use this template (or clone), then:
composer setup     # installs, migrates, seeds the demo shop, builds assets
composer dev       # server + queue + logs + vite (Herd users: site is already live)
```

Log in as `admin@example.com` / `password` — the demo catalogue, a UK
shipping zone, and the admin role are all seeded.

Payments default to the **fake gateway** locally (every payment succeeds).
For real pay-by-bank, create a free [GoCardless sandbox
account](https://manage-sandbox.gocardless.com/sign-up) and set:

```env
PAYMENT_GATEWAY=gocardless
GOCARDLESS_ACCESS_TOKEN=your-sandbox-token
GOCARDLESS_ENVIRONMENT=sandbox
GOCARDLESS_WEBHOOK_SECRET=your-webhook-secret
```

## Make it yours

1. `.env` — `APP_NAME`, `SHOP_NAME`, `SHOP_TAGLINE`, `SHOP_DESCRIPTION`,
   `SHOP_CONTACT_EMAIL`, `MAIL_FROM_*`.
2. `resources/css/app.css` — the `BRAND TOKENS` block at the top drives every
   button, link and accent.
3. `config/shop.php` — defaults for identity/SEO; runtime values are editable
   in the admin later.
4. Logo components in `resources/js/components/app-logo*.tsx`, favicons in
   `public/`.
5. Replace the demo catalogue (it's seeded by
   `database/seeders/DemoCatalogueSeeder.php`) with real products in the
   admin.
6. Rewrite this README for your shop.

## Deploy to Coolify

The repo ships a multi-stage `Dockerfile` (built on
`ghcr.io/stumason/laravel-coolify-base`, ~2–3 min builds) running php-fpm,
nginx, the Inertia SSR server, a queue worker and the scheduler under
supervisord, with a `/up` healthcheck.

1. Create a Coolify application pointing at your repo (build pack:
   Dockerfile), or run `php artisan coolify:provision` (stumason/laravel-coolify
   is installed).
2. Mount persistent volumes for `/var/www/html/database` (SQLite) and
   `/var/www/html/storage/app/public` (media).
3. Set your `.env` values in Coolify (see `.env.example`); set
   `INERTIA_SSR_ENABLED=true`.
4. Optional: add `COOLIFY_URL`, `COOLIFY_TOKEN` and `COOLIFY_APPLICATION_UUID`
   repo secrets and pushes to `main` deploy automatically after CI passes.

**Scaling up:** swap SQLite for Postgres (`DB_CONNECTION=pgsql` + a Coolify
Postgres service), media to S3/R2 (`MEDIA_DISK=s3` + `AWS_*` vars), search to
Meilisearch (`SCOUT_DRIVER=meilisearch`). No code changes.

## For AI agents

This shop speaks machine. `GET /llms.txt` indexes the catalogue,
`/products/{slug}.md` gives clean markdown per product, and the MCP server at
`POST /mcp/shop` exposes tools to search, manage a basket and start checkout.
Checkout intentionally stops at a signed `pay_url`: payment is authorised by
the human at their own bank. Agents shop; people pay.

## Before you trade (the boring-but-vital list)

1. **Legal pages** — edit `resources/markdown/{terms,privacy,returns,about}.md`
   (they ship as placeholders marked *REPLACE BEFORE TRADING*) and set your
   trading details in **Admin → Settings** (shown in the footer).
2. **Real email** — set `MAIL_MAILER` to a transactional provider
   ([Resend](https://resend.com) or Postmark are painless) and add SPF/DKIM
   DNS records for your sending domain, or confirmations land in spam.
3. **VAT** — if registered, flip it on in **Admin → Settings** (number +
   rate) and mark any zero-rated products on their edit screens. Prices are
   always VAT-inclusive.
4. **Error monitoring** — set `SENTRY_LARAVEL_DSN` (sentry-laravel is wired
   and silent until then).
5. **Backups** — schedule Coolify backups for the volumes (`/data`
   and `storage/app/public/`). One bad day without them is fatal.
6. **GoCardless live credentials** — swap `GOCARDLESS_ENVIRONMENT=live` with
   a live access token and a webhook endpoint secret pointing at
   `/webhooks/gocardless`.

## Digital products

Tick **Digital product** on a product and attach a file: stock and shipping
stop applying, checkout skips delivery, and paid orders auto-complete with
signed 30-day download links in the confirmation email (25 fetches per
line, then the customer needs fresh links from you). Files live on the
private disk, never the public one — on Coolify, add a volume for
`storage/app/private` so deliverables survive redeploys (alongside the
`database/` and `storage/app/public` volumes). Selling digital B2C into the EU has
place-of-supply VAT rules — read up before you do.

## Selling to AI agents

Three surfaces, all optional and all over the same checkout actions:

- **MCP** (`/mcp/shop`) — always on; agents browse, build baskets, and get
  a human pay link.
- **ACP** — set `ACP_API_KEY` to enable the Agentic Commerce Protocol
  (ChatGPT shopping et al.): product feed at `/acp/feed`, checkout
  sessions at `/acp/checkout_sessions`.
- **x402** — set `X402_ENABLED=true` + `X402_PAY_TO` (your wallet) and
  agents can settle orders autonomously in USDC on Base, verified through
  the x402 facilitator. Set `X402_FX_RATE` for non-USD shops.

## Getting found by shopping agents

- **Perplexity Merchant Program** — free, open to merchants shipping to the
  US: submit your catalogue feed (this shop serves one at `/acp/feed`) at
  perplexity.ai/shopping. No gateway requirement.
- **x402 Bazaar / Agentic.Market** — automatic: point
  `X402_FACILITATOR_URL` at Coinbase's CDP facilitator and the index
  catalogs your endpoint from settled payments (delisted after 30 idle
  days — activity is the listing fee).
- **ChatGPT shopping** — application-gated: apply at chatgpt.com/merchants,
  then serve them your ACP feed.
- **Copilot / PayPal Agent Ready** — require Stripe or PayPal as the
  payment gateway; the `PaymentGateway` contract makes adding either a
  single driver class.
- Watch **Google's Universal Commerce Protocol (UCP)** — the emerging open
  standard Shopify has adopted; an adapter here is likely worthwhile once
  the spec settles.

## AI support drafts (optional)

Set `SUPPORT_DRAFTER=anthropic` + `ANTHROPIC_API_KEY` and every customer
ticket message gets a suggested staff reply drafted from the customer's
real order history (status, tracking, items) — one click to adopt, edit,
send. Drafts never auto-send; a human always approves.

## Address type-ahead (optional)

Set `ADDRESS_LOOKUP=google` and a server-side `GOOGLE_PLACES_API_KEY`
(enable **Places API (New)** in Google Cloud — no referrer restrictions
needed, the key never reaches the browser) and checkout + the address book
gain a "Find your address" search. Leave it as `none` and the forms are
plain manual entry. `fake` gives deterministic suggestions for local dev.
Other providers (e.g. getAddress.io for UK PAF data) are one class
implementing `AddressLookupProvider`.

## Commands you'll use

| Command | What it does |
| --- | --- |
| `composer dev` | serve + queue + logs + vite |
| `composer test` | pint check, larastan (level 7), pest |
| `npm run types:check` | TypeScript |
| `npm run build:ssr` | production client + SSR bundles |
| `php artisan payments:expire-abandoned` | abandon stale pending payments (scheduled hourly) |

## License

[MIT](LICENSE)
