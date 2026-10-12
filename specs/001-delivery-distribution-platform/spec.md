# Feature Specification: Intelligent Delivery & Order Distribution Platform (MVP)

**Feature Branch**: `001-delivery-distribution-platform`

**Created**: 2026-10-09

**Status**: Draft

**Input**: User description: "Read intent.md and use it as the primary source of requirements for generating the specification. Identify missing or ambiguous requirements and ask me questions before finalizing the spec. Do not invent requirements. Primary source: intent/delivery-distribution-platform.md"

## Clarifications

### Session 2026-10-10

- Q: How should the system decide which available driver gets offered an order so that the distribution is fair? → A: Offer the order to the available driver closest to the order location; break distance ties by the driver with fewer completed deliveries; if the closest driver does not accept within 30 seconds or rejects, offer it to the next closest eligible driver.
- Q: Should the first release accept only cash on delivery, or must it also accept card and e-wallet payments at launch? → A: Cash on delivery only at launch; card and e-wallet are phased in later once a licensed provider is selected and verified.
- Q: What should the platform commit to now for location consent, data retention, and account deletion while the legal review is pending? → A: Explicit opt-in before any location tracking; purge personal data within 30 days of a validated deletion request; retain order/financial records only for the legally required period once confirmed.
- Q: What order statuses must the system track, and when may a customer cancel an order? → A: Statuses placed → driver assigned → picked up → delivered, plus cancelled; customers may cancel only before a driver is assigned, and after that only operations staff may cancel.
- Q: What is the expected launch audience size and scale target for the first release? → A: Pilot scale — first 20 restaurants serving Gaza and Ramallah, up to a few hundred customers and drivers, and a few hundred orders per day.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Customer places and tracks a food order (Priority: P1)

A customer in Gaza or Ramallah opens the app, signs up or logs in (or continues as a guest), selects a delivery location, browses nearby restaurants, adds food items to a cart, chooses how to pay, reviews the final price, confirms the order, tracks the order and driver on a map until delivery, then rates the restaurant and driver.

**Why this priority**: Ordering is the customer-facing core of the business. Without a working order-and-track flow there is no product, no revenue, and no driver demand, so this is the first viable slice.

**Independent Test**: A single customer can go from app entry through location selection, restaurant browse, cart, payment choice, price review, order confirmation, live tracking, and post-delivery rating, and receives a delivered order end-to-end.

**Acceptance Scenarios**:

1. **Given** a new or returning customer, **When** they open the app, **Then** they can sign up or log in with Google, log in with Apple, or continue as a guest.
2. **Given** a customer, **When** they select a delivery location, **Then** the system shows restaurants available for that location.
3. **Given** a customer browsing restaurants, **When** they add food items, **Then** the items are added to a cart.
4. **Given** a customer with cart items, **When** they choose a payment method, **Then** cash on delivery is available at launch, with card and e-wallet added once providers are verified (see FR-026).
5. **Given** a customer with a filled cart, **When** they proceed to confirm, **Then** they can review the final price before the order is submitted.
6. **Given** a customer who confirmed an order, **When** the order progresses, **Then** they can see the order status and the assigned driver on a map with live updates.
7. **Given** a customer who received their order, **When** delivery is confirmed, **Then** they can rate the restaurant and the driver.

---

### User Story 2 - Driver receives fair order offers and completes deliveries (Priority: P1)

An approved driver opens the app, sets their status to Available, receives a new order offer showing distance and fare, accepts or rejects it within 30 seconds, is navigated to the restaurant, confirms pickup, delivers to the customer using a live map, confirms delivery, and gets the amount added to their wallet.

**Why this priority**: Fair, timely order allocation to drivers is the product's stated core differentiator. A customer order has no value unless a driver reliably receives and completes it, so this is P1 alongside ordering.

**Independent Test**: A single available driver receives an order offer, accepts it, is navigated to the restaurant and the customer, confirms pickup and delivery, and sees their wallet credited.

**Acceptance Scenarios**:

1. **Given** an approved driver, **When** they open the app, **Then** they can set their status to Available.
2. **Given** an available driver, **When** an order is dispatched, **Then** they receive a new order offer that includes the distance and the fare.
3. **Given** a driver presented with an order offer, **When** the offer is shown, **Then** the driver can accept or reject it within 30 seconds.
4. **Given** a driver who accepted an order, **When** they begin the pickup, **Then** they receive navigation to the restaurant.
5. **Given** a driver who collected the order, **When** they confirm pickup, **Then** the order status is updated.
6. **Given** a driver with a picked-up order, **When** they deliver, **Then** they follow a live map to the customer.
7. **Given** a driver who reached the customer, **When** they confirm delivery, **Then** the amount is credited to the driver's wallet.
8. **Given** several available drivers, **When** an order is dispatched, **Then** it is offered to the available driver closest to the order location, with distance ties broken by the driver who has completed fewer deliveries.

