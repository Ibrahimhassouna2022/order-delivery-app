# Phase 1 Data Model: Intelligent Delivery & Order Distribution Platform (MVP)

**Feature**: `001-delivery-distribution-platform` | **Date**: 2026-10-10

Derived from `spec.md` Key Entities and the **existing** migrations in `database/migrations/`. Tables marked **(existing)** are already implemented; tables/columns marked **(add)** are planned. All existing migrations use portable Laravel schema types (verified against SQLite dev/test and PostgreSQL production).

**Conventions** (existing): money is `decimal(12,3)` plus a `char(3) currency_code`; enums are stored as strings and cast to backed PHP enums; idempotent writes carry a unique `idempotency_key`; timestamps are `created_at`/`updated_at` unless noted.

---

## Identity & Access

### `users` (existing, extend)
- `id`, `name`, `email` (unique), `email_verified_at`, `password` (nullable after **add** for guests/social-only), `remember_token`, timestamps.
- `role` — string, default `customer`, cast to `UserRole` (`customer` | `driver` | `restaurant` | `admin`); **not mass assignable** (existing `add_role_to_users_table` migration).
- **(add)** `is_guest` — boolean, default false.
- **(add)** `locale` — string(5), nullable (`ar` | `en`).
- **(add)** `deletion_requested_at` — timestamp, nullable (drives the 30-day purge).
- **(add)** `anonymized_at` — timestamp, nullable.
- Relationships: customerOrders (hasMany Order), driverProfile (hasOne), driverAssignments (hasMany), restaurants (belongsToMany via `restaurant_user`), locationConsents (hasMany).

### `personal_access_tokens` (existing) — Sanctum tokens; revoked on account deletion.

### `location_consents` (existing)
- `id`, `user_id` (FK), `granted_at`, `revoked_at` (nullable), `policy_version` (nullable), timestamps; index `(user_id, granted_at)`.
- Rule: no `driver_locations` write or tracking without a currently-granted consent (FR-032, FR-035).

---

## Restaurant Domain

### `restaurants` (existing, extend)
- `id`, `name`, `description` (nullable), `address`, `phone` (nullable), `latitude`/`longitude` (decimal 10,7 nullable), `status` (default `pending`; `RestaurantStatus`: pending|active|rejected), `assisted_onboarding_completed_at` (nullable), `onboarding_method` (default `assisted`; `RestaurantOnboardingMethod`), timestamps.
- Rule: self-service registration is blocked until 20 assisted restaurants exist (existing middleware + `FR-024`).
- **(add)** no schema change required for launch; `photo_path` for restaurant-level photos if needed (menu items already have `photo_path`).

### `menu_items` (existing)
- `id`, `restaurant_id` (FK, cascade), `name`, `description` (nullable), `photo_path` (nullable), `price` (decimal 12,3), `currency_code` (char 3), `is_available` (bool default true), timestamps; index `(restaurant_id, is_available)`.

### `restaurant_user` (existing)
- `id`, `restaurant_id` (FK), `user_id` (FK), `membership_role` (default `owner`), timestamps; unique `(restaurant_id, user_id)`.
- Rule: a restaurant user may only read/modify their own restaurant's menu and orders (`FR-023`; **add** `RestaurantPolicy`).

---

## Ordering Domain

### `delivery_locations` (existing)
- `id`, `user_id` (FK, cascade), `label`, `address`, `latitude`/`longitude` (decimal 10,7 nullable), `delivery_instructions` (nullable), `is_default` (bool default false), timestamps; index `(user_id, is_default)`.

### `orders` (existing, extend)
- `id`, `customer_id` (FK), `restaurant_id` (FK), `delivery_location_id` (FK), `type` (default `food`; extensible to pharmacy/grocery/parcel), `status` (default `placed`; `OrderStatus`), `idempotency_key`, `idempotency_hash` (char 64, nullable), `currency_code` (char 3), `items_subtotal`/`delivery_fee`/`total` (decimal 12,3), `placed_at` (nullable), timestamps.
- Unique `(customer_id, idempotency_key)`; indexes `(status, created_at)`, `(restaurant_id, status)`.
- **(add)** `cancelled_at` timestamp nullable, `cancelled_by_user_id` FK nullable (`users`, nullOnDelete).
- State machine (FR-041; see research R-9):
  - internal: `placed → restaurant_accepted → preparing → ready_for_pickup → assigned → picked_up → delivered`
  - terminal: `cancelled`
  - customer-visible projection: `placed → assigned → picked_up → delivered` (+ `cancelled`)
  - Cancellation rule: customer may cancel only while no active assignment (`offered`/`accepted`/`picked_up`) exists; admin/operations may cancel before `delivered`.

### `order_items` (existing)
- `id`, `order_id` (FK, cascade), `menu_item_id` (FK, nullable, nullOnDelete), `item_name` (snapshot), `unit_price` (decimal 12,3), `quantity` (unsigned int), `line_total` (decimal 12,3), timestamps.

### `restaurant_ratings` (existing, expose)
- `id`, `customer_id` (FK), `order_id` (FK, unique with customer), `restaurant_id` (FK), `score` (unsigned tinyint 1–5), `comment` (nullable), timestamps.
- Rule: only after `delivered`; one rating per order (existing unique constraint) (FR-013).

