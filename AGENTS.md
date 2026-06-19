# AGENTS.md — for AI agents working with this repository

This is a complete, self-contained shop. An agent can install it, run it,
test it, deploy it, and trade through it without human intervention.

`CAPABILITIES.md` is the full feature map — read it before building anything,
so you extend rather than duplicate.

## One-command setup

Containerised — no PHP/Node/database needed on the host:

```bash
docker compose up        # builds, generates APP_KEY, migrates, seeds, serves :8080
```

Or natively (Herd / local PHP + Node):

```bash
composer setup   # install, .env, key, migrate, seed demo catalogue, npm install + build
composer dev     # serve (localhost:8000) + queue + logs + vite
```

Health check: `GET /up` returns 200 when the app is alive.
Test suite: `composer test` (pint + larastan + pest). Frontend gates:
`npm run types:check && npm run lint:check && npm run format:check`.

## Environment manifest

Everything works with the defaults in `.env.example` (SQLite, fake payment
gateway, no external keys). Optional capabilities and the envs that unlock
them:

| Capability | Envs |
| --- | --- |
| Admin login (else `admin@example.com` / `password`) | `ADMIN_EMAIL`, `ADMIN_PASSWORD` |
| Real pay-by-bank | `PAYMENT_GATEWAY=gocardless`, `GOCARDLESS_ACCESS_TOKEN`, `GOCARDLESS_ENVIRONMENT`, `GOCARDLESS_WEBHOOK_SECRET` |
| Agent USDC payments (x402) | `X402_ENABLED=true`, `X402_PAY_TO`, `X402_NETWORK`, `X402_FACILITATOR_URL`, `X402_FX_RATE` (PayAI facilitator: `PAY_AI_KEY`, `PAY_AI_SECRET`) |
| Human "Pay with USDC" checkout (browser wallet) | `X402_ENABLED=true` + `WALLETCONNECT_PROJECT_ID` (free from cloud.reown.com) |
| Agentic Commerce Protocol | `ACP_API_KEY` (+ optional `ACP_SIGNATURE_SECRET`) |
| Print-on-demand fulfilment | `PRINTFUL_API_TOKEN`, `PRINTFUL_STORE_ID`, `PRINTFUL_WEBHOOK_SECRET`, `PRINTFUL_AUTO_CONFIRM` |
| Address type-ahead | `ADDRESS_LOOKUP=google`, `GOOGLE_PLACES_API_KEY` |
| Error monitoring | `SENTRY_LARAVEL_DSN` |

## Surfaces for shopping agents

- `GET /llms.txt` — shop overview; `GET /products/{slug}.md` — per-product markdown
- `POST /mcp/shop` — MCP server: search, basket, checkout (returns a human pay link)
- `GET /acp/feed` + `POST /acp/checkout_sessions` — Agentic Commerce Protocol (Bearer key)
- x402 — `start-checkout` returns an `x402_payment_url` when enabled; HTTP 402 dance, USDC settle
- `POST /mcp/admin` — staff MCP (OAuth via Passport): reporting, orders, shipping

## Deploying

Dockerfile is multi-stage, serves on :8080, health-checks `/up`. Persist
three paths: a data dir for SQLite (set `DB_DATABASE=/data/database.sqlite`
and mount `/data` — NEVER mount `database/` itself, the volume would shadow
new migration files shipped in the image), `storage/app/public`, and
`storage/app/private`.
The entrypoint migrates, provisions the admin from `ADMIN_EMAIL` /
`ADMIN_PASSWORD` (every boot — set them and redeploy to rotate), seeds
(`AUTO_SEED=true`, once), generates Passport keys, and optimizes. See
`docs/architecture.md` before changing commerce code — it lists the invariants.
