# Implementation Plan: Intelligent Delivery & Order Distribution Platform (MVP)

**Branch**: `001-delivery-distribution-platform` | **Date**: 2026-10-10 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/001-delivery-distribution-platform/spec.md`

**Note**: This is a **brownfield** plan. A substantial Laravel JSON API already exists in this repository and implements part of the spec. The plan is therefore split into a **baseline inventory**, a **gap analysis**, and a **forward design** for the remaining work. It does not re-plan what already works.

## Summary

Deliver the approved food-delivery MVP for Gaza and Ramallah: customers order food and track delivery, drivers receive fair (closest-first) order offers and earn into a wallet, restaurants onboard and receive orders, and operations staff monitor the system. The primary requirement is a mobile-first, low-bandwidth, Arabic-RTL web experience backed by an idempotent, role-secured API, with cash-on-delivery payments at launch (card/e-wallet phased in) and real-time order/driver updates.

**Technical approach**: Extend the existing Laravel 13 `api/v1` application (Sanctum tokens, SQLite dev/test, PostgreSQL for production) by closing the gaps below, then add a first-party responsive web frontend (Blade + Alpine.js + Tailwind/Vite) served by Laravel and authenticated with Sanctum first-party (session cookie) auth, plus Google Maps and Pusher-powered real-time updates.

### Existing Implementation Baseline (verified)

| Area | Status | Evidence |
|------|--------|----------|
| Sanctum auth, RBAC roles (customer/driver/restaurant/admin) | **Done** | `routes/api.php`, `app/Enums/UserRole.php`, `app/Http/Middleware/EnsureUserHasRole.php` |
| Email/password register + login | **Done** | `app/Http/Controllers/Api/V1/AuthController.php` |
| Location consent grant/revoke | **Done** | `routes/api.php` (`location-consents`), `app/Actions/Location/*` |
| Delivery locations (customer) | **Done** | `CustomerDeliveryLocationController`, `CreateDeliveryLocationAction` |
| Food order quote + placement with idempotency | **Done** | `BuildFoodOrderQuoteAction`, `PlaceFoodOrderAction`, orders `idempotency_key`/`idempotency_hash` |
| Driver availability + location update | **Done** | `SetDriverAvailabilityAction`, `UpdateDriverLocationAction` |
| Manual driver offers (accept/reject/expire), pickup/deliver transitions | **Done** | `CreateDriverOfferAction`, `RespondToDriverOfferAction`, `TransitionDriverAssignmentAction`, `ExpireDriverOfferAction` |
| Driver wallet read (credits + ledger) | **Done** | `GetDriverWalletAction`, `driver_wallet_ledger_entries` |
| Restaurant self-service registration gating + assisted onboarding + menu items | **Done** | `RegisterSelfServiceRestaurantAction`, `CreateAssistedRestaurantAction`, `EnsureRestaurantSelfRegistrationIsOpen` |
| Operations: provision users, create offers, expire offers, onboard restaurants | **Done** | `app/Http/Controllers/Api/V1/Operations/*` |
| Domain schema (15 domain tables) + factories + feature tests | **Done** | `database/migrations`, `database/factories`, `tests/Feature/Api/*` |
| Audit log model + table | **Partial** | Table/model exist; **no code writes audit records** |

### Gap Analysis (vs. the clarified spec)

**Core dispatch (P1)**
- **G1** — No automatic closest-driver assignment. Ops must pass `driver_user_id`, `distance_meters`, `fare` to create an offer. Spec FR-017 requires the system to select the nearest available driver, tie-broken by fewest completed deliveries.
- **G2** — No automatic 30-second offer expiry/reassignment. `ExpireDriverOfferAction` is operator-triggered only. Spec FR-016 requires expiry after 30 s and re-offer to the next closest driver.
- **G3** — No "completed deliveries" counter for tie-breaking (derivable from `driver_assignments` where status = delivered).

**Order lifecycle & customer (P1)**
- **G4** — No customer order read/tracking endpoint (status + driver location). Spec FR-012.
- **G5** — No order cancellation endpoint (customer pre-assignment, ops post-assignment). Spec FR-041.
- **G6** — No rating submission endpoints although `restaurant_ratings`/`driver_ratings` tables exist. Spec FR-013.
- **G7** — Order status set in code (8 statuses incl. `restaurant_accepted`, `preparing`, `ready_for_pickup`) is richer than the clarified 5-status model. Needs reconciliation (see research R-9).

**Driver (P1/P2)**
- **G8** — No payout completion flow. `WalletEntryType::PayoutDebit` exists but no action/endpoint. Spec FR-022.
- **G9** — No driver assignment history with navigation/live-map handoff beyond the assignment read list.

**Restaurant (P2)**
- **G10** — No restaurant-facing endpoint to receive/list incoming orders. Spec FR-023.

**Operations (P3)**
- **G11** — No order monitoring/list endpoint for the console. Spec FR-029.
- **G12** — No console UI.

**Auth & accounts (P1)**
- **G13** — No Google/Apple sign-in; no guest checkout. Spec FR-001.
- **G14** — No account/data deletion endpoint or 30-day purge job. Spec FR-033, FR-035, SC-011.

**Notifications, maps, real-time (P1/P2)**
- **G15** — No broadcasting: `BROADCAST_CONNECTION=log`, no `routes/channels.php`, no broadcast events. Spec FR-027/FR-028.
- **G16** — No Google Maps integration. Spec FR-012/FR-018/FR-020.

**Compliance, localization, resilience (P1/P2)**
- **G17** — No audit logging wired to sensitive operations (FR-030).
- **G18** — No localization/RTL (no `lang/`, single `en` locale) (FR-037).
- **G19** — No accessibility implementation (FR-038).
- **G20** — No offline fallback/retry (FR-040).
- **G21** — No Arabic privacy policy content or deletion/retention enforcement (FR-032/FR-035).
- **G22** — Payment provider abstraction absent; cash-only path hard-coded (acceptable for launch per clarification, but structure needed for phasing) (FR-026).

**Frontend (P1)**
- **G23** — No application UI (only `resources/views/welcome.blade.php`). Spec is a web app.

## Technical Context

**Language/Version**: PHP 8.4 (composer constraint `^8.3`), Laravel Framework 13.35.0

**Primary Dependencies**: `laravel/sanctum` ^4.3 (present). Pending (require approval): `laravel/socialite` (Google/Apple), `pusher/pusher-php-server` + `laravel/echo`/`pusher-js` (real-time), Google Maps JS API (external, no Composer package). Frontend: Blade + Alpine.js + Tailwind CSS 4 + Vite 8 (present toolchain).

**Storage**: SQLite for local dev and tests (current); **PostgreSQL** for production. Geospatial handled with decimal `latitude`/`longitude` + Haversine distance in SQL/PHP. PostGIS is intentionally deferred (see Complexity Tracking).

**Testing**: PHPUnit 12.5 (`php artisan test --compact`), `RefreshDatabase` against SQLite, existing `tests/Feature/Api/*` conventions.

**Target Platform**: Linux/container server (Docker/Kubernetes-ready per intent); responsive mobile-first web on 2G/3G/4G/5G; Arabic RTL + English.

**Project Type**: Web application — Laravel JSON API (`/api/v1`) plus a first-party server-rendered frontend.

**Performance Goals**: most requests < 2 s (p95); driver assignment < 5 s; driver location updates every 5–10 s; 99.5% uptime (30-day rolling).

**Constraints**: pilot scale; HTTPS/TLS 1.3; encryption at rest; RBAC; idempotent ordering; audit logs for sensitive ops; offline retry; Arabic RTL; WCAG 2.1 AA accessibility target; battery-conscious location; no unlicensed payment service.

**Scale/Scope**: first 20 restaurants; a few hundred customers/drivers; a few hundred orders/day; 2 launch cities (Gaza, Ramallah).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

No ratified project constitution exists — `.specify/memory/constitution.md` is still the unratified template. The de facto governing constraints are the repository's `AGENTS.md` rules, and the gate is evaluated against them:

| Rule (AGENTS.md) | Status |
|------------------|--------|
| Layered architecture: Controllers → Form Requests → Actions (services) → Resources; no business logic in controllers/models | PASS — existing code already follows this; new work must too |
| Form Requests for all input validation | PASS (planned) |
| Policies/Gates for authorization | PASS (planned) — restaurant-scoped access must use policies |
| Eager loading to prevent N+1 | PASS (planned) — required for assignment/ops listings |
| Eloquent API Resources + API versioning | PASS — extend `/api/v1` Resources |
| Jobs for slow/async work (notifications, purge) | PASS (planned) |
| Use factories in tests; no ad-hoc verification scripts | PASS |
| Do not create new base folders; no dependency changes without approval | **ACTION** — `laravel/socialite` + Pusher packages require explicit approval before installation |
| PHP 8 conventions (constructor promotion, return types, casts) | PASS |

**Gate result: PASS**, conditional on user approval for the two new PHP/JS dependencies listed above and recorded in Complexity Tracking.

*Post-Phase-1 re-check*: still PASS. The design adds no controller/model business logic, keeps validation in Form Requests and logic in Actions, and adds broadcasting events + queued jobs (not synchronous controller work).

## Project Structure

### Documentation (this feature)

```text
specs/001-delivery-distribution-platform/
├── plan.md              # This file (/speckit.plan output)
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   ├── api-v1.md
│   └── broadcasting-channels.md
└── tasks.md             # Phase 2 output (/speckit.tasks - NOT created here)
```

### Source Code (repository root — existing layout, extended)

```text
app/
├── Actions/            # business logic (extend: Dispatch/, Ratings/, Payout/, Account/)
├── Console/            # commands (extend: offer expiry safety-net, data purge)
├── Enums/              # backed enums (extend as needed)
├── Events/             # NEW: broadcast events for order/status/location
├── Http/
│   ├── Controllers/Api/V1/        # JSON API controllers (extend)
│   ├── Middleware/                # role + onboarding gates (present)
│   ├── Requests/Api/V1/           # Form Requests (extend)
│   └── Resources/Api/V1/          # API Resources (extend)
├── Jobs/               # NEW: async notifications, offer expiry, account purge
├── Models/             # Eloquent models (present)
├── Policies/           # authorization policies (extend: Order, Restaurant, Assignment)
└── Providers/

config/                 # add: services.php (pusher), broadcasting.php, delivery.php (extend)
database/{migrations,factories,seeders}/
lang/                   # NEW (standard Laravel location): ar/, en/
resources/
├── css/app.css         # Tailwind (present)
├── js/                 # Alpine + Echo bootstrap (extend)
└── views/              # NEW: customer/driver/restaurant/ops UI (Blade)

routes/
├── api.php             # extend v1 endpoints
├── web.php             # add authenticated app shell + locale
├── console.php         # schedule expiry + purge
└── channels.php        # NEW: broadcast channel authorization

tests/
├── Feature/{Api,Http,Models,Console}/   # extend
└── Unit/
```

**Structure Decision**: Continue the existing single Laravel application (no new base folders). Keep the versioned JSON API as the single source of business logic; add a first-party Blade + Alpine + Tailwind frontend in the standard `resources/views` location that consumes it. `lang/` and `routes/channels.php` are standard Laravel locations, not new base folders. Native mobile apps remain deferred; the token-based API stays ready for them.

## Complexity Tracking

| Decision / Violation | Why Needed | Simpler Alternative Rejected Because |
|----------------------|------------|--------------------------------------|
| Geospatial via decimal lat/long + Haversine instead of the intent's PostgreSQL + PostGIS | Pilot scale (a few hundred drivers) and a < 5 s assignment budget; keeps migrations portable across SQLite (tests) and PostgreSQL (prod) with no schema change to existing columns | PostGIS would force a Postgres-only test path, extra migration work, and a spatial index the pilot volume does not need yet; revisit when scale grows |
| Add `laravel/socialite` and Pusher packages (pending approval) | Required to deliver Google/Apple sign-in (FR-001) and real-time updates (FR-027) | Hand-rolling OAuth is error-prone; polling instead of Pusher breaks the 5–10 s update and notification requirements and the intent's stated Pusher choice |
| Blade + Alpine.js frontend instead of a heavy SPA framework | Lowest payload/battery cost on low-end devices and 2G/3G, RTL-friendly, no new JS framework dependency | Inertia + React/Vue adds bundle weight and a new dependency without MVP benefit; native apps are deferred |
