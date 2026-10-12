# SPEC-001: Intelligent Delivery & Order Distribution Platform

## Source intent

`intent/delivery-distribution-platform.md` (approved, lines 1-146).

## Objective

Specify an MVP web platform for customer orders and driver delivery operations
in Gaza and Ramallah, with order assignment, tracking, payments, notifications,
and operational oversight. Fairer assignment and better customer transparency
are core goals (intent lines 3-5, 15-19).

## MVP scope

- Mobile-friendly, responsive web app supporting Arabic RTL and English
  (intent line 42).
- Food ordering, driver availability and assignment, customer tracking/status,
  driver wallet/payout, simplified restaurant onboarding, and a simplified
  operations console (intent lines 54-64, 96-106, 132).
- Intended integrations: Laravel Sanctum, Google Maps, Pusher, cash on
  delivery, and selected licensed payment providers (intent lines 45-50,
  54-64, 87-93, 105-111).

## Requirements and acceptance criteria

### R-01 Authentication

**Requirement:** Authenticate users with Laravel Sanctum.
**Acceptance criterion:** Given a valid authenticated session, when a protected
API operation is requested, then Sanctum authenticates the request; given no
valid session, the operation is rejected.
**Trace:** intent lines 45, 54, 92.

**Decision required:** The customer workflow also names Google, Apple, and guest
access (line 24). The intent does not say whether these are MVP authentication
methods or how they relate to Sanctum.

### R-02 Customer ordering

**Requirement:** The initial customer-facing release supports food delivery.
Customers can select a delivery location, browse nearby restaurants and menus,
add food items to a cart, review the final price, confirm an order, receive it,
and rate the restaurant and driver. The data model remains extensible to future
order types, while the initial customer interface displays food only.
**Acceptance criterion:** Given available catalog entries and a selected
restaurant and delivery location, when a customer completes checkout, then the
order contains the selected food items and location, the final price is shown
before confirmation, and the customer can submit ratings after delivery.
**Trace:** intent lines 26-30, 55-56, 96-99.

**Decision required:** Price components and rating scales are not defined.
Grocery and pharmacy are candidates for Phase Two after proof of concept;
other future order types are not part of the initial customer interface
(intent lines 96-99).

### R-03 Driver order lifecycle

**Requirement:** Drivers can become available, receive an order offer with
distance and fare, accept or reject it, confirm pickup, confirm delivery, and
receive the amount in their wallet.
**Acceptance criterion:** Given an available driver and an assigned order, when
the driver accepts it, then pickup and delivery can be confirmed in sequence
and the corresponding amount is credited to the driver's wallet; when the
driver rejects it, then that driver does not advance that order's delivery
lifecycle.
**Trace:** intent lines 33-39, 59, 61.

**Decision required:** The offer response window is described only as a “short
window” (line 35). Fairness is a core goal (lines 3-5, 16), but no assignment
fairness measure or tie-breaking behavior is specified.

### R-04 Assignment performance

**Requirement:** Driver assignment completes in under five seconds.
**Acceptance criterion:** Given a dispatch request with eligible available
drivers, when assignment runs, then the system returns an assignment result in
less than five seconds.
**Trace:** intent lines 59, 69.

**Decision required:** The intent does not define the load, eligible-driver
selection rules, or what result counts as a completed assignment.

### R-05 Maps and tracking

**Requirement:** Use Google Maps for location/navigation and show customers the
order and driver on a map; drivers use live-map navigation.
**Acceptance criterion:** Given an active order with driver location data, when
the customer opens tracking, then the map displays the order's current status
and the driver's latest available location.
**Trace:** intent lines 29, 36, 38, 47, 56, 60, 90.

### R-06 Location update cadence

**Requirement:** Driver location updates occur every 5–10 seconds.
**Acceptance criterion:** Given active driver location tracking, when updates
are measured during a connected session, then each update interval is between
five and ten seconds inclusive.
**Trace:** intent line 70.

### R-07 Payments and price confirmation

**Requirement:** Cash on delivery is the default payment option. Offer card
processing through a licensed third-party gateway and e-wallet options through
licensed providers; present the final price before order confirmation. Do not
operate an unlicensed payment service.
**Acceptance criteria:**

- Given checkout, when the customer reviews the order, then the final price
  and cash-on-delivery option are displayed before confirmation.
- Given a payment attempt using a configured provider, when the provider reports
  success or failure, then the resulting payment state is reflected in the
  order.
- Given a payment provider is enabled in production configuration, when its
  licensing status has not been verified, then the integration is not enabled
  for production.

**Trace:** intent lines 27-28, 49, 57, 87-89, 105-127.

**Decision required:** Select the card gateway and e-wallet provider(s).
PayTabs or MEPS are examples for card processing; Jawwal Pay or Reflect are
examples for e-wallets, not final selections (intent lines 87-89, 105-108).

### R-08 Notifications and order status

**Requirement:** Use Pusher for push notifications and real-time order updates.
**Acceptance criterion:** Given an order status change, when the change is
committed, then subscribed clients receive the corresponding update.
**Trace:** intent lines 48, 58, 60, 91, 93.

**Decision required:** WhatsApp is listed for messaging and alerts (line 91),
but the MVP priorities name Pusher only (line 58); WhatsApp's MVP role is
unspecified.

### R-09 Driver wallet and payout

**Requirement:** Complete the driver wallet and payout flow.
**Acceptance criterion:** Given a successfully delivered order, when its payout
is completed, then the wallet records the credited amount exactly once.
**Trace:** intent lines 39, 61.

