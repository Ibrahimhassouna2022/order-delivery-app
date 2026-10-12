---
description: "Task list for Intelligent Delivery & Order Distribution Platform (MVP)"
---

# Tasks: Intelligent Delivery & Order Distribution Platform (MVP)

**Input**: Design documents from `/specs/001-delivery-distribution-platform/`

**Prerequisites**: `plan.md` (required), `spec.md` (required), `research.md`, `data-model.md`, `contracts/api-v1.md`, `contracts/broadcasting-channels.md`, `quickstart.md`

**Tests**: INCLUDED. The feature spec does not request TDD, but this repository treats tests as mandatory (`AGENTS.md`: "Unit and feature tests are more important"; `quickstart.md` exit criteria: `php artisan test --compact` green). Test tasks are written first per story and MUST fail before implementation.

**Organization**: Tasks are grouped by user story so each story is independently implementable and testable.

## Current Implementation Status (brownfield)

A substantial Laravel 13 JSON API already exists and is **not** re-planned here. Verified baseline (see `plan.md` "Existing Implementation Baseline"): Sanctum auth + RBAC, location consent, delivery locations, idempotent order quote/placement, driver availability/location, manual driver offers + pickup/deliver, driver wallet read, restaurant self/assisted onboarding + menu items, operations endpoints, 15 domain tables + factories + `tests/Feature/Api/*` (84 tests green).

Already done during planning (do **not** redo):
- **G22 — Payment provider abstraction**: `app/Actions/Payment/{PaymentGateway,CashOnDeliveryGateway,PaymentGatewayManager,PaymentProviderUnavailableException}.php` + `config/delivery.php` `payments` section. Card/e-wallet phasing is now a config + adapter change.
- **G15 (partial) — Notification seam**: `app/Actions/Notification/{Notifier,LogNotifier,NullNotifier}.php` bound in `AppServiceProvider`; still needs real broadcast/database channels (T034, T083).
- **G7 (partial) — Order status projection**: `OrderStatus::publicStatus()` + `public_status` on `CustomerOrderResource`; still needs the new order columns and tracking usage (T011/T015, T049–T051).

## Path Conventions

Single Laravel application at the repository root (continue the existing layout; **no new base folders**):
- Business logic: `app/Actions/**`
- HTTP: `app/Http/{Controllers,Requests,Resources}/Api/V1/**`
- Events/Jobs/Policies: `app/Events/**`, `app/Jobs/**`, `app/Policies/**`
- Config: `config/**`; Routes: `routes/**`; Views: `resources/views/**`; JS: `resources/js/**`; `lang/**`
- Migrations/factories: `database/**`; Tests: `tests/**`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Dependencies, broadcasting config, localization scaffolding, and frontend build wiring.

- [X] T001 Confirm green baseline before any change by running `php artisan test --compact` from the repository root (expect the existing 84 tests passing).
- [X] T002 [P] — add `laravel/socialite` to `composer.json` (Google/Apple sign-in, FR-001). User approved the dependency; installed `laravel/socialite ^5.31` plus `firebase/php-jwt ^7.2` (declared direct because the Apple driver uses it). Note: socialite v5 no longer ships an Apple driver, so a custom `AppleProvider` is registered via `Socialite::extend('apple')` (see T045).
- [X] T003 [P] — add `pusher/pusher-php-server` to `composer.json` and `laravel-echo` + `pusher-js` to `package.json` (real-time, FR-027). User approved; installed `pusher/pusher-php-server ^7.3`, `laravel-echo` + `pusher-js` (npm).
- [X] T004 [P] Create `config/broadcasting.php` (pusher connection) and add the `pusher` + Google Maps key entries to `config/services.php`.
- [X] T005 [P] Add Echo bootstrap at `resources/js/echo.js` and import it from `resources/js/app.js`; gate it on `import.meta.env.VITE_PUSHER_APP_KEY` (`VITE_PUSHER_APP_KEY`/`VITE_PUSHER_APP_CLUSTER` added to `.env.example`; `npm run build` verified).
- [X] T006 [P] Create localization scaffolding: `lang/en.json`, `lang/ar.json`, `lang/en/validation.php`, `lang/ar/validation.php`, plus `app/Http/Middleware/SetLocale.php` registered in `bootstrap/app.php`.
- [X] T007 [P] Update `vite.config.js` inputs and `resources/css/app.css` Tailwind content globs to cover `resources/views/**` and `resources/js/**`.
- [X] T008 [P] Extend `config/delivery.php` with a `dispatch` section (`offer_window_seconds` = 30, `location_freshness_seconds` = 60, `candidate_limit` = 50) and a `retention` section (`personal_data_purge_days` = 30), read via `env()` only in this file.