---

### User Story 3 - Restaurant onboards and receives orders (Priority: P2)

A restaurant operator signs up, enters their menu, uploads photos, and receives orders through a simplified dashboard. The first restaurants are onboarded manually and personally assisted before self-service access opens.

**Why this priority**: Restaurants supply the food being ordered. This is required for a real marketplace, but a pilot can be seeded by manually onboarding the first restaurants, so it follows the P1 customer/driver flows.

**Independent Test**: A restaurant operator can complete sign-up, publish a menu with photos, and see an incoming order appear in their dashboard.

**Acceptance Scenarios**:

1. **Given** a prospective restaurant, **When** they sign up, **Then** they can enter their menu.
2. **Given** a restaurant entering a menu, **When** they add items, **Then** they can upload item or restaurant photos.
3. **Given** an onboarded restaurant, **When** a customer places an order, **Then** the restaurant receives the order in their dashboard.
4. **Given** the first 20 restaurants, **When** they are onboarded, **Then** they are onboarded manually with personal assistance for the first 10–20, after which self-service access is opened.

---

### User Story 4 - Driver wallet and payout completion (Priority: P2)

A driver's confirmed deliveries accumulate in a wallet, and the driver can complete the payout flow to receive their earnings.

**Why this priority**: Earnings trust is central to keeping drivers on the platform, and payouts operationalize the "receive the amount in the driver wallet" promise. It depends on completed deliveries from User Story 2.

**Independent Test**: After one or more confirmed deliveries, a driver's wallet reflects the credited amounts and a payout can be completed.

**Acceptance Scenarios**:

1. **Given** a driver with a confirmed delivery, **When** the delivery is confirmed, **Then** the order amount is credited to their wallet.
2. **Given** a driver with a positive wallet balance, **When** they complete the payout flow, **Then** the payout is recorded as completed.

---

### User Story 5 - Operations staff monitor and support the system (Priority: P3)

An admin or operations staff member uses a simplified operations console to monitor orders and support the running system.

**Why this priority**: Operational oversight is needed to keep the pilot healthy, but a full administrative dashboard is explicitly deferred until scale requires it, so this starts as a simplified console at the lowest priority.

**Independent Test**: An operations user can sign in to the console and see live orders and their statuses to intervene when needed.

**Acceptance Scenarios**:

1. **Given** an operations user, **When** they open the operations console, **Then** they can monitor current orders and their statuses.
2. **Given** an operations user monitoring orders, **When** an order needs support, **Then** they can act on it from the console.

---

### Edge Cases

- What happens when a customer on the order screen loses connectivity during checkout or tracking? The system MUST support offline fallback and retry when the internet drops.
- What happens when the same order is submitted twice (double tap, retry after timeout)? The system MUST handle orders idempotently and prevent duplicates.
- What happens when a driver does not respond to an order offer within 30 seconds, or rejects it? The system offers the order to the next closest eligible driver (see FR-016, FR-017).
- What happens when no driver is available for a placed order?
- What happens when a customer wants to cancel after a driver has already been assigned? Only operations staff may cancel at that point (FR-041), so the customer is directed to support.
- What happens when a chosen card or e-wallet payment fails or the provider is unavailable?
- What happens when the customer denies location tracking consent? No location tracking may occur without explicit consent (FR-032, FR-035); the ordering behavior available without consent must be defined.
- What happens when there are no restaurants available for the selected delivery location?
- What happens when a customer or driver requests account/data deletion while an order is in progress?
- What happens on a low-end device over a slow 2G/3G network?
- What happens if the Arabic RTL layout misrenders on an older browser?

## Requirements *(mandatory)*

### Functional Requirements

**Accounts and Access**

- **FR-001**: Customers MUST be able to sign up or log in using Google, log in using Apple, or continue as a guest.
- **FR-002**: The system MUST enforce role-based access control for the customer, driver, and admin roles.
- **FR-003**: Drivers MUST have a dedicated path to be registered/approved and to manage their availability.
- **FR-004**: Restaurant operators MUST have a dedicated onboarding and access path separate from customers.
- **FR-005**: Users MUST be able to delete their account and personal data.

**Ordering (Customer)**

