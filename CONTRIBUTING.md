# Contributing

Thanks for considering a contribution! This is a template people clone to run
real shops, so the bar is correctness and clarity over cleverness.

## Before you start

- **Read [`CAPABILITIES.md`](CAPABILITIES.md)** — the full feature map. If the
  thing you want already exists, extend it rather than rebuild it.
- **Read [`docs/architecture.md`](docs/architecture.md)** before touching
  commerce code — it lists the invariants you must not break (integer-pence
  money, decrement-at-order stock, verify-lookback payments, cart_id
  idempotency) and the gotchas that already bit us.

## Setup

```bash
composer setup   # install, .env, key, migrate, seed demo data, npm install + build
composer dev     # serve + queue + logs + vite
```

## The gate (run before every PR)

CI runs these on PHP 8.4 and 8.5 and will block a merge if any fail:

```bash
composer test          # Pint, Larastan (level 7), Pest
npm run types:check    # tsc
npm run lint:check     # eslint
npm run format:check   # prettier
npm run build:ssr      # client + SSR bundles
```

Run the fixers locally first: `vendor/bin/pint`, `npm run lint`, `npm run format`.

## Conventions

- **Every change is tested.** New behaviour gets a Pest test; most are feature
  tests. No browser tests — the suite drives the app directly.
- Commerce mutations live in `app/Actions/**`; controllers, MCP tools and the
  ACP surface are thin adapters over the same actions. Never fork logic into a
  controller.
- Pluggable external integrations follow the Manager + driver pattern (see the
  payment, address-lookup and support-drafter managers) with a `none`/`fake`
  default, so the template runs with zero external keys.
- New features must be added to `CAPABILITIES.md` — `CapabilitiesDocTest`
  fails CI if a payment gateway, command or manager is left undocumented.
- After PHP changes: `vendor/bin/pint --dirty`. After route changes:
  `php artisan wayfinder:generate --with-form`.

## Reporting security issues

Please don't open public issues for vulnerabilities — see
[`SECURITY.md`](SECURITY.md).
