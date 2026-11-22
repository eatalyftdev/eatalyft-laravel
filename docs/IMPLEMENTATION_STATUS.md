# EataLyft Rebuild — Implementation Status

Updated: 2026-11-22

## Implemented in this baseline

- Laravel 12 module namespace foundation under `app/Modules/Bus`.
- Forward migration restoring the unique nullable `users.firebase_uid` mapping.
- MySQL bus operations schema: routes, ordered route stops, route fares, schedules, trips, normalized trip seats, bookings, tickets and append-only ticket events.
- Bus trip discovery API and authenticated customer booking-hold API.
- Transactional seat-hold service: trip row lock, seat row locks, duplicate-seat rejection, expiry timestamp, idempotency key and no payment collection.
- Scheduled `bus:expire-seat-holds` command to release expired inventory.
- New Firebase CLI scaffold, deny-all Firestore rules, restricted user-avatar Storage rules, environment placeholders and mobile integration guide.
- Documentation separating legacy Firestore data from the new Laravel/MySQL source of truth.

## Not yet implemented / not verified

- Firebase projects have not been provisioned, linked, or deployed. No Firebase rules have been deployed.
- Firebase Admin SDK, Firebase ID-token verification, token exchange, App Check enforcement and FCM delivery are not connected.
- No live Firebase auth login is enabled. `firebase_uid` is only the schema mapping at this stage.
- Bus agency/branch membership policies and cross-agency authorization need to be applied to every agency-facing API/controller.
- Bus route, schedule, fleet and trip management APIs/UI are not yet complete.
- Seat-layout-to-trip inventory generation is not yet implemented.
- Ticket issuance, signed/opaque QR generation, scan validation, replay protection, offline scanner sync, manifests and reconciliation are not yet implemented.
- Payment integration is intentionally deferred. No wallet debit, provider call, webhook confirmation or ticket issuance based on a client payment claim is implemented.
- Legacy Firestore data export/import and historical reconciliation have not been performed.
- Database migrations and automated integration tests could not be executed in this runtime because the PHP installation has no PDO database drivers. PHP syntax checks and Laravel route/scheduler registration checks passed.

## Immediate next engineering slice

1. Install/enable the required PHP extensions and provision an isolated MySQL staging database.
2. Run all migrations on an empty staging database and fix any driver-specific migration issues.
3. Implement agency/branch membership and policy scopes.
4. Implement fleet/seat-layout assignment and trip inventory generation.
5. Implement agency route/schedule/trip management endpoints.
6. Add booking confirmation and ticket issuance for authorized counter sales only, without connecting online payment providers.
7. Implement secure QR scanning, ticket event history and trip manifests.
8. Add integration tests for concurrent seat holds, idempotency, expired holds and cross-agency access.
