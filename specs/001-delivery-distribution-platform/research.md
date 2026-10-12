# Phase 0 Research: Intelligent Delivery & Order Distribution Platform (MVP)

**Feature**: `001-delivery-distribution-platform` | **Date**: 2026-10-10

Every "NEEDS CLARIFICATION" raised in `plan.md` Technical Context is resolved below. Decisions favor the existing brownfield implementation, the repository's `AGENTS.md` constraints, and the pilot-scale reality from the spec.

---

## R-1: Frontend architecture

- **Decision**: First-party **Blade + Alpine.js + Tailwind CSS 4 (Vite)** web frontend served by the same Laravel app, consuming the existing `/api/v1` JSON API. First-party browser sessions authenticate via **Sanctum SPA (session-cookie) auth**; the existing bearer-token mode is retained for future mobile apps.
- **Rationale**: Consistent with the current stack (only Vite + Tailwind installed); smallest bundle and battery cost for low-end devices and 2G/3G (spec FR-039, SC-001); RTL-friendly; no new JS framework dependency (AGENTS.md: no dependency changes without approval).
- **Alternatives considered**: Inertia.js + React/Vue (richer SPA, but heavier payload and a new dependency); decoupled SPA (extra hosting/CORS/CSRF surface without MVP benefit); Livewire (server round-trips for every interaction add latency on slow networks).

## R-2: Real-time transport

- **Decision**: Use **Pusher Channels** with `pusher/pusher-php-server` (server) and `laravel/echo` + `pusher-js` (client), broadcasting over **private channels** authorized in `routes/channels.php`. Keep `BROADCAST_CONNECTION` configurable so local dev can use `log`/`reverb`.
- **Rationale**: The intent explicitly names Pusher (lines 48, 58, 93); it satisfies 5–10 s driver location and order-status updates without polling. Private channels enforce per-user access.
- **Alternatives considered**: Laravel Reverb (self-hosted, avoids vendor but adds an ops component the intent did not ask for); HTTP polling (breaks the update cadence and battery goals); SSE via Redis.

## R-3: Maps and navigation

- **Decision**: **Google Maps JavaScript API** in the customer tracking view; driver navigation deep-links to the Google Maps app/URL scheme (turn-by-turn) with the restaurant/customer coordinates. The backend stores only coordinates; no map rendering server-side.
- **Rationale**: Intent names Google Maps (R-05 in SPEC-001); deep-linking avoids rebuilding turn-by-turn navigation.
- **Alternatives considered**: Mapbox/Leaflet (not requested); in-app turn-by-turn (out of scope, expensive).

## R-4: Driver proximity and geospatial storage

