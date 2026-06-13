# Capabilities — everything this shop does

The single map of every feature: what it is, where it lives, how to switch it
on. **Read this before building anything** — if it's already here, don't
rebuild it. For *why* and *how it's built*, follow the detail pointers to
[`README.md`](README.md) (merchant), [`docs/architecture.md`](docs/architecture.md)
(developer/AI), and [`AGENTS.md`](AGENTS.md) (installing agent).

`tests/Feature/CapabilitiesDocTest.php` keeps this file honest: every source
path and env flag it cites must exist, and every payment gateway, console
command, and swappable driver in the codebase must appear here. Adding a
feature without listing it fails CI — that's the point.

Toggle column: **always on** means no config; a backticked env assignment
(e.g. the `PAYMENT_GATEWAY` row below) means set it in `.env`. Unset optional
flags mean the feature is simply off (or 404s).

---

## Storefront & catalogue

| Feature | Where | Toggle |
| --- | --- | --- |
| Products with options → variants (every product ≥1 variant) | `app/Models/Product.php`, `app/Models/ProductVariant.php` | always on |
| Categories (adjacency-list tree) | `app/Models/Category.php` | always on |
| Product listing, search, filters | `app/Http/Controllers/Storefront/ProductController.php` | always on |
| Full-text search (DB engine, env-swappable to Meili/Algolia) | Scout on `Product` | `SCOUT_DRIVER=database` |
| Responsive images (AVIF→WebP), placeholder fallback | `app/Models/Product.php`, `resources/js/components/storefront/product-image.tsx` | always on |
| SEO: `<Seo>`, JSON-LD Product/Breadcrumb/AggregateRating, canonicals | `resources/js/components/seo.tsx` | always on |
| Markdown static pages (terms, privacy, returns, about) | `app/Http/Controllers/Storefront/PageController.php` | always on |

## Basket & checkout

| Feature | Where | Toggle |
| --- | --- | --- |
| Cart (ulid token = guest/agent identity) | `app/Models/Cart.php`, `app/Actions/Cart/ResolveCart.php` | always on |
| Add/update/remove, basket drawer + page | `app/Actions/Cart/AddToCart.php` | always on |
| Guest cart merges into account on login | `app/Actions/Cart/MergeCarts.php` | always on |
| Order creation (stock lock + decrement, idempotent on `cart_id`) | `app/Actions/Checkout/CreateOrderFromCart.php` | always on |
| Pre-order totals quote (shared maths) | `app/Actions/Checkout/QuoteCart.php` | always on |
| Shipping zones & methods, free-over thresholds | `app/Models/ShippingZone.php`, `app/Models/ShippingMethod.php` | always on |
| Address type-ahead (Google Places, server-proxied) | `app/AddressLookup/AddressLookupManager.php` | `ADDRESS_LOOKUP=google` |
| Order lifecycle state machine | `app/Enums/OrderStatus.php` | always on |
| Abandoned-payment sweep + restock | `app/Console/Commands/ExpireAbandonedPayments.php` (`payments:expire-abandoned`) | always on (hourly) |

## Payments

Driver-based — add a provider = one class implementing `PaymentGateway` +
a `create{Name}Driver` on the manager. Trust model: redirects/webhooks are
triggers only; `verify()` is the sole authority.

| Feature | Where | Toggle |
| --- | --- | --- |
| Manager / driver resolution | `app/Payments/PaymentManager.php` | `PAYMENT_GATEWAY=fake` |
| Pay-by-bank (GoCardless Instant Bank Pay + signed webhook) | `app/Payments/Gateways/GoCardlessGateway.php` | `PAYMENT_GATEWAY=gocardless` |
| Agent USDC settlement (x402, HTTP-402 dance via facilitator) | `app/Payments/Gateways/X402Gateway.php` | `X402_ENABLED=true` |
| Local/test gateway (scriptable outcomes) | `app/Payments/Gateways/FakeGateway.php` | `PAYMENT_GATEWAY=fake` |
| Self-healing confirmation (re-verify + poll while pending) | `app/Http/Controllers/Storefront/PaymentController.php` | always on |
| Manual refund recording | `app/Actions/Orders/RecordRefund.php` | always on |

## Discounts & VAT

| Feature | Where | Toggle |
| --- | --- | --- |
| Discount codes (percent/fixed, min spend, window, max uses) | `app/Models/Discount.php`, `app/Actions/Cart/ApplyDiscount.php` | always on |
| Once-per-customer codes (email or account) | `app/Models/Discount.php` | always on |
| UK VAT (inclusive pricing, zero-rated flag, order snapshot) | `app/Support/Vat.php`, `app/Support/ShopSettings.php` | `SHOP_VAT_REGISTERED=true` |

## Digital products

| Feature | Where | Toggle |
| --- | --- | --- |
| `is_digital` products: no stock, no shipping, auto-deliver | `app/Models/Product.php`, `app/Actions/Checkout/CreateOrderFromCart.php` | per-product flag |
| Signed expiring download links (paid-only, fetch cap) | `app/Http/Controllers/Storefront/DownloadController.php` | always on |
| Minimal digital checkout (name + email + country only) | `app/Actions/Checkout/CheckoutData.php` | always on |

## Customer accounts

| Feature | Where | Toggle |
| --- | --- | --- |
| Register/login/2FA/passkeys/reset (Fortify) | `app/Actions/Fortify/CreateNewUser.php` | always on |
| Order history, addresses, notifications | `app/Http/Controllers/Account/` | always on |
| Support tickets (customer side) | `app/Http/Controllers/Account/TicketController.php` | always on |
| Guest orders claimed on verify + login | `app/Actions/Orders/ClaimGuestOrders.php` | always on |