> **Completed after user approval (dependencies granted):** T002 (`laravel/socialite ^5.31` + `firebase/php-jwt ^7.2` declared direct), T003 (`pusher/pusher-php-server ^7.3` + `laravel-echo`/`pusher-js`), and T005 (Echo bootstrap). Because socialite v5 removed its bundled Apple driver, the `apple` driver is provided by `app/Actions/Auth/AppleProvider.php` registered via `Socialite::extend()` in AppServiceProvider. Note: T007's outcome was already satisfied — the Vite plugin already bundles `resources/css/app.css` + `resources/js/app.js`, and Tailwind v4 auto-detects `resources/views/**` and `resources/js/**`, so no edits were required.

**Checkpoint**: Toolchain and configuration are ready; no user story is blocked on wiring.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Schema changes, broadcast events/channels, audit logging, policies, and shared resources required by every user story.

**⚠️ CRITICAL**: No user-story work may begin until this phase is complete.

### Schema & models

- [X] T010 Add migration `database/migrations/2026_10_11_000001_add_account_lifecycle_to_users_table.php` — `is_guest` boolean default false, `locale` string(5) nullable, `deletion_requested_at` timestamp nullable, `anonymized_at` timestamp nullable (data-model.md; FR-001/FR-033/FR-035).
- [X] T011 [P] Add migration `database/migrations/2026_10_11_000002_add_cancellation_to_orders_table.php` — `cancelled_at` timestamp nullable, `cancelled_by_user_id` FK nullable `nullOnDelete` (data-model.md; FR-041).
- [X] T012 [P] Add migration `database/migrations/2026_10_11_000003_add_expires_at_to_driver_assignments_table.php` — `expires_at` timestamp nullable, indexed (data-model.md; FR-016).
- [X] T013 [P] Add migration `database/migrations/2026_10_11_000004_add_completed_deliveries_to_driver_profiles_table.php` — `completed_deliveries_count` unsigned int default 0 (data-model.md; FR-017).
- [X] T014 [P] Update `app/Models/User.php`: add `is_guest`/`locale`/`deletion_requested_at`/`anonymized_at` casts, remove `password` from required fillable default path, and add an `isDeletionPending()` helper.
- [X] T015 [P] Update `app/Models/Order.php`: cast `cancelled_at`, add `cancelledBy()` belongsTo relation, and add scopes `scopeDispatchable` and `scopeActiveAssignment`.
- [X] T016 [P] Update `app/Models/DriverAssignment.php`: cast `expires_at` and add `scopePending` (status `offered` and not past `expires_at`).
- [X] T017 [P] Update `app/Models/DriverProfile.php`: cast `completed_deliveries_count`.
- [X] T018 [P] Update factories `database/factories/{UserFactory,OrderFactory,DriverAssignmentFactory,DriverProfileFactory}.php` with guest/locale states, `cancelledAt`/`expiresAt` states, and `completed_deliveries_count` (tests use factories per AGENTS.md).

### Real-time infrastructure

