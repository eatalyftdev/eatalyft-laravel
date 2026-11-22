# EataLyft Firebase Architecture — Fresh Project Baseline

**Status: scaffolded, not connected or deployed.** No Firebase project IDs, service-account keys, or production credentials are included in this repository.

## Environment isolation

Create three independent Firebase projects:

- Development: `eatalyft-dev` (project ID chosen in Firebase Console)
- Staging: `eatalyft-staging`
- Production: `eatalyft-production`

Use `.firebaserc.example` as a template and create a local `.firebaserc` with the actual project IDs. Do not commit `.firebaserc` if it contains internal project identifiers, and never commit service-account JSON files.

## Responsibility boundaries

Laravel 12 + MySQL is the sole authority for EataLyft application identity mapping, roles, agencies, bus routes, schedules, trips, seat inventory, bookings, tickets, ticket scans, and financial records. Firebase is a supporting platform for Firebase Authentication, Cloud Messaging, controlled profile-image storage, App Check, and optional future real-time read projections.

No client-side Firestore writes are permitted. `firebase/firestore.rules` is intentionally deny-all until a concrete read-model use case is approved. `firebase/firestore.indexes.json` is intentionally empty because the supplied legacy indexes target retired collections (`rides`, `parcels`, `food_orders`, and the old Firestore `trips` / `busTrips` / `busBookings` design). Bus operational queries are now MySQL queries and must be indexed in MySQL migrations.

## Authentication mapping

The `users.firebase_uid` nullable unique column is restored by a forward migration. A Firebase UID is an external identity key, not an authorization source. Laravel's user row, Spatie roles, permissions, agency membership and branch membership remain authoritative. Never trust a role claim from a client-supplied token without server-side verification and synchronization.

The Laravel Firebase token-verification adapter is not yet enabled. Before enabling Firebase login, select and install a maintained Firebase Admin SDK, validate ID-token signature/issuer/audience/expiry, check revocation according to the chosen policy, map `sub`/UID to `users.firebase_uid`, and issue the application's own Sanctum session/token. Do not accept decoded-but-unverified JWT payloads.

## Storage

Profile image uploads are limited to the user's own `users/{uid}/avatars/` path, image/jpeg|png|webp, under 5 MiB. Agency legal documents, bus documents, ticket QR artifacts, and other private assets must pass through Laravel authorization and private storage. Do not make agency documents publicly readable.

## Deployment order

1. Create the development project and enable only required products.
2. Run Firebase Emulator Suite locally using `firebase.json`.
3. Validate rules with emulator tests before deployment.
4. Create staging project and deploy rules there only after review.
5. Production project remains untouched until staging acceptance and explicit approval.

## Legacy migration policy

The supplied legacy Firestore rules and indexes are retained as migration evidence, not copied into the new project. Legacy collections for rides, food, parcels, delivery, wallet balances, payments, and old bus bookings are not recreated. Historical production data must be exported and archived separately before any migration or decommissioning decision. Nothing in this scaffold deletes or modifies the old Firebase project.