- **Decision**: Keep decimal `latitude`/`longitude` (7 dp) and compute nearest drivers with a **Haversine expression** over a bounded candidate set (drivers with `is_available = true` and a `driver_locations.recorded_at` within a freshness window, e.g. 60 s). Candidate set is capped (e.g. nearest 50) before scoring.
- **Rationale**: Portable across SQLite (tests) and PostgreSQL (prod); no schema change to existing columns; sufficient for a few hundred drivers within the 5 s budget (spec SC-003). Matches existing `driver_locations` design.
- **Alternatives considered**: PostGIS `geography` + `ST_DWithin` (intent's recommendation; deferred — forces Postgres-only tests and extra migration work at pilot scale); geohash cache in Redis (premature).

## R-5: Dispatch trigger and 30-second offer expiry

- **Decision**: Add a `DispatchClosestDriverAction` that (1) selects the nearest `is_available` eligible driver, tie-broken by fewest **completed deliveries** (count of `driver_assignments` with status `delivered`), and (2) creates the offer via the existing `CreateDriverOfferAction`, recording `offered_at`. On offer creation, dispatch a delayed queued job (`ExpireDriverOfferJob`, delay 30 s) that calls `ExpireDriverOfferAction` and then re-dispatches to the next closest driver. Add a scheduled `offers:expire-stale` command as a safety net for missed or failed jobs.
- **Rationale**: 30 s window and closest-first + fewest-deliveries tie-break come directly from the spec clarifications (FR-016, FR-017); delayed jobs work with the configured `database` queue while a worker runs; the scheduled command covers worker downtime.
- **Alternatives considered**: Laravel scheduler-only minute ticks (too coarse for a 30 s window); synchronous expiry inside the accept/reject request (misses non-responsive drivers); Redis sorted-set timers (extra infra).

## R-6: Payment provider abstraction

- **Decision**: Define a `PaymentGateway` contract with a null/`CashOnDelivery` implementation active at launch and configuration-gated provider entries (`services.payments.providers.*`) that default **disabled** unless an operator explicitly enables a verified provider. `PaymentAttempt` continues to record method/provider/status/idempotency.
- **Rationale**: Launch is cash-on-delivery only (spec clarification; FR-025); the contract keeps card/e-wallet a configuration + adapter change later (FR-026) and prevents enabling an unlicensed provider.
- **Alternatives considered**: Provider SDKs now (blocked on unverified licensing); abstracting order totals around payments (unnecessary — totals are independent of method).

## R-7: Google/Apple sign-in and guest checkout

- **Decision**: Add **`laravel/socialite`** (approval pending) with Google and Apple drivers, issuing the same Sanctum token/session as email/password auth. Guest checkout is modelled as a `User` with `role = customer`, `is_guest = true`, and no password; the guest identity is upgraded/claimed on sign-in or registration (matching on verified email or a client-held guest token).
- **Rationale**: FR-001 requires Google, Apple, and guest access; reusing the existing Sanctum identity avoids a parallel account system. Guest-as-user keeps orders, consent, and ratings fully relational.
- **Alternatives considered**: Order without any user row (breaks FKs and consent/rating ownership); separate guest table (duplicate identity logic).

## R-8: Account deletion and retention enforcement

- **Decision**: `DELETE /api/v1/account` records a deletion request, revokes tokens/consent, and dispatches a `PurgeAccountJob` scheduled to anonymize/purge personal data after 30 days (spec FR-035, SC-011). Order/financial records are retained but de-identified except where a legal retention period applies (configurable in `config/delivery.php` under `retention`).
- **Rationale**: Implements the committed consent/retention baseline while leaving the legal retention window configurable pending counsel.
- **Alternatives considered**: Immediate hard delete (violates financial-record retention and referential integrity); indefinite retention (violates the clarified 30-day purge).

## R-9: Order status reconciliation

- **Decision**: Treat the existing `OrderStatus` enum as the internal lifecycle and expose the **customer/driver-visible** statuses from the clarification: `placed → assigned → picked_up → delivered`, plus `cancelled`. Map internal restaurant-side statuses (`restaurant_accepted`, `preparing`, `ready_for_pickup`) forward to `placed`/`assigned` for customer/driver views. Add `cancelled_at`/`cancelled_by_user_id` and restrict customer cancellation to before an active assignment exists; operations may cancel at any time before delivery.
- **Rationale**: Avoids a destructive enum migration while honouring the clarified 5-status contract (FR-012, FR-041).
- **Alternatives considered**: Dropping the restaurant-side statuses (would break existing offers logic that gates on `Placed`/`ReadyForPickup`); exposing all 8 statuses to customers (contradicts the clarification).

## R-10: Localization and RTL

- **Decision**: Laravel localization with `lang/ar` and `lang/en`, an `APP_LOCALE`/user-locale switch, a `dir="rtl"` document direction for Arabic, and Tailwind logical properties (`ps-*`/`pe-*`, `ms-*`/`me-*`) instead of hard-coded left/right.
- **Rationale**: FR-037; avoids a duplicate RTL stylesheet.
- **Alternatives considered**: RTL-only (drops English support); CSS mirroring sheet (harder to maintain).

## R-11: Accessibility

- **Decision**: Target **WCAG 2.1 AA** for primary flows: semantic landmarks, labels on all form controls, visible focus, keyboard-operable controls, sufficient contrast, ≥ 44 px touch targets, and screen-reader-friendly status announcements for order updates. Validated per-screen in the quickstart.
- **Rationale**: Makes FR-038 testable without inventing a bespoke standard.
- **Alternatives considered**: Unspecified "accessible" (not testable); AAA (disproportionate for MVP).

## R-12: Offline fallback and retry

- **Decision**: Client-side resilience only — the API is already idempotent for orders (existing `idempotency_key`/`idempotency_hash`). The frontend caches the last known order/status in `localStorage`, retries failed writes with exponential backoff, and re-syncs on `online`. Add idempotency keys to new mutating endpoints (rating, cancel, offer accept/reject already keyed by assignment).
- **Rationale**: FR-040 and SC-010; server idempotency already exists for the riskiest write (order placement).
- **Alternatives considered**: Service worker background sync (more moving parts for pilot); server-side offline queue (not meaningful on a web client).

## R-13: Audit logging

- **Decision**: Introduce an `AuditLogger` service and a small `RecordsAudit` concern invoked from Actions for sensitive operations (role changes, restaurant onboarding/status, payment provider enablement, cancellation, payout, account deletion, consent changes). Records use the existing `audit_logs` table.
- **Rationale**: FR-030; the table already exists but is unused.
- **Alternatives considered**: Model observers (implicit/opaque for security events); DB triggers (non-portable).

## R-14: Restaurant-facing order access

- **Decision**: Add restaurant-scoped endpoints (`GET /api/v1/restaurant/orders`, `PATCH /api/v1/restaurant/orders/{order}/status` for accept/prepare/ready) authorized via a `RestaurantPolicy`/membership check against `restaurant_user`. Reuse `OrderResource`.
- **Rationale**: FR-023 ("receive orders"); `restaurant_user` membership already exists.
- **Alternatives considered**: Reusing ops endpoints for restaurants (wrong role boundary).

## R-15: Testing and database strategy

- **Decision**: Keep **PHPUnit 12 + SQLite + `RefreshDatabase`** for all feature tests (existing convention); production uses PostgreSQL. Haversine/proximity queries must be exercised on SQLite in tests. Time-based behaviour (30 s expiry) is tested with `Queue::fake()` + `travel()`/`Carbon::setTestNow` and the scheduled command.
- **Rationale**: Matches existing `tests/Feature/Api/*`; keeps the suite fast and CI-free of a Postgres dependency for MVP.
- **Alternatives considered**: Postgres-only test DB (slower, extra infra); Postgres matrix in CI (added cost not justified at pilot).

## R-16: Performance/uptime verification

- **Decision**: Verify latency/assignment targets with targeted load checks against the pilot-scale profile (SC-012) rather than formal SLO tooling; uptime is tracked externally over the 30-day rolling window (SC-006). No new observability vendor for MVP.
- **Rationale**: Proportional to pilot scale; avoids premature tooling spend.
- **Alternatives considered**: APM/observability stack now (deferred until scale requires it).

## R-17: Notification channel abstraction

- **Decision**: Implement Pusher real-time + database notifications for order status now. Define a `Notifier` contract with a Pusher-backed implementation and a deferred/optional WhatsApp driver; WhatsApp is **not** enabled at launch.
- **Rationale**: The MVP priority names Pusher only (SPEC-001 R-08 decision); FR-028 keeps the messaging path but launch scope is Pusher.
- **Alternatives considered**: WhatsApp-first (provider/account not confirmed); SMS (not requested).
