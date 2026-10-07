# Intelligent Delivery & Order Distribution Platform

## Problem and opportunity
We are building a delivery platform for Gaza and Ramallah that solves a major problem: unfair order distribution for drivers. Drivers often receive unequal access to jobs, which leads to frustration, lower trust, and lost earning opportunities. At the same time, customers want a faster, easier, and more transparent ordering experience.

The product is not just a typical real-time tracking app. Its core differentiator is an intelligent, fair dispatch system that helps allocate orders more evenly and efficiently to available drivers while still delivering a better service to customers.

## Target audience
The primary audience is both drivers and customers in Gaza and Ramallah. This includes:
- Customers who want to order food quickly and conveniently.
- Drivers who want to receive fair, timely, and clear order opportunities.
- Admin or operations staff who need to monitor orders and support the system.
- Business owners or partners who may want to expand the service to additional cities and services later.

## Core value proposition
- Fairer order assignment for drivers.
- Faster and more transparent delivery for customers.
- Better experience through order tracking, live status updates, and clear payments.
- A platform that can expand to new cities without major architectural redesign.

## User workflows

### Customer workflow
1. Sign up/login using Google, Apple, or continue as a guest.
2. Select a delivery location.
3. Browse nearby restaurants and add food items to the cart.
4. Choose payment method: cash, card, or e-wallet.
5. Review the final price before confirming the order.
6. Track the order and driver on a map.
7. Receive the order and rate the restaurant and driver.

### Driver workflow
1. Open the app and set status to Available.
2. Receive a new order with distance and fare.
3. Accept or reject the order within a short window.
4. Receive navigation to the restaurant.
5. Confirm pickup after collecting the order.
6. Deliver to the customer using a live map.
7. Confirm delivery and receive the amount in the driver wallet.

## Platform and technical direction
The initial release is a web app, primarily mobile-friendly and responsive for the regional market. The product should work well on low-end devices and older networks, including 2G/3G/4G/5G, and must support Arabic RTL.

The recommended MVP technical direction is:
- Laravel backend with Sanctum authentication
- PostgreSQL + PostGIS for geospatial logic and driver proximity
- Google Maps for navigation and location services
- Pusher for real-time notifications and order updates
- Licensed third-party card gateway and licensed e-wallet provider(s), pending selection
- Docker/Kubernetes-ready deployment for future scalability
- Redis and queues for async processing and retries

## Functional priorities for MVP
- User authentication using Laravel Sanctum
- Food ordering flow; keep the data model extensible for future order types
- Map integration using Google Maps
- Cash on delivery and integrations with selected licensed payment providers
- Push notifications using Pusher
- Driver availability and assignment flow
- Customer order tracking and status updates
- Driver wallet and payout completion flow
- Simplified restaurant onboarding dashboard: sign up, enter menu, upload
  photos, and receive orders
- Simplified operations console for operational oversight

## Non-functional requirements
The product should prioritize:
- Fast API response times under 2 seconds for most requests
- Driver assignment in under 5 seconds
- Driver location updates every 5–10 seconds
- 99.5% uptime
- Secure communication using HTTPS/TLS 1.3
- Encrypted data at rest
- Role-based access control for customer, driver, admin
- Audit logs for sensitive operations
- Idempotent order handling and duplicate prevention
- Offline fallback and retry support when the internet drops
- Arabic RTL interface and English support
- Accessibility support for limited digital literacy and visually impaired users
- Low-end device compatibility and battery-conscious location tracking
- GDPR as a data-protection benchmark, with an Arabic privacy policy explaining
  collected data, purposes, and deletion; explicit consent for location
  tracking; account and data deletion; and no sale of personal data to third
  parties

## Key integrations
- Payments: cash on delivery (default), a licensed card-payment gateway
  (provider to be selected, e.g. PayTabs or MEPS), and licensed e-wallet
  options (e.g. Jawwal Pay or Reflect)
- Mapping/navigation: Google Maps
- Messaging and alerts: WhatsApp and Pusher
- Authentication: Laravel Sanctum
- Real-time features: Pusher notifications and updates

## Confirmed MVP decisions
- Food delivery is the only customer-facing service in the initial release.
  Keep the data model extensible for future food, pharmacy, grocery, and parcel
  order types. Grocery and pharmacy are candidates for Phase Two after proof of
  concept; other service categories remain deferred.
- Include a simplified restaurant dashboard in the initial release with
  sign-up, menu entry, photo upload, and order receipt. Manually onboard the
  first 20 restaurants, personally assisting the first 10–20; then open
  self-service access.
- Use a simplified operations console initially, with a full administrative
  dashboard deferred until scale requires it.
- Prefer cash on delivery as the default payment option. Use a licensed
  third-party provider for card processing and licensed e-wallet providers;
  do not operate an unlicensed payment service. Final provider selection is
  pending.
- Treat the Palestinian payment and data-protection legal statements and
  provider licensing status in the product brief as assumptions requiring
  verification with the PMA and qualified local legal counsel before launch.
- User-provided legal context for counsel to verify, not verified legal advice:
  Decree-Law No. 41 of 2022 is said to regulate national payments and require
  PSP licensing; a digital bank is said to require $25 million capital while
  standard PSP requirements are lower. PMA iDplus is said to have launched in
  2025 for identity/account linking and transaction authorization, with
  iBURAQ as the instant payment system. The data-protection framework is
  described as fragmented, with no unified law; Decree-Law No. 17 of 2024
  covers electronic transactions and trust services; a GDPR-inspired Data
  Protection Law is said to be in draft for 2025; and E-Commerce Law No. 21
  of 2025 is said to require consumer-data confidentiality and transparency.
  Verify applicability and status of each claim with local counsel and the PMA.
- Cash is said to account for 75% of transactions; treat this as an
  unverified rationale for making cash on delivery the default.
- Verify the asserted licensing status of PayTabs, MEPS, Jawwal Pay, and
  Reflect with the PMA before selecting providers.

## Constraints and scope
- Target timeline: 2–3 weeks for the first release
- Budget: flexible / standard MVP budget
- MVP focus: food ordering, simplified restaurant onboarding, order assignment, map integration, payment, notifications, and a simplified operations console
- Deferred to later phases: native iOS/Android apps, wider market expansion, advanced analytics, advanced fraud detection, a full-featured restaurant portal, richer localization, and non-food customer-facing services beyond the initial food offering

## Business impact
This product aims to create a more equitable, faster, and more reliable delivery experience in Gaza and Ramallah. It reduces operational friction for drivers, improves customer trust, and provides a foundation for scaling to more cities and services in the future.

## Open questions
- What is the expected launch audience size and scale target for the first release?
- Which licensed card-payment gateway and e-wallet providers will be selected?
- What specific PMA licensing, identity-verification, instant-payment, consumer-data, and electronic-transaction obligations apply to this platform?
- What consent, retention, deletion, and privacy-policy requirements should be implemented after local legal review?
- What assignment fairness measure, offer response window, and tie-breaking behavior should be used?
- What are the measurable API latency, uptime measurement window, offline recovery, accessibility, supported-device, and battery-use targets?

Status: approved