- [X] T019 [P] Create `app/Events/OrderStatusChanged.php` (implements `ShouldBroadcast`, `ShouldDispatchAfterCommit`) exposing `order_id`, `status`, `occurred_at` (contracts/broadcasting-channels.md).
- [X] T020 [P] Create `app/Events/DriverLocationUpdated.php` (`order_id`, `driver_user_id`, `latitude`, `longitude`, `recorded_at`).
- [X] T021 [P] Create `app/Events/DriverOfferReceived.php` (`assignment_id`, `order_id`, `distance_meters`, `fare`, `currency_code`, `expires_at`).
- [X] T022 [P] Create `app/Events/DriverOfferExpired.php` (`assignment_id`, `order_id`, `expired_at`).
- [X] T023 [P] Create `app/Events/OrderPlaced.php` (`order_id`, `restaurant_id`, `total`, `placed_at`).
- [X] T024 Create `routes/channels.php` declaring the four private channels with authorizations exactly as in `contracts/broadcasting-channels.md` (`private-order.{id}` must allow customer-owner OR active-assignment driver OR restaurant member OR admin).
- [X] T025 Register broadcasting in `bootstrap/app.php` (`withBroadcasting(__DIR__.'/../routes/channels.php')`) and set `BROADCAST_CONNECTION=pusher` in `.env.example`.

### Audit, authorization, and shared resources

- [X] T026 Create `app/Actions/Audit/RecordAuditAction.php` writing to the existing `audit_logs` table (actor, action, subject_type/id, metadata) (research R-13; FR-030).
- [X] T027 [P] Create `app/Policies/OrderPolicy.php` — `view` (owner, assigned driver, restaurant member, or admin) and `cancel` (owner pre-assignment; admin pre-delivery).
- [X] T028 [P] Create `app/Policies/DriverAssignmentPolicy.php` — `view`/`respond` only for the assignment's driver, `view` also for admin.
- [X] T029 [P] Create `app/Policies/RestaurantPolicy.php` — menu/order access scoped to `restaurant_user` membership (FR-023).
- [X] T030 Register the three policies via `Gate::policy()` in `app/Providers/AppServiceProvider.php`.
- [X] T031 [P] Create `app/Http/Resources/Api/V1/OrderResource.php` producing the full shape in `contracts/api-v1.md` (restaurant, items, totals, driver + latest location, `status` = public projection via `OrderStatus::publicStatus()`, `cancelled_at`); reuse for customer/restaurant/ops.
- [X] T032 [P] Add `app/Http/Resources/Api/V1/OrderTrackingResource.php` that layers the assigned driver's latest `driver_locations` coordinates onto `OrderResource` (FR-012).
- [X] T033 Create the shared layout `resources/views/layouts/app.blade.php` with `dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"`, a locale switch, and Tailwind logical-property spacing (research R-10).
- [X] T034 Create `app/Actions/Notification/BroadcastNotifier.php` (Pusher + database) and register a `pusher` driver in the `notifications.drivers` map in `config/delivery.php`; keep `log` as the default (research R-17).
- [X] T035 Wire `RecordAuditAction` into existing sensitive actions: `app/Actions/Operations/CreateOperationalUserAction.php`, `app/Actions/Operations/CompleteAssistedRestaurantAction.php`, `app/Actions/Location/GrantLocationConsentAction.php`, `app/Actions/Location/RevokeLocationConsentAction.php`.

**Checkpoint**: Schema, events, channels, policies, audit, and shared resources are ready — user stories can proceed in parallel.

---

## Phase 3: User Story 1 — Customer places and tracks a food order (Priority: P1) 🎯 MVP

**Goal**: A customer signs up/logs in (Google, Apple, or guest), selects a delivery location, browses restaurants, builds a cart, reviews the price, confirms a cash-on-delivery order, tracks it and the driver on a map, cancels before assignment, and rates the restaurant and driver after delivery.

**Independent Test**: A single customer can complete app entry → location → browse → cart → checkout → confirm → live tracking → rating end-to-end (a seeded assignment stands in for the driver side), and account deletion can be requested.

### Tests for User Story 1 (write first, must fail)