- **FR-006**: Customers MUST be able to select a delivery location.
- **FR-007**: Customers MUST be able to browse restaurants available for their selected location and add food items to a cart.
- **FR-008**: Customers MUST be able to review the final price before confirming the order.
- **FR-009**: Customers MUST be able to choose a payment method, with cash on delivery available at launch and card and e-wallet options added once those providers are in place (see FR-026).
- **FR-010**: The system MUST handle order placement idempotently and prevent duplicate orders.
- **FR-011**: The system MUST keep the order data model extensible for future order types (food, pharmacy, grocery, parcel), while food is the only customer-facing service in the initial release.
- **FR-012**: Customers MUST be able to track their order and the assigned driver on a map through the order statuses placed, driver assigned, picked up, and delivered, with live status updates.
- **FR-013**: Customers MUST be able to rate the restaurant and the driver after receiving the order.
- **FR-041**: The system MUST track the order statuses placed, driver assigned, picked up, delivered, and cancelled; customers MAY cancel only before a driver is assigned, and after assignment only operations staff MAY cancel an order.

**Driver and Dispatch**

- **FR-014**: Drivers MUST be able to set their status to Available.
- **FR-015**: The system MUST offer a new order to an available driver together with the order's distance and fare.
- **FR-016**: Drivers MUST be able to accept or reject an order offer within 30 seconds; if the driver rejects the offer or does not respond within 30 seconds, the system MUST offer the order to the next closest eligible driver.
- **FR-017**: The system MUST assign each order to the available driver closest to the order location; when two or more available drivers are equidistant, the system MUST select the driver with fewer completed deliveries.
- **FR-018**: Drivers MUST receive navigation to the restaurant after accepting an order.
- **FR-019**: Drivers MUST confirm pickup after collecting the order.
- **FR-020**: Drivers MUST be able to deliver to the customer using a live map and confirm delivery.
- **FR-021**: The system MUST credit the driver's wallet with the order amount upon confirmed delivery.
- **FR-022**: Drivers MUST have a wallet and a payout completion flow.

**Restaurant Onboarding**

- **FR-023**: Restaurant operators MUST be able to sign up, enter a menu, upload photos, and receive orders through a simplified dashboard.
- **FR-024**: The system MUST support manual onboarding and personal assistance for the first 20 restaurants, after which self-service access is opened.

**Payments**

- **FR-025**: The system MUST support cash on delivery as the only payment option in the initial release.
- **FR-026**: The system MUST add card and e-wallet payments only after a licensed card-payment gateway and licensed e-wallet provider(s) are selected and verified, and MUST NOT operate an unlicensed payment service.

**Notifications and Real-Time Updates**

- **FR-027**: The system MUST send push notifications and real-time order/status updates to customers, drivers, and restaurants.
- **FR-028**: The system MUST send alerts/notifications through both in-app real-time channels and messaging (e.g., WhatsApp) as described in the intent.

**Operations**

- **FR-029**: Admin/operations staff MUST have a simplified operations console to monitor orders and support the system; a full administrative dashboard MAY be deferred until scale requires it.

**Security, Privacy, and Compliance**

- **FR-030**: The system MUST record audit logs for sensitive operations.
- **FR-031**: The system MUST NOT sell personal data to third parties.
- **FR-032**: The system MUST provide an Arabic privacy policy explaining what data is collected, for what purposes, and how deletion works, and MUST obtain explicit consent for location tracking.
- **FR-033**: The system MUST support account and data deletion.
- **FR-034**: The system MUST encrypt data at rest and secure communication in transit.
- **FR-035**: The system MUST obtain explicit consent before any location tracking; on a validated deletion request, the system MUST purge personal data within 30 days; and the system MUST retain order and financial records only for the legally required period once confirmed by local legal review.
- **FR-036**: The system MUST treat the Palestinian payment and data-protection legal statements and provider licensing status in the intent as assumptions requiring verification with the PMA and qualified local legal counsel before launch.

**Localization, Accessibility, and Resilience**

- **FR-037**: The interface MUST support Arabic with right-to-left layout and must also support English.
- **FR-038**: The system MUST support users with limited digital literacy and visually impaired users.
- **FR-039**: The system MUST work on low-end devices and older networks (2G/3G/4G/5G) with battery-conscious location tracking.
- **FR-040**: The system MUST provide offline fallback and retry support when the internet drops.

### Non-Functional Requirements

- **NFR-001**: Most requests MUST complete in under 2 seconds.
- **NFR-002**: Driver assignment MUST complete in under 5 seconds.
- **NFR-003**: Driver location updates MUST occur every 5–10 seconds.
- **NFR-004**: The system MUST target 99.5% uptime.

### Key Entities *(include if feature involves data)*

