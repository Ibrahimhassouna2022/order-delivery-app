# Quickstart & Validation Guide: Intelligent Delivery & Order Distribution Platform (MVP)

**Feature**: `001-delivery-distribution-platform` | **Date**: 2026-10-10

This guide proves the feature works end-to-end. It covers prerequisites, setup, the automated test command, and manual validation scenarios mapped to the spec's acceptance criteria. It intentionally avoids implementation code, migrations, and full test bodies — those belong in `tasks.md` and the implementation phase.

---

## Prerequisites

- PHP 8.4, Composer, Node 20+ and npm.
- App key and local database (SQLite for dev/test).
- A running queue worker for the 30 s offer-expiry job.
- Optional for real-time/maps manual checks: a Pusher app (key/secret/cluster) and a Google Maps API key.

## Setup (dev)

```bash
composer install
copy .env.example .env   # Windows; use `cp` on Unix
php artisan key:generate
php artisan migrate
npm install
npm run build            # or `npm run dev`
php artisan serve
php artisan queue:work   # separate terminal — required for expiry/notifications/purge
php artisan schedule:work # separate terminal — safety-net expiry
```

Reference config: `config/delivery.php` (pricing/retention), `.env` (`BROADCAST_CONNECTION=pusher`, Pusher + Google Maps keys).

## Automated verification (primary gate)

```bash
php artisan test --compact
```

- Executes the full PHPUnit 12 suite against SQLite with `RefreshDatabase`.
- Existing suites under `tests/Feature/Api/*` must stay green; new work adds coverage alongside them.
- Time-dependent behaviour (30 s expiry) is verified with fakes/time travel, not real waiting.

## Manual validation scenarios

Each scenario lists the actor, the action, and the expected observable outcome tied to a spec acceptance criterion. Use the API endpoints in [`contracts/api-v1.md`](./contracts/api-v1.md) and the entity fields in [`data-model.md`](./data-model.md).

### S1 — Order placement is idempotent (FR-010, SC-009)
1. As a customer, `POST /api/v1/orders` with an `idempotency_key`.
2. Repeat the identical request.
3. **Expected**: exactly one order is created; the repeat returns the same order, not a duplicate. A different payload with the same key returns `409`.

### S2 — Closest-driver assignment with 30 s expiry and re-offer (FR-016, FR-017, SC-003)
1. Two drivers in the same city are `available`; driver A is nearer, driver B farther (A and B have equal completed deliveries in the tie case).
2. Operations dispatches the order (`POST /api/v1/operations/orders/{order}/dispatch`).
3. **Expected**: A receives the offer; `offered_at` set and `expires_at = offered_at + 30 s`.
4. A does not respond; advance time 30 s (or let the worker run).
5. **Expected**: A's offer is `expired`; the order is re-offered to B. With equal distance, the driver with fewer completed deliveries is chosen.

### S3 — Customer live tracking (FR-012, FR-027)
1. As the order's customer, `GET /api/v1/orders/{order}`.
2. Subscribe to `private-order.{id}`.
3. As the assigned driver, `PUT /api/v1/driver/location`.
4. **Expected**: the read returns current status + driver coordinates; a `DriverLocationUpdated` event arrives within 5–10 s. A client that misses the event still re-syncs via the read endpoint.

### S4 — Status projection (FR-012; research R-9)
1. Progress an order through restaurant accept/prepare/ready → assigned → picked up → delivered.
2. **Expected**: the customer-visible status only ever shows `placed → assigned → picked_up → delivered`; internal restaurant sub-states are not exposed to customers.

### S5 — Cancellation rules (FR-041)
1. Customer cancels while no active assignment exists → **Expected**: `cancelled`, `cancelled_at` set.
2. Customer attempts to cancel after a driver is assigned → **Expected**: `409`, order unchanged.
3. Operations cancels a pre-delivery order → **Expected**: succeeds.

### S6 — Delivery credits wallet exactly once (FR-021, SC-005)
1. Driver picks up, then delivers (`POST /driver/assignments/{assignment}/deliver`).
2. Retry the deliver request.
3. **Expected**: wallet shows a single `delivery_credit` for the order; no double credit.

### S7 — Payout (FR-022)
1. Driver with a positive balance calls `POST /api/v1/driver/payouts` (idempotent).
2. **Expected**: a `payout_debit` ledger entry is written once; the balance decreases; a retry does not double-debit.

### S8 — Ratings (FR-013)
1. Customer rates the restaurant and the driver after delivery.
2. **Expected**: score 1–5 accepted once per order; a second rating for the same order is rejected.

### S9 — Location consent gate (FR-032, FR-035, SC-011)
1. Without an active consent, `PUT /driver/location` → **Expected**: rejected.
2. Grant consent, retry → **Expected**: accepted.
3. Revoke consent → **Expected**: tracking stops; `driver_locations` is no longer updated.

### S10 — Account deletion and retention (FR-033, FR-035, SC-011)
1. `DELETE /api/v1/account`.
2. **Expected**: tokens revoked and consent revoked; a purge job is scheduled for 30 days later.
3. When the job runs → **Expected**: personal data anonymized/purged; legally required financial records retained de-identified.

### S11 — Restaurant onboarding gate and order receipt (FR-024, FR-023)
1. With fewer than 20 assisted restaurants, attempt self-service registration → **Expected**: blocked (`restaurant.self-registration` gate).
2. As a restaurant member, `GET /api/v1/restaurant/orders` → **Expected**: only that restaurant's orders are visible; another restaurant's orders are not.

### S12 — Operations monitoring (FR-029)
1. As an admin, `GET /api/v1/operations/orders` filtered by status/city/restaurant.
2. **Expected**: a live list of orders; `private-operations` receives `OrderPlaced`/status events.

### S13 — Localization, RTL, accessibility, low-bandwidth (FR-037, FR-038, FR-039, SC-001)
1. Switch locale ar ↔ en.
2. **Expected**: Arabic renders RTL (`dir="rtl"`) with mirrored spacing; English renders LTR.
3. Keyboard-only navigation: all primary flows (order, track, accept offer) are operable; controls have labels and visible focus; touch targets ≥ 44 px.
4. Throttle to 3G: primary screens remain usable; location updates stay within the 5–10 s cadence.

### S14 — Offline retry (FR-040, SC-010)
1. Place/accept an action while offline, then restore connectivity.
2. **Expected**: the write retries with its idempotency key and lands exactly once; the client re-syncs from the read endpoints.

### S15 — Audit trail (FR-030)
1. Perform a sensitive operation (role change, onboarding/status change, cancellation, payout, deletion, consent change).
2. **Expected**: a corresponding `audit_logs` record exists with actor, action, subject, and metadata.

### S16 — Performance (SC-012)
1. Under the pilot load profile (a few hundred orders/day), exercise the primary endpoints.
2. **Expected**: p95 < 2 s for most requests and < 5 s for assignment.

---

## Exit criteria

- `php artisan test --compact` is green (existing + new suites).
- All S1–S16 scenarios pass.
- No unresolved `[NEEDS CLARIFICATION]` remain in `spec.md`, and the Constitution Check in `plan.md` still reads PASS after implementation.