- [X] T040 [P] [US1] Feature test social/guest auth in `tests/Feature/Api/AuthSocialTest.php` (guest issues a customer identity; Google/Apple provider flows exercised via a faked Socialite driver; the real Apple identity-token verification is covered in `tests/Unit/AppleProviderTest.php` — FR-001).
- [X] T041 [P] [US1] Feature test tracking read + public status projection in `tests/Feature/Api/CustomerOrderTrackingTest.php` (only `placed|assigned|picked_up|delivered|cancelled` are ever exposed; FR-012, research R-9).
- [X] T042 [P] [US1] Feature test cancellation rules in `tests/Feature/Api/CustomerOrderCancellationTest.php` (owner pre-assignment succeeds; post-assignment 409; FR-041).
- [X] T043 [P] [US1] Feature test ratings in `tests/Feature/Api/CustomerOrderRatingTest.php` (score 1–5 after delivery; second rating rejected; FR-013).
- [X] T044 [P] [US1] Feature test account deletion in `tests/Feature/Api/AccountDeletionTest.php` (request revokes tokens/consent and schedules purge; FR-033/FR-035).

### Implementation for User Story 1

- [X] T045 [P] [US1] Create `app/Actions/Auth/AuthenticateSocialUserAction.php` (Socialite Google/Apple → find-or-create `User`, issue token/session; FR-001). Implemented with the custom `app/Actions/Auth/AppleProvider.php` because socialite v5 removed its Apple driver; `users.email_verified_at` added to the model's fillable list to stamp provider-verified emails.
- [X] T046 [P] [US1] Create `app/Actions/Auth/CreateGuestSessionAction.php` (`role=customer`, `is_guest=true`, no password; claim on later sign-in by verified email; research R-7). Note: required making `users.email` nullable (`2026_10_11_000005_make_users_email_nullable.php`).
- [X] T047 [US1] Add `google`, `apple`, `guest` methods to `app/Http/Controllers/Api/V1/AuthController.php` with Form Requests in `app/Http/Requests/Api/V1/Auth/` (`SocialAuthRequest` validates `access_token`; used by both `google` and `apple`).
- [X] T048 [US1] Add routes for `POST /auth/google`, `POST /auth/apple`, `POST /auth/guest` in `routes/api.php` (rate-limited `throttle:auth-register` like register).
- [X] T049 [P] [US1] Create `app/Actions/Order/GetCustomerOrderAction.php` (eager-load restaurant, items, active assignment + driver + latest driver location; no N+1).
- [X] T050 [US1] Add `show` to `app/Http/Controllers/Api/V1/CustomerOrderController.php` and the route `GET /api/v1/orders/{order}` (authorized by `OrderPolicy@view`).
- [X] T051 [US1] Return `OrderTrackingResource` from T050 (status + assigned driver + latest coordinates; FR-012).
- [X] T052 [P] [US1] Create `app/Actions/Order/CancelOrderAction.php` (customer only while no active assignment; ops/admin pre-delivery; set `cancelled_at`/`cancelled_by_user_id`; record audit; FR-041).
- [X] T053 [US1] Add cancel Form Request in `app/Http/Requests/Api/V1/Customer/CancelOrderRequest.php`, controller method, and route `POST /api/v1/orders/{order}/cancel` (409 when an active assignment exists).
- [X] T054 [P] [US1] Create `app/Actions/Order/RateRestaurantAction.php` and `app/Actions/Order/RateDriverAction.php` (only after `delivered`, one per order, score 1–5; FR-013).
- [X] T055 [US1] Add rating Form Requests in `app/Http/Requests/Api/V1/Customer/`, controller methods, and routes `POST /orders/{order}/restaurant-rating` and `POST /orders/{order}/driver-rating`.
- [X] T056 [P] [US1] Create `app/Actions/Auth/RequestAccountDeletionAction.php` (set `deletion_requested_at`, revoke tokens + active consent) and add `DELETE /api/v1/account` route + controller method (FR-033/FR-035).
- [ ] T057–T063 [US1] Customer views/JS/tracking/Echo/offline/RTL/app-shell routes — **DEFERRED: frontend pass (user instructed backend first).**

**Checkpoint**: Customer ordering, tracking, cancellation, ratings, and deletion work independently and are test-covered.

---

## Phase 4: User Story 2 — Driver receives fair order offers and completes deliveries (Priority: P1)