- **User / Account**: A platform account with a role (customer, driver, admin) and an associated authentication identity; supports Google, Apple, and guest access; supports deletion.
- **Guest Session**: A limited identity allowing ordering without a full account.
- **Customer**: A user who places food orders.
- **Driver**: A user who receives order offers, is tracked live, has an availability status, and owns a wallet.
- **Restaurant**: A food provider with an operator account, menu, photos, and an order dashboard.
- **Menu / Menu Item**: The list of orderable food items and their content for a restaurant, including photos.
- **Cart / Order / Order Item**: The selected items, the final priced order, and its line items; the model is extensible to future order types.
- **Delivery Location**: The customer's selected delivery address/point.
- **Order Status**: The lifecycle state of an order: placed → driver assigned → picked up → delivered, plus cancelled. Customers may cancel only before a driver is assigned; after assignment only operations staff may cancel.
- **Order Offer / Assignment**: An offer of an order to a driver and the resulting assignment; the closest available driver is selected, with distance ties broken by fewer completed deliveries.
- **Payment / Payment Method**: The selected method and its record; cash on delivery is the only option in the initial release, with card and e-wallet added later.
- **Driver Wallet / Wallet Transaction / Payout**: The driver's balance, credited amounts, and completed payouts.
- **Rating**: Customer feedback on a restaurant and driver after delivery.
- **Notification / Alert**: Push, real-time, and messaging alerts sent to customers, drivers, and restaurants.
- **Audit Log**: A record of sensitive operations.
- **Consent Record**: The record of a user's explicit consent for location tracking and data handling.
- **City / Service Area**: The supported geographic area (Gaza, Ramallah) with room to expand to new cities.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A customer can go from opening the app to confirming a food order in under 4 minutes on a typical low-end device over a 3G connection.
- **SC-002**: 95% of user-facing requests return within 2 seconds.
- **SC-003**: An available driver is offered a newly placed order within 5 seconds of the order being confirmed.
- **SC-004**: Drivers who accept an offer can complete delivery end-to-end, with the order amount reflected in their wallet immediately after confirmation of delivery.
- **SC-005**: Customers see order and driver location updates every 5–10 seconds while an order is active.
- **SC-006**: The platform maintains 99.5% uptime over a rolling 30-day window.
- **SC-007**: At least 90% of placed orders are delivered without manual operations intervention.
- **SC-008**: In a post-pilot survey, at least 80% of active drivers agree that order distribution is fair.
- **SC-009**: At least 95% of ordering, tracking, and offer screens complete successfully in Arabic RTL, matching the English experience.
- **SC-010**: When connectivity drops mid-action, at least 95% of in-progress actions complete automatically within 1 minute of reconnection without the user re-entering data.
- **SC-011**: A validated account-and-data deletion request is completed within 30 days.
- **SC-012**: The platform supports pilot scale — a few hundred concurrent customers and drivers and a few hundred orders per day — while meeting the response-time and uptime targets above.

## Assumptions

- **Scope**: This specification covers the full MVP platform described in the intent — all listed MVP functional priorities (authentication, food ordering, map tracking, payments, notifications, driver availability and assignment, driver wallet and payout, restaurant onboarding dashboard, and operations console). It is not limited to one slice.
- **Launch locations**: The initial release targets Gaza and Ramallah only; expansion to additional cities is future work.
- **Customer-facing service**: Food delivery is the only customer-facing service in the initial release; pharmacy, grocery, and parcel are deferred (grocery/pharmacy are Phase Two candidates).
- **Payment default**: Cash on delivery is the only payment option in the initial release; card and e-wallet are phased in later once a licensed provider is selected and verified (FR-026). The claim that cash is ~75% of transactions is treated as unverified.
- **Platform**: The initial release is a responsive, mobile-friendly web app; native iOS/Android apps are deferred.
- **Scale**: The first release targets pilot scale — the first 20 restaurants serving Gaza and Ramallah, up to a few hundred customers and drivers, and a few hundred orders per day (SC-012).
- **Legal and privacy specifics**: The exact consent, retention, and deletion obligations still require local legal review. The committed baseline is explicit opt-in before location tracking, a 30-day purge after a validated deletion request (SC-011), and retention of order/financial records only for the legally required period once confirmed (FR-035).
- **Uptime measurement**: Unless confirmed otherwise, 99.5% uptime is measured over a rolling 30-day window (SC-006).
- **Technology direction is not a requirement**: The intent's recommended stack (Laravel/Sanctum, PostgreSQL + PostGIS, Google Maps, Pusher, Redis/queues, Docker/Kubernetes-ready deployment) is treated as implementation context for planning, not as requirements in this specification. Google/Apple sign-in and cash/card/e-wallet are included only because they are user-facing.
- **Legal claims**: Statements about Decree-Law No. 41 of 2022, PMA iDplus, iBURAQ, Decree-Law No. 17 of 2024, the draft Data Protection Law, E-Commerce Law No. 21 of 2025, and provider licensing status are unverified user-provided context and are not treated as confirmed requirements.
- **Out of scope for the initial release**: Native apps, wider market expansion, advanced analytics, advanced fraud detection, a full-featured restaurant portal, richer localization, and non-food customer-facing services.
- **Timeline and budget**: The intent targets a 2–3 week first release on a flexible/standard MVP budget; these are planning constraints, not product requirements.
