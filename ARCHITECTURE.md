# EataLyft Platform Architecture

**Status:** new Laravel baseline under active implementation. This document distinguishes implemented code from planned capabilities.

## Product scope

The rebuilt EataLyft platform launches with the internally developed bus booking and agency operations system as its first fully implemented vertical. Rides, food delivery, parcel delivery and vendor operations are excluded from the rebuilt product scope. Existing legacy data is preserved for a separately approved archive/migration process. Hotels, service marketplace and Boxo-powered mini-app categories remain future modules in the platform roadmap; their presence in the existing Laravel admin scaffold does not mean they are operational.

## Technology

- Laravel 12 / PHP 8.2+.
- MySQL 8+ as the authoritative transactional database.
- Blade + Livewire for the web application (incremental UI build).
- REST API under `/api/v1` for web and Expo clients.
- React Native / Expo for mobile clients.
- Firebase as a supporting identity, push, storage and optional real-time projection platform.
- Laravel queues and scheduler for asynchronous work.

## System-of-record boundaries

| Domain | Authority |
|---|---|
| User profile, roles and permissions | Laravel + MySQL |
| Firebase identity link | `users.firebase_uid` mapped by verified server-side Firebase ID token |
| Agencies, branches and staff membership | Laravel + MySQL |
| Bus routes, schedules, fleet, trips and seat inventory | Laravel + MySQL |
| Bus bookings, tickets, scans and ticket event history | Laravel + MySQL |
| Wallet, ledger and payment state | Deferred; must be designed ledger-first before activation |
| Push delivery | Firebase Cloud Messaging, requested/authorized by Laravel |
| User profile image storage | Firebase Storage or approved private storage policy |
| Boxo supplier operations | Laravel integration adapter; Boxo is not the authority for EataLyft user or payment records |

Clients must not write authoritative bus or financial records directly to Firestore. The new Firestore rules are deny-all until a specific read-only projection is designed and reviewed.

## Environments

Development, staging and production must use independent MySQL databases, Firebase projects, credentials, storage buckets, mail providers and API endpoints. Staging is the first deploy target. Production remains untouched until staging tests pass and the owner explicitly approves the release.

## Module boundaries

Business modules live under `app/Modules/<Domain>`. Shared identity, authorization, settings, notifications, audit and file services live under `app/Core` when those modules are extracted from the current app scaffold. External adapters live under `app/Infrastructure`. Modules communicate through service contracts/events rather than reaching into one another's controllers.

## Bus booking invariants

1. A trip's sellable seats are normalized rows, not an embedded client-controlled array.
2. Seat holds and bookings are created inside a database transaction with row locks.
3. A seat may have at most one active hold or issued ticket for a trip.
4. Holds expire through an idempotent scheduled command.
5. Ticket issuance and scanning are server-authoritative and append immutable ticket events.
6. A QR code must contain an opaque, unguessable token; store only its hash server-side.
7. Payment is not active in this baseline. A held booking is not paid, confirmed, or ticketed.
8. Agency and branch authorization is enforced server-side for every operational endpoint.

## Firebase

See `docs/FIREBASE_ARCHITECTURE.md`. The new Firebase CLI scaffold is present but has not been connected to actual projects or deployed. Firebase Auth token verification is not yet enabled. Never accept an unverified JWT payload or trust client-supplied role claims.

## Mobile

See `MOBILE_BUILD.md`. Expo uses the Laravel REST API for all business operations. Firebase client configuration may be included in builds; Admin SDK credentials and private integration secrets must never be bundled into a mobile app.

## Implementation status

See `docs/IMPLEMENTATION_STATUS.md` for verified code, gaps and the next implementation slice. Historical claims from the old Next.js architecture are not automatically treated as live in this Laravel rebuild.