### `driver_ratings` (existing, expose)
- `id`, `customer_id` (FK), `order_id` (FK, unique with customer), `driver_user_id` (FK), `score` (1–5), `comment` (nullable), timestamps.

---

## Dispatch Domain

### `driver_profiles` (existing)
- `id`, `user_id` (FK, unique, cascade), `is_available` (bool default false), `availability_updated_at` (nullable), timestamps.
- **(add)** `completed_deliveries_count` (unsigned int, default 0) — denormalized counter for the fewest-deliveries tie-break (FR-017), incremented on confirmed delivery. (Derivable from assignments; denormalized for the assignment query budget.)

### `driver_assignments` (existing, extend)
- `id`, `order_id` (FK, cascade), `driver_user_id` (FK), `status` (default `offered`; `DriverAssignmentStatus`: offered|accepted|rejected|picked_up|delivered|expired), `distance_meters` (unsigned int nullable), `fare` (decimal 12,3 nullable), `currency_code` (char 3 nullable), `offered_at`/`responded_at`/`picked_up_at`/`delivered_at` (nullable), timestamps.
- Unique `(order_id, driver_user_id)`; indexes `(order_id, status)`, `(driver_user_id, status)`.
- **(add)** `expires_at` timestamp nullable — set to `offered_at + 30 s` at creation to make expiry queryable and testable (FR-016).
- Rules: only one active assignment (`offered`/`accepted`/`picked_up`) per order; a driver may hold only one active assignment (existing `CreateDriverOfferAction` invariants).

### `driver_locations` (existing)
- `id`, `driver_user_id` (FK, unique, cascade), `latitude`/`longitude` (decimal 10,7), `recorded_at` (timestamp), timestamps.
- Rule: written only with active location consent; freshness window (60 s) bounds the dispatch candidate set (research R-4).

### `driver_wallet_ledger_entries` (existing, expose)
- `id`, `driver_user_id` (FK), `order_id` (FK, nullable), `delivery_credit_order_id` (FK unique, nullable), `entry_type` (`WalletEntryType`: delivery_credit|payout_debit|adjustment), `amount` (decimal 12,3), `currency_code` (char 3), `idempotency_key` (unique), timestamps; index `(driver_user_id, created_at)`.
- Rules: exactly-once delivery credit (guarded by `delivery_credit_order_id` unique); payout writes a `payout_debit` with its own idempotency key (FR-021, FR-022).

---

## Payments

### `payment_attempts` (existing)
- `id`, `order_id` (FK), `method` (`PaymentMethod`: cash_on_delivery|card|e_wallet), `provider` (nullable), `status` (default `pending`; `PaymentStatus`: pending|succeeded|failed|refunded), `amount` (decimal 12,3), `currency_code` (char 3), `provider_reference` (nullable), `idempotency_key`, `metadata` (json nullable), timestamps.
- Unique `(order_id, idempotency_key)`; index `(order_id, status)`.
- Launch rule: only `cash_on_delivery` is created (FR-025). Card/e-wallet attempts are created only when a verified provider is enabled (FR-026).

---

## Compliance & Oversight

### `audit_logs` (existing, wire up)
- `id`, `actor_user_id` (FK nullable, nullOnDelete), `action`, `subject_type`, `subject_id`, `metadata` (json nullable), `created_at`; indexes `(subject_type, subject_id)`, `(actor_user_id, created_at)`.
- Rule: written by the `AuditLogger` for sensitive operations (FR-030; research R-13).

---

## Entity Relationship Summary (text)

- `User (customer)` 1—* `Order` 1—* `OrderItem` *—1 `MenuItem` *—1 `Restaurant`
- `User (customer)` 1—* `DeliveryLocation`; `Order` *—1 `DeliveryLocation`
- `Order` 1—* `DriverAssignment` *—1 `User (driver)`; `Order` 1—1 `RestaurantRating`, 1—1 `DriverRating`
- `User (driver)` 1—1 `DriverProfile`, 1—1 `DriverLocation`, 1—* `DriverWalletLedgerEntry`
- `User (restaurant)` *—* `Restaurant` via `restaurant_user`
- `User` 1—* `LocationConsent`; `Order` 1—* `PaymentAttempt`

## Validation Rules (from requirements)

- Order placement: items belong to the selected restaurant, are available, quantity ≥ 1, delivery location belongs to the customer; idempotency key + request hash enforced (FR-010).
- Assignment: order dispatchable (`placed`/`ready_for_pickup`), driver role = driver, driver available, no competing active assignment (FR-015–FR-017).
- Offer: accept/reject only while `offered` and not past `expires_at`; expiry at 30 s (FR-016).
- Delivery: confirmed only from `picked_up`; credits wallet exactly once (FR-021).
- Ratings: `score` 1–5; only for the customer's own `delivered` order (FR-013).
- Cancellation: customer only pre-assignment; ops/admin pre-delivery (FR-041).
- Consent: tracking disabled without an active consent record (FR-032/FR-035).
- Deletion: anonymize/purge personal data 30 days after `deletion_requested_at`, preserving legally required financial records (FR-035, SC-011).