**Goal**: An approved driver sets Available, receives an offer with distance + fare, accepts or rejects within 30 seconds, is navigated to the restaurant, confirms pickup, delivers on a live map, confirms delivery, and is credited to their wallet.

**Independent Test**: With an order ready for dispatch and two available drivers, the nearest (fewest-completed-deliveries on a tie) receives the offer; non-response within 30 s re-offers to the next closest; accepting driver completes pickup/delivery and the wallet is credited exactly once.

### Tests for User Story 2 (write first, must fail)

- [X] T070 [P] [US2] Feature test closest-driver selection + tie-break in `tests/Feature/Api/AutoDispatchTest.php` (FR-017).
- [X] T071 [P] [US2] Feature test 30 s expiry + re-offer using `Queue::fake()` + `travel()` in `tests/Feature/Api/OfferExpiryTest.php` (FR-016).
- [X] T072 [P] [US2] Feature test `completed_deliveries_count` increments on confirmed delivery in `tests/Feature/Api/DriverCompletedDeliveriesTest.php`.
- [X] T073 [P] [US2] Feature test auto-dispatch endpoint in `tests/Feature/Api/OperationsAutoDispatchTest.php`.

### Implementation for User Story 2

- [X] T074 [P] [US2] Create `app/Actions/Driver/FindClosestAvailableDriverAction.php` — Haversine over available drivers with fresh `driver_locations` (`config('delivery.dispatch.location_freshness_seconds')`), capped to `candidate_limit`, ordered by distance then `completed_deliveries_count` (research R-4/R-5).
- [X] T075 [US2] Create `app/Actions/Driver/DispatchClosestDriverAction.php` — select via T074, create the offer through `CreateDriverOfferAction`, set `expires_at = offered_at + config('delivery.dispatch.offer_window_seconds')`, dispatch `ExpireDriverOfferJob`, broadcast `DriverOfferReceived` (FR-015/FR-017).
- [X] T076 [US2] Update `app/Actions/Driver/CreateDriverOfferAction.php` to set `expires_at` from config on creation and broadcast `DriverOfferReceived` after commit.
- [X] T077 [US2] Create `app/Jobs/ExpireDriverOfferJob.php` (delay 30 s → call `ExpireDriverOfferAction` → re-dispatch to the next closest eligible driver; FR-016).
- [X] T078 [US2] Extend `app/Actions/Driver/ExpireDriverOfferAction.php` to set assignment `expired`, broadcast `DriverOfferExpired`, and trigger re-offer via `DispatchClosestDriverAction` excluding already-offered drivers.
- [X] T079 [US2] Create `app/Console/Commands/ExpireStaleDriverOffersCommand.php` and schedule it every minute in `routes/console.php` as the safety net (research R-5).
- [X] T080 [US2] Increment `completed_deliveries_count` inside the delivery transaction in `app/Actions/Driver/TransitionDriverAssignmentAction.php` and dispatch `OrderStatusChanged` after commit (FR-017/FR-021).
- [X] T081 [US2] Add `dispatch` to `app/Http/Controllers/Api/V1/Operations/DriverDispatchController.php` and route `POST /api/v1/operations/orders/{order}/dispatch` (keeps manual offer as override).
- [X] T082 [P] [US2] Add the dispatch Form Request in `app/Http/Requests/Api/V1/Operations/DispatchOrderRequest.php` (optional `exclude_driver_user_ids`).
- [X] T083 [US2] Wire the `BroadcastNotifier`/events for driver-facing offer alerts (FR-027).
- [X] T084 [US2] Dispatch `OrderStatusChanged` from offer accept (`app/Actions/Driver/RespondToDriverOfferAction.php`) and pickup (`TransitionDriverAssignmentAction.php`) after commit (contracts/broadcasting-channels.md).
- [ ] T085 [US2] Build driver views in `resources/views/driver/` (availability toggle, offer card with 30 s countdown, navigation handoff, pickup/deliver, live map, wallet link).
- [ ] T086 [US2] Driver JS in `resources/js/driver/` — Echo `private-driver.{id}` subscription with polling fallback for offers.
- [ ] T087 [P] [US2] Navigation deep-links in `resources/js/driver/navigation.js` (Google Maps app/URL scheme to restaurant then customer; research R-3).
- [ ] T088 [US2] Apply Arabic RTL + WCAG 2.1 AA to all driver views (FR-037/FR-038).

