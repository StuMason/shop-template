# Architecture

For developers and AI agents working on this codebase. The README covers
running and branding the shop; this covers how it's built, the invariants
that must hold, and the decisions you shouldn't accidentally reverse.

## Domain map

```
Catalogue   Product ─< ProductOption ─< ProductOptionValue
            Product ─< ProductVariant >─< ProductOptionValue
            Product >─< Category (adjacency-list parents)
Basket      Cart (ulid token) ─< CartItem ─> ProductVariant
Checkout    ShippingZone ─< ShippingMethod
Orders      Order ─< OrderItem (snapshot)   Order ─< Payment ─< Refund
Support     Ticket ─< TicketMessage
```

The flow: browse → cart → `CreateOrderFromCart` → pending Order →
`StartPayment` → gateway redirect → verified return/webhook →
`MarkOrderPaid` → fulfilment transitions.

## Invariants — do not break these

1. **Money is integer minor units (pence) everywhere.** Formatting happens
   server-side only (`App\Support\Money`), and formatted strings are sent as
   props. Never format money client-side: SSR and hydration must agree.
2. **Every product has ≥ 1 variant.** A "simple" product is one default
   variant with no option values. Baskets and orders only ever reference
   variants. Admin enforces this (can't delete the last variant).
3. **Stock decrements at order creation, not at payment.** Open-banking
   authorisation takes minutes; decrementing at payment invites oversell.
   `CreateOrderFromCart` locks variants (`lockForUpdate`) inside a
   transaction. Stock returns via `RestockOrderItems` on cancellation or
   abandonment — and only then.
4. **One order per cart.** `orders.cart_id` is unique and is the checkout
   idempotency anchor; double submits and concurrent submits return the
   existing order (see the `UniqueConstraintViolationException` catch).
5. **Gateways are never trusted.** Redirect query params and webhook bodies
   are *triggers only*. The only thing that marks an order paid is
   `PaymentGateway::verify()` — a server-side API lookback. This holds for
   the return controller, both webhook controllers, and the
   `payments:expire-abandoned` sweep.
6. **Order status moves only through `OrderStatus::canTransitionTo()`.**
   Use `Order::transitionTo()`; it throws on illegal moves. The transitions
   table lives in the enum — one place.
7. **Order items and addresses are snapshots.** Catalogue edits and address
   deletions never rewrite order history.
8. **The cart token is a bearer secret.** A `carts.token` ulid identifies
   guests (session) and agents (MCP). Anyone holding it controls that cart —
   never log it or include it in URLs.

## The shared action layer

`app/Actions/**` is the single implementation of every commerce mutation.
Web controllers, MCP tools, and any future API are thin adapters over it:

- `Actions\Cart\ResolveCart` is the only way to obtain a cart (user → active
  cart, else token → cart, else create). Web keeps the token in the session;
  MCP passes it explicitly.
- `Actions\Checkout\CheckoutData` is the DTO both the web FormRequest and the
  MCP `StartCheckout` tool build; address rules are shared via
  `CheckoutData::addressRules()`.

If you add a commerce behaviour, put it in an action and call it from both
surfaces. Don't fork logic into a controller.

## Payments

`PaymentManager` (an `Illuminate\Support\Manager`) resolves the driver from
`PAYMENT_GATEWAY` (`fake` | `gocardless`). Adding a provider = one class
implementing `App\Payments\Contracts\PaymentGateway` (two methods:
`createPayment`, `verify`) + a `create{Name}Driver` method.

- **FakeGateway**: local dev/tests. "Redirects" straight to the return URL;
  script outcomes with `FakeGateway::willReturn($payment, $status)`.
- **GoCardlessGateway**: Instant Bank Pay via hosted Billing Request Flows.
  Webhooks are signature-verified (`Webhook-Signature`, 498 on mismatch) but
  still only trigger a verify-lookback.
- A human always authorises payment at their bank. MCP checkout deliberately
  stops at a signed `pay_url` — keep it that way.

## Frontend / SSR

- Layout is assigned by page-name prefix in **both** `resources/js/app.tsx`
  and `resources/js/ssr.tsx` — change them in lockstep or hydration diverges.
- Only the public storefront renders on the server. `DisableInertiaSsr`
  middleware turns SSR off for auth/settings/account/admin/checkout.
- SSR-safety rules: no `window`/`document`/`localStorage` at render time, no
  default-open Radix portals, server-formatted money/dates only.
- `<Seo>` (resources/js/components/seo.tsx) is required on every storefront
  page. Canonicals are absolutised with the shared `shop.url` prop —
  Inertia's `usePage().url` and wayfinder `.url()` are path-relative.
- Wayfinder generates `@/routes`, `@/actions`, `@/wayfinder` (gitignored).
  Regenerate with `php artisan wayfinder:generate --with-form` — without
  `--with-form` the `.form()` variants vanish and tsc breaks.

## Conventions

- Models use PHP attributes (`#[Fillable]`), `casts()` methods, and full
  PHPDoc property blocks (Larastan level 7 is enforced).
- Backed enums with TitleCase cases; state machines live in the enum.
- Form requests for web validation; MCP tools validate with
  `Validator::make` against the same shared rule sets.
- Admins bypass all gates via `Gate::before`; route groups carry
  `role:admin|staff`. Ownership checks (`abort_unless($x->user_id === ...)`)
  guard account resources.
- Runtime config lives in `App\Support\ShopSettings` (cached key-value over
  `config/shop.php` defaults). Inject the singleton; don't read the table.

## Gotchas that already bit us once

- **Migration filename order matters**: `make:model` in the same second
  sorts alphabetically (`product_option_values` before `product_options`).
  Rename timestamps when tables depend on each other.
- **CI lint needs wayfinder routes**: eslint-plugin-import resolves
  `@/routes/*` differently when the generated files are missing, producing
  phantom import/order errors. lint.yml generates them first.
- **`WithoutModelEvents` on seeders breaks medialibrary** (conversions run
  via model observers). Don't add it back to `DatabaseSeeder`.
- **Media conversions are `nonQueued()`** so uploads and seeds work without
  a running worker. If a shop outgrows this, queue them and run a worker.
- **Tests need built assets**: the blade `@vite` includes the page component,
  so a new page 500s in feature tests until `npm run build`.
- **Scout database engine** searches columns from `toSearchableArray()` at
  query time — there's no index to rebuild, and `whereIn('id', keys())` is
  the pattern for combining it with Eloquent constraints.
- **phpunit.xml pins `PAYMENT_GATEWAY=fake`** (and blanks the GoCardless
  token). Without it, setting a real gateway in `.env` makes the test suite
  call the live payment API. If you add a new external integration, pin its
  env there too.
- **Banks fulfil a beat after authorising.** The return-URL verify can land
  while the billing request is still `pending`; the confirmation page polls
  and re-verifies server-side until settled, so it self-heals without a
  webhook. Don't remove that loop.

## Where things live

| Concern | Path |
| --- | --- |
| Commerce mutations | `app/Actions/{Cart,Checkout,Orders}` |
| Payment drivers | `app/Payments/Gateways` |
| State enums | `app/Enums` |
| MCP server + tools | `app/Mcp`, `routes/ai.php` |
| Agent/SEO endpoints | `app/Http/Controllers/SiteController.php` |
| Storefront pages | `resources/js/pages/storefront` |
| Admin pages | `resources/js/pages/admin` |
| Brand tokens | top of `resources/css/app.css` |
| Shop identity config | `config/shop.php` + `shop_settings` table |
| Production topology | `Dockerfile`, `docker/supervisord.conf` |