## Admin

| Feature | Where | Toggle |
| --- | --- | --- |
| RBAC (admin/staff/customer, `Gate::before` bypass) | spatie/laravel-permission | always on |
| Dashboard: revenue, orders, AOV, abandonment, repeat rate | `app/Http/Controllers/Admin/DashboardController.php` | always on |
| Products/variants/options/media CRUD + digital file upload | `app/Http/Controllers/Admin/ProductController.php` | always on |
| Orders: status transitions, ship+tracking, refunds, packing slip | `app/Http/Controllers/Admin/OrderController.php` | always on |
| Discounts, shipping, reviews, users, settings, support | `app/Http/Controllers/Admin/` | always on |
| Review moderation (verified-buyer; hide/delete) | `app/Http/Controllers/Admin/ReviewController.php` | always on |

## Automation (the scheduled spine)

| Feature | Where | Toggle |
| --- | --- | --- |
| Abandoned checkout recovery (1h nudge, 24h last-call) | `app/Console/Commands/SendRecoveryEmails.php` (`shop:send-recovery-emails`) | always on (15m) |
| Back-in-stock waitlist + restock fan-out | `app/Jobs/SendBackInStockNotifications.php`, `app/Models/StockNotification.php` | always on |
| Verified-buyer review requests (3 days post-delivery) | `app/Console/Commands/SendReviewRequests.php` (`shop:send-review-requests`) | always on (daily) |
| Weekly metrics digest to admins | `app/Console/Commands/SendWeeklyDigest.php` (`shop:send-weekly-digest`) | always on (Mon) |
| AI support draft replies (grounded in order data) | `app/Support/SupportDrafter/SupportDrafterManager.php` | `SUPPORT_DRAFTER=anthropic` |
| Low-stock admin alerts | `app/Notifications/LowStockNotification.php` | always on |

## Agent surfaces

| Feature | Where | Toggle |
| --- | --- | --- |
| MCP shop server (browse/basket/checkout, 10 tools) | `app/Mcp/Servers/ShopServer.php`, `routes/ai.php` | always on |
| MCP admin server (reporting/orders, OAuth via Passport) | `app/Mcp/Servers/AdminServer.php` | always on (OAuth-gated) |
| Apply discount via MCP | `app/Mcp/Tools/ApplyDiscountToBasket.php` | always on |
| Agentic Commerce Protocol (feed + checkout_sessions) | `app/Http/Controllers/Agent/AcpCheckoutController.php` | `ACP_API_KEY=...` |
| ACP HMAC body signatures | `app/Http/Middleware/AuthenticateAcp.php` | `ACP_SIGNATURE_SECRET=...` |
| x402 agent payment endpoint + Bazaar discovery metadata | `app/Http/Controllers/Agent/X402PaymentController.php` | `X402_ENABLED=true` |
| llms.txt, llms-full.txt, per-product `.md`, sitemap, robots | `app/Http/Controllers/SiteController.php` | always on |

## Frontend & performance

| Feature | Where | Toggle |
| --- | --- | --- |
| Inertia v3 + React 19 SSR (storefront only) | `resources/js/ssr.tsx`, `app/Http/Middleware/DisableInertiaSsr.php` | `INERTIA_SSR_ENABLED=true` |
| AVIF-first images, `fetchpriority` on PDP hero | `resources/js/components/storefront/product-image.tsx` | always on |
| View transitions (card→PDP image morph) | `resources/js/components/storefront/product-card.tsx` | always on |
| Prefetch + deferred props + optimistic basket | `resources/js/pages/storefront/products/show.tsx` | always on |
| Brand theming (OKLch tokens, one CSS block) | `resources/css/app.css` | always on |

## Ops & deploy

| Feature | Where | Toggle |
| --- | --- | --- |
| Multi-stage Docker, supervisord (fpm/nginx/ssr/queue/scheduler) | `Dockerfile`, `docker/supervisord.conf` | always on |
| One-command run (auto APP_KEY, seed-once) | `compose.yaml`, `docker/entrypoint.sh` | `docker compose up` |
| Railway one-click deploy | `railway.json` | always on |
| Media disk (public volume → S3/R2) | `config/filesystems.php` | `MEDIA_DISK=public` |
| Error monitoring | `bootstrap/app.php` | `SENTRY_LARAVEL_DSN=...` |
| Runtime shop settings (cached over config) | `app/Support/ShopSettings.php` | always on |

## The swappable-driver pattern (cross-cutting)

Four subsystems share the same Manager shape — **a `none`/`fake` default plus
real drivers behind one env var.** When you need a pluggable external
integration, copy this, don't invent a new shape:

- Payments — `app/Payments/PaymentManager.php` — `PAYMENT_GATEWAY`
- Address lookup — `app/AddressLookup/AddressLookupManager.php` — `ADDRESS_LOOKUP`
- Support drafts — `app/Support/SupportDrafter/SupportDrafterManager.php` — `SUPPORT_DRAFTER`
- (x402 is a second *rail* alongside payments, gated by `X402_ENABLED`, not a `PAYMENT_GATEWAY` value)

## Where the detail lives

| For… | Read |
| --- | --- |
| Running, branding, before-trading checklist, agent-discovery channels | [`README.md`](README.md) |
| Invariants, action layer, trust model, gotchas, conventions | [`docs/architecture.md`](docs/architecture.md) |
| One-command setup, env manifest, deploy contract | [`AGENTS.md`](AGENTS.md) |
| Project rules loaded into every AI session | [`CLAUDE.md`](CLAUDE.md) |
| Live machine-readable catalogue | `/llms.txt`, `/products/{slug}.md` |