**Decision required:** Payout timing, payout destination, fees, and the
relationship between a wallet credit and payout completion are unspecified.

### R-10 Operational oversight

**Requirement:** Provide a simplified operations console (Ops Console) for
operational oversight; a full administrative dashboard is deferred until scale
requires it.
**Acceptance criterion:** Given an authorized admin, when they open the
Ops Console, then they can view order records and their current statuses.
**Trace:** intent lines 12, 64, 103-104, 132.

**Decision required:** The exact monitoring actions and permissions are
unspecified.

### R-11 Security and order integrity

**Requirement:** Use HTTPS/TLS 1.3, encrypt data at rest, enforce role-based
access for customers, drivers, and admins, audit sensitive operations, prevent
duplicate order handling, use GDPR as a data-protection benchmark, obtain
explicit consent for location tracking, support account and data deletion, and
do not sell personal data to third parties.
**Acceptance criteria:**

- Given a client connection, when it connects to the service, then the
  connection uses HTTPS with TLS 1.3.
- Given a user without the required role, when they access a role-restricted
  operation, then access is denied.
- Given an order submission is retried, when both submissions are processed,
  then no duplicate order is created.
- Given a configured sensitive operation, when it succeeds, then an audit
  record is persisted.
- Given a user has not granted location consent, when location tracking is
  requested, then tracking does not start; after explicit consent, the
  recorded consent is associated with the user.
- Given a user requests account and data deletion, when the request completes,
  then the account is deleted and the user's personal data is deleted or
  anonymized according to the legally approved retention policy.
- Given any outbound third-party data-sharing integration, when its payload is
  inspected, then personal data is not shared for sale.

**Trace:** intent lines 72-86, 109-127.

**Decision required:** The intent does not define protected data, key
management, role-to-operation permissions, which operations are sensitive,
or the retention exceptions that may limit deletion. The intent records
owner-provided, unverified claims about Decree-Law No. 41 of 2022, PSP and
digital-bank capital requirements, iDplus, iBURAQ, Decree-Law No. 17 of 2024,
the draft Data Protection Law (2025), E-Commerce Law No. 21 of 2025, and
candidate provider licensing. Confirm the claims and their applicability with
the PMA and qualified local legal counsel before launch (intent lines
109-127, 141-142).

### R-12 Restaurant onboarding

**Requirement:** Include a simplified restaurant dashboard in the initial
release with sign-up, menu entry, photo upload, and order receipt. Manually
onboard the first 20 restaurants, personally assisting the first 10–20, then
open self-service access.
**Acceptance criteria:**

- Given a restaurant account, when an authorized restaurant user completes
  onboarding, then they can submit restaurant details, enter menu items, and
  upload photos.
- Given an order is placed with an onboarded restaurant, when the order is
  confirmed, then it is available in that restaurant's dashboard.
- Given a restaurant user, when they access another restaurant's menu or
  orders, then access is denied.
- Given fewer than 20 restaurants have completed assisted onboarding,
  self-service restaurant registration is unavailable; after the initial 20
  are onboarded, self-service registration is available.

**Trace:** intent lines 62-63, 100-102.

### R-13 Reliability and performance

**Requirement:** Support retries/offline fallback and achieve 99.5% uptime;
most API responses should be under two seconds.
**Acceptance criteria:** Not yet specifiable as automated pass/fail criteria.
The request population and percentile for “most,” the uptime measurement
window, and the offline behaviors to preserve are undefined.
**Trace:** intent lines 68-71, 77.

### R-14 Language, accessibility, and device support

**Requirement:** Support Arabic RTL and English, accessibility for users with
limited digital literacy or visual impairments, low-end devices, and
battery-conscious location tracking.
**Acceptance criterion:** Given Arabic and English locale selections, when a
user opens a primary workflow, then the interface presents the selected
language and Arabic content uses right-to-left direction.
**Trace:** intent lines 42, 78-80.

**Decision required:** Accessibility standard and testable success criteria,
supported device/network baseline, and battery-use target are unspecified.

## Deferred scope

Native iOS/Android apps, expansion beyond the initial market, advanced
analytics, advanced fraud detection, a full-featured restaurant portal, richer
localization, and non-food customer-facing services beyond food delivery are
deferred. Keep the data model extensible for food, pharmacy, grocery, and parcel
order types; grocery and pharmacy are candidates for Phase Two after proof of
concept (intent lines 96-99, 133). The first release target is 2–3 weeks
(intent line 130); no delivery milestone breakdown is provided.

## Approval status

**Approved.** The MVP scope and requirements in this specification are
approved. The unresolved decisions below are intentionally deferred to a later
stage and do not block this approval.

## Conflicts and open questions

Restaurant onboarding, food-only launch scope, and the initial simplified
operations console are confirmed decisions, not open questions (intent lines
96-104).

The following decisions remain unresolved and are deferred for later resolution:

- What launch audience size and scale target should performance tests use?
  (intent line 139)
- Which licensed card-payment gateway and e-wallet providers will be selected?
  (line 140)
- What PMA licensing, identity-verification, instant-payment, consumer-data,
  and electronic-transaction obligations apply? What privacy and retention
  requirements should follow legal review? (lines 113-127, 141-142)
- What assignment fairness measure, offer response window, and tie-breaking
  behavior should be used? (line 143)
- Define measurable API latency, uptime measurement window, offline recovery,
  accessibility, supported-device, and battery-use targets (line 144).
- Resolve authentication options, WhatsApp's MVP role, payment-method coverage,
  payout details, price components, and rating scales (intent lines 24, 27,
  35, 39, 91, 93).