**Checkpoint**: Automated fair dispatch with 30 s expiry, navigation, delivery, and wallet credit work end-to-end.

---

## Phase 5: User Story 3 — Restaurant onboards and receives orders (Priority: P2)

**Goal**: A restaurant operator signs up, enters a menu with photos, and receives orders through a simplified dashboard.

**Independent Test**: An onboarded restaurant operator sees only their restaurant's orders and can advance an order through accept/prepare/ready, and photos upload to menu items.

### Tests for User Story 3 (write first, must fail)

- [X] T090 [P] [US3] Feature test restaurant order scoping in `tests/Feature/Api/RestaurantOrderTest.php` (members see only their orders; another restaurant's orders return 403/404; FR-023).
- [X] T091 [P] [US3] Feature test restaurant status transitions accept/prepare/ready in `tests/Feature/Api/RestaurantOrderStatusTest.php`.

### Implementation for User Story 3

- [X] T092 [P] [US3] Create `app/Actions/Restaurant/ListRestaurantOrdersAction.php` (membership-scoped, eager-load customer/items).
- [X] T093 [US3] Create `app/Http/Controllers/Api/V1/RestaurantOrderController.php` (`index`) + route `GET /api/v1/restaurant/orders` guarded by `RestaurantPolicy` (FR-023).
- [X] T094 [P] [US3] Create `app/Actions/Restaurant/AdvanceRestaurantOrderStatusAction.php` (accept → `restaurant_accepted`, prepare → `preparing`, ready → `ready_for_pickup`) + route `PATCH /api/v1/restaurant/orders/{order}/status`.
- [X] T095 [US3] Enforce membership scoping via `RestaurantPolicy` on both restaurant endpoints (no cross-tenant reads).
- [X] T096 [P] [US3] Add menu photo upload to `app/Actions/Restaurant/CreateMenuItemAction.php` + `app/Http/Controllers/Api/V1/RestaurantMenuItemController.php` (store `photo_path`; FR-023). Already satisfied — action/controller/resource (`photo_url`) and `RestaurantMenuControllerTest` cover it.
- [ ] T097 [US3] Build restaurant views in `resources/views/restaurant/` (dashboard, incoming/active orders, menu + photo upload). **DEFERRED: frontend pass.**
- [X] T098 [US3] Restaurant real-time: broadcast `OrderPlaced`/`OrderStatusChanged` to restaurant members on `private-order.{id}` (contracts/broadcasting-channels.md). `Notifier::orderPlaced()` added and wired into `PlaceFoodOrderAction`; `OrderStatusChanged` already broadcast from transitions.
- [X] T099 [US3] Ensure restaurant status transitions drive the internal lifecycle that feeds the customer public projection (research R-9; quickstart S4).

**Checkpoint**: A real restaurant can receive and progress an order, and the customer sees the projected status.

---

## Phase 6: User Story 4 — Driver wallet and payout completion (Priority: P2)

**Goal**: Confirmed deliveries accumulate in the driver's wallet and the driver can complete a payout.

**Independent Test**: After one or more confirmed deliveries, the wallet shows the credits and `POST /driver/payouts` records a `payout_debit` once (idempotent).

### Tests for User Story 4 (write first, must fail)

- [X] T100 [P] [US4] Feature test payout in `tests/Feature/Api/DriverPayoutTest.php` (writes `payout_debit` once, decreases balance, retry does not double-debit; FR-022).

### Implementation for User Story 4

- [X] T101 [P] [US4] Create `app/Actions/Driver/CompleteDriverPayoutAction.php` (balance check, idempotency key, `WalletEntryType::PayoutDebit`, record audit; FR-022).
- [X] T102 [US4] Add payout Form Request `app/Http/Requests/Api/V1/Driver/CompletePayoutRequest.php`, method on `app/Http/Controllers/Api/V1/DriverWalletController.php`, and route `POST /api/v1/driver/payouts`.
- [ ] T103 [US4] Build the wallet + payout view `resources/views/driver/wallet.blade.php` (balance, ledger, payout action). **DEFERRED: frontend pass.**
- [X] T104 [US4] Broadcast/notify the payout completion through the `Notifier` contract (FR-027). `Notifier::driverPayoutCompleted()` + `DriverPayoutCompleted` event to the driver's private user channel.

**Checkpoint**: Drivers can withdraw earnings and see an accurate wallet history.

---

## Phase 7: User Story 5 — Operations staff monitor and support the system (Priority: P3)

**Goal**: An admin/operations user monitors live orders and can act on them from a simplified console.

**Independent Test**: An admin signs in, sees a filterable live order list, can cancel a pre-delivery order, and receives `private-operations` updates.

### Tests for User Story 5 (write first, must fail)

- [X] T110 [P] [US5] Feature test operations order monitoring in `tests/Feature/Api/OperationsOrderMonitorTest.php` (filters by status/city/restaurant; admin-only; pre-delivery cancel → 200, delivered → 409; FR-029).

### Implementation for User Story 5

- [X] T111 [P] [US5] Create `app/Actions/Operations/ListOrdersAction.php` (filters + eager-load restaurant/customer/assignment to avoid N+1).
- [X] T112 [US5] Create `app/Http/Controllers/Api/V1/Operations/OrderMonitorController.php` (`index`) + route `GET /api/v1/operations/orders` (FR-029).
- [X] T113 [US5] Add the operations cancel endpoint `POST /api/v1/operations/orders/{order}/cancel` reusing `CancelOrderAction` (pre-delivery; FR-041).
- [ ] T114 [US5] Build operations console views in `resources/views/operations/` (live order list with filters, order detail, cancel action). **DEFERRED: frontend pass.**
- [ ] T115 [US5] Subscribe the console to `private-operations` for `OrderPlaced`/`OrderStatusChanged`/`DriverOfferExpired` (FR-029; contracts/broadcasting-channels.md). **DEFERRED: frontend pass.**

**Checkpoint**: Operations can observe and intervene without manual database work.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Compliance, accessibility, performance, and end-to-end validation that spans stories.

- [X] T120 [P] Create `app/Jobs/PurgeAccountJob.php` — anonymize/purge personal data 30 days after `deletion_requested_at`, retain financial records de-identified; schedule it in `routes/console.php` (FR-035/SC-011). Covered by `tests/Feature/Jobs/PurgeAccountJobTest.php`.
- [X] T121-backend Add `privacy.policy_version` to `config/delivery.php` (+ `DELIVERY_PRIVACY_POLICY_VERSION` in `.env.example`) and stamp it in `app/Actions/Location/GrantLocationConsentAction.php` (FR-032). Arabic content `resources/views/legal/privacy.blade.php` + `lang/ar/privacy.php` **DEFERRED: frontend pass** (and Arabic locale is deferred).
- [X] T122 [P] Complete audit coverage for all sensitive operations (payment-provider enablement, cancellation, payout, account deletion, consent, onboarding, role changes) via `RecordAuditAction` (FR-030). Added `restaurant.created` audits to `CreateAssistedRestaurantAction` (actor = admin) and `RegisterSelfServiceRestaurantAction` (actor = owner); cancellation/payout/deletion/consent/role-change audits already present. Payment-provider enablement is config-driven (no endpoint) — noted N/A.
- [ ] T123 [P] Accessibility audit pass (WCAG 2.1 AA) across all customer/driver/restaurant/ops screens (FR-038). **DEFERRED: frontend pass.**
- [ ] T124 [P] Performance checks against the pilot profile for `SC-012`/`S16` (p95 < 2 s; assignment < 5 s). **DEFERRED: requires load tooling; re-run with the frontend pass.**
- [X] T125 [P] Review every broadcast dispatch to confirm `ShouldDispatchAfterCommit`/`afterCommit()` so clients never see rolled-back state.
- [ ] T126 Execute the manual validation scenarios S1–S16 in `quickstart.md` and record results.
- [X] T127 [P] Update `.env.example` with `BROADCAST_CONNECTION=pusher`, Pusher keys, Google Maps key, and the new `DELIVERY_*` variables.
- [X] T128 Run `php artisan test --compact` (must be green) and re-check the `plan.md` Constitution Check; confirm no `[NEEDS CLARIFICATION]` remain in `spec.md`.
- [ ] T129 [P] **OPTIONAL** Ratify `.specify/memory/constitution.md` (still the unratified template) so future gates are enforceable — requires user approval.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: no dependencies — start immediately.
- **Foundational (Phase 2)**: depends on Setup — **BLOCKS all user stories**.
- **User Stories (Phases 3–7)**: all depend on Foundational; then proceed in parallel or in priority order.
- **Polish (Phase 8)**: depends on the desired user stories being complete.

### User Story Dependencies

- **US1 (P1)**: after Foundational. Its end-to-end test needs a driver, so full validation also needs US2 (the driver side can be seeded until US2 lands).
- **US2 (P1)**: after Foundational. Independent of US1 code; produces the driver side that US1 tracks.
- **US3 (P2)**: after Foundational. Uses the order lifecycle (R-9) shared with US1/US2.
- **US4 (P2)**: after US2 (needs confirmed deliveries to credit).
- **US5 (P3)**: after Foundational; reuses `CancelOrderAction` from US1 (T052).

### Within Each User Story

- Tests first and failing → schema/models → services → endpoints → views/JS → integration.
- Broadcast after commit only.

### Parallel Opportunities

- Setup: T002–T008 all [P].
- Foundational: migrations T010–T013 and models T014–T018 are [P]; events T019–T023 are [P]; policies T027–T029 and resources T031–T032 are [P].
- Per story: all test tasks `[P]`; independent models/services/resources `[P]`.

---

## Parallel Example: User Story 2

```bash
# Tests for US2 together (must fail first):
Task: "Feature test closest-driver selection + tie-break in tests/Feature/Api/AutoDispatchTest.php"
Task: "Feature test 30 s expiry + re-offer in tests/Feature/Api/OfferExpiryTest.php"
Task: "Feature test completed-deliveries counter in tests/Feature/Api/DriverCompletedDeliveriesTest.php"

# Independent implementation pieces:
Task: "Create app/Actions/Driver/FindClosestAvailableDriverAction.php"
Task: "Create app/Jobs/ExpireDriverOfferJob.php"
Task: "Create app/Console/Commands/ExpireStaleDriverOffersCommand.php"
```

---

## Implementation Strategy

### MVP First

1. Phase 1 Setup → 2. Phase 2 Foundational → 3. Phase 3 (US1) → **STOP and validate** US1 independently → deploy/demo.
2. Because US1's end-to-end story needs a driver, the practical P1 slice is **Phase 1 + 2 + US1 + US2** (both are P1), which yields the full order → dispatch → track → deliver → credit loop.

### Incremental Delivery

- After Foundational: ship US1, then US2, then US3, then US4, then US5 — each independently testable and non-breaking.
- Polish (Phase 8) closes compliance/accessibility/performance before launch.

### Parallel Team Strategy

- Team completes Setup + Foundational together.
- Then: Dev A → US1 (customer), Dev B → US2 (dispatch/driver), Dev C → US3 (restaurant); US4 follows US2, US5 follows US1's cancel action.

---

## Notes

- `[P]` = different files, no dependency on incomplete tasks.
- `[US#]` maps each task to its user story for traceability.
- Migrations must remain portable across SQLite (tests) and PostgreSQL (prod) — no PostGIS (plan.md Complexity Tracking).
- Dependency tasks T002/T003 and T005 are **done** (user approved `laravel/socialite` + Pusher packages); T005's Echo bootstrap is verified via `npm run build`.
- Time-dependent behaviour (30 s expiry) is tested with `Queue::fake()` + `travel()`, never real waiting (research R-15).
- Commit after each task or logical group; stop at any checkpoint to validate a story independently.
