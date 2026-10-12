# Contract: Broadcasting Channels

**Feature**: `001-delivery-distribution-platform` | **Date**: 2026-10-10

Transport: **Pusher Channels** (server: `pusher/pusher-php-server`; client: `laravel/echo` + `pusher-js`). Channel authorization is declared in `routes/channels.php` and guarded by Sanctum-authenticated sessions/tokens. All channels are **private** (prefixed `private-`); no public channels carry order or personal data.

## Channels

| Channel | Subscriber | Purpose | Authorization rule |
|---------|-----------|---------|--------------------|
| `private-App.Models.User.{userId}` | the user | personal notifications (order status, offer alerts) | `$user->id === (int) $userId` |
| `private-order.{orderId}` | the order's customer, the assigned driver, members of the order's restaurant, admins | live order status + driver location (FR-012/FR-027) | customer owns order, OR driver has an active assignment on the order, OR user is a member of the order's restaurant, OR user role is admin |
| `private-driver.{driverUserId}` | that driver | new offer / offer-expiry alerts (FR-015/FR-016) | `$user->id === (int) $driverUserId` and role is driver |
| `private-operations` | admins | live order/dispatch feed for the console (FR-029) | `$user->role === UserRole::Admin` |

## Events (server → client)

| Event | Channel | Payload (shape) | Trigger |
|-------|---------|-----------------|---------|
| `OrderStatusChanged` | `private-order.{id}`, `private-App.Models.User.{customerId}` | `{ order_id, status, occurred_at }` | order status transition |
| `DriverLocationUpdated` | `private-order.{id}` | `{ order_id, driver_user_id, latitude, longitude, recorded_at }` | driver location write while on an active assignment |
| `DriverOfferReceived` | `private-driver.{driverId}` | `{ assignment_id, order_id, distance_meters, fare, currency_code, expires_at }` | offer created (FR-015) |
| `DriverOfferExpired` | `private-driver.{driverId}`, `private-operations` | `{ assignment_id, order_id, expired_at }` | 30 s expiry or manual expiry |
| `OrderPlaced` | `private-operations`, restaurant members (`private-order.{id}`) | `{ order_id, restaurant_id, total, placed_at }` | order placed |

## Delivery guarantees & constraints

- Events are broadcast after the database transaction commits (use `afterCommit()` / queued broadcasting) so clients never see state that was rolled back.
- Real-time is an enhancement only: every event has a corresponding read endpoint so a client that misses an event (offline/2G) re-syncs via `GET /orders/{order}` / `GET /driver/offers` (FR-040).
- Payloads contain only identifiers and non-sensitive display fields; no personal data beyond what the authorized subscriber may already read.
- Channel authorization failures return `403` and must fail closed.
