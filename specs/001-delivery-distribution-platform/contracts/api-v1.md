# Contract: JSON API v1

**Feature**: `001-delivery-distribution-platform` | **Date**: 2026-10-10

Base path: `/api/v1`. Media type: `application/json`. Authenticated requests use a Sanctum bearer token (`Authorization: Bearer <token>`) or, for the first-party web app, a Sanctum session cookie. All input validation is performed by Form Requests; all responses are shaped by Eloquent API Resources.

**Conventions**
- Success: `200` (read/update), `201` (create). Errors: `401` unauthenticated, `403` forbidden (role/policy), `404` not found, `409` workflow/idempotency conflict, `422` validation.
- Money is serialized as a string with 3 decimal places plus a `currency_code`.
- Mutating endpoints that may be retried accept/return an idempotency key.
- Enums serialize to their string values.

---

## Authentication

| Method | Path | Actor | Status | Notes |
|--------|------|-------|--------|-------|
| POST | `/auth/register` | public | existing | email/password |
| POST | `/auth/login` | public | existing | email/password |
| POST | `/auth/logout` | authenticated | existing | |
| GET | `/auth/user` | authenticated | existing | current identity |
| POST | `/auth/google` | public | **new** | Socialite callback → token/session (FR-001) |
| POST | `/auth/apple` | public | **new** | Socialite callback → token/session (FR-001) |
| POST | `/auth/guest` | public | **new** | issues a guest customer identity/token (FR-001) |

## Accounts & Consent

| Method | Path | Actor | Status | Notes |
|--------|------|-------|--------|-------|
| POST | `/location-consents` | authenticated | existing | grant; records `policy_version` |
| DELETE | `/location-consents` | authenticated | existing | revoke |
| DELETE | `/account` | authenticated | **new** | requests deletion; 30-day purge (FR-033/FR-035) |

## Customer ordering & tracking

| Method | Path | Actor | Status | Notes |
|--------|------|-------|--------|-------|
| GET | `/delivery-locations` | customer | existing | |
| POST | `/delivery-locations` | customer | existing | |
| POST | `/orders/quote` | customer | existing | price preview (FR-008) |
| POST | `/orders` | customer | existing | idempotent placement (FR-010) |
| GET | `/orders/{order}` | customer (owner) | **new** | status + assigned driver + latest driver location (FR-012) |
| POST | `/orders/{order}/cancel` | customer (pre-assignment) | **new** | 409 if an active assignment exists; ops may cancel any pre-delivery (FR-041) |
| POST | `/orders/{order}/restaurant-rating` | customer (owner) | **new** | score 1–5, after delivery (FR-013) |
| POST | `/orders/{order}/driver-rating` | customer (owner) | **new** | score 1–5, after delivery (FR-013) |

**`GET /orders/{order}` response (shape)**
```json
{
  "data": {
    "id": 123,
    "status": "assigned",
    "restaurant": { "id": 4, "name": "..." },
    "items": [ { "item_name": "...", "quantity": 2, "line_total": "12.000" } ],
    "totals": { "items_subtotal": "10.000", "delivery_fee": "2.000", "total": "12.000", "currency_code": "ILS" },
    "driver": { "id": 9, "name": "...", "location": { "latitude": 31.5, "longitude": 34.4, "recorded_at": "..." } },
    "placed_at": "...", "cancelled_at": null
  }
}
```

## Driver

| Method | Path | Actor | Status | Notes |
|--------|------|-------|--------|-------|
| PUT | `/driver/availability` | driver | existing | set Available (FR-014) |
| PUT | `/driver/location` | driver | existing | requires active consent; 5–10 s cadence (FR-039) |
| GET | `/driver/offers` | driver | existing | pending offers with distance + fare (FR-015) |
| POST | `/driver/offers/{assignment}/accept` | driver | existing | only while offered + not expired (FR-016) |
| POST | `/driver/offers/{assignment}/reject` | driver | existing | triggers re-offer to next closest |
| GET | `/driver/assignments` | driver | existing | active/history |
| POST | `/driver/assignments/{assignment}/pickup` | driver | existing | confirm pickup (FR-019) |
| POST | `/driver/assignments/{assignment}/deliver` | driver | existing | confirm delivery → wallet credit (FR-020/FR-021) |
| GET | `/driver/wallet` | driver | existing | credits + ledger |
| POST | `/driver/payouts` | driver | **new** | completes payout from wallet balance; idempotent (FR-022) |

## Restaurant

| Method | Path | Actor | Status | Notes |
|--------|------|-------|--------|-------|
| POST | `/restaurants` | public (gated) | existing | self-service blocked until 20 assisted |
| GET | `/restaurants` | public | existing | discovery |
| GET | `/restaurants/{restaurant}/menu-items` | public | existing | |
| POST | `/restaurants/{restaurant}/menu-items` | restaurant (owner) | existing | |
| PATCH | `/restaurants/{restaurant}/menu-items/{menuItem}` | restaurant (owner) | existing | |
| GET | `/restaurant/orders` | restaurant (member) | **new** | incoming/active orders (FR-023) |
| PATCH | `/restaurant/orders/{order}/status` | restaurant (member) | **new** | accept/prepare/ready transitions |

## Operations / Admin

| Method | Path | Actor | Status | Notes |
|--------|------|-------|--------|-------|
| POST | `/operations/users` | admin | existing | provision ops user |
| POST | `/operations/orders/{order}/offers` | admin | existing | manual offer (kept as override) |
| POST | `/operations/orders/{order}/dispatch` | admin | **new** | auto-select closest eligible driver (FR-017) |
| POST | `/operations/driver-assignments/{assignment}/expire` | admin | existing | manual expiry (kept as override) |
| POST | `/operations/restaurants` | admin | existing | assisted onboarding |
| POST | `/operations/restaurants/{restaurant}/complete-onboarding` | admin | existing | |
| GET | `/operations/orders` | admin | **new** | filter by status/city/restaurant; monitoring (FR-029) |

---

## Broadcast channel contract

Channel authorization lives in `routes/channels.php` (see `contracts/broadcasting-channels.md`).

## Error envelope (existing convention)

Form Request failures return Laravel's standard `422` body:
```json
{ "message": "...", "errors": { "field": ["..."] } }
```
Workflow/idempotency conflicts (`409`) return `{ "message": "..." }`; role/policy failures return `403`; unauthenticated returns `401`.
