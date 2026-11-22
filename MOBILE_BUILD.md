# EataLyft Mobile Build & Firebase Integration

**Target:** React Native / Expo. **Status:** integration guide baseline; credentials and native build profiles remain environment-specific.

## Platform boundaries

- Laravel REST API (`/api/v1`) is the business API and source of truth.
- Expo clients use the Laravel API for trips, seats, bookings, tickets, agencies and account permissions.
- Firebase Authentication is the planned identity provider; until the Laravel token-verification adapter is implemented and tested, use the existing staging auth flow and do not ship Firebase login as active.
- Firebase Cloud Messaging is for push delivery only. Store device-token registration through an authenticated Laravel endpoint; do not expose privileged Firebase credentials in the app.
- Firebase Storage is limited to approved user-owned upload paths. Private agency and bus documents upload through Laravel.

## Environment setup

Create separate Expo/EAS environments for development, staging and production. Inject only public Firebase client configuration into the corresponding build profile. Never bundle Firebase Admin service-account credentials, Laravel app keys, payment secrets, Boxo secrets or private API tokens in an Expo binary.

Recommended public variables:

- `EXPO_PUBLIC_API_BASE_URL`
- `EXPO_PUBLIC_FIREBASE_API_KEY`
- `EXPO_PUBLIC_FIREBASE_AUTH_DOMAIN`
- `EXPO_PUBLIC_FIREBASE_PROJECT_ID`
- `EXPO_PUBLIC_FIREBASE_STORAGE_BUCKET`
- `EXPO_PUBLIC_FIREBASE_MESSAGING_SENDER_ID`
- `EXPO_PUBLIC_FIREBASE_APP_ID`

These client values identify a Firebase project; they are not substitutes for Firebase security rules, App Check, or server-side authorization.

## Build sequence

1. Configure Expo app identifiers and EAS profiles for dev/staging/production.
2. Connect the app to the matching Firebase project; use Firebase Emulator Suite for local development.
3. Implement the Laravel auth-token exchange only after the server verifier is in place.
4. Implement API client with typed request/response contracts and refresh/session handling.
5. Implement bus discovery, trip details, seat map, booking hold, booking status and ticket wallet against `/api/v1/bus/*`.
6. Add FCM registration and notification deep links through Laravel-managed device tokens.
7. Test offline/poor-network behaviour, duplicate taps, expired holds, retry/idempotency and QR scanning.
8. Build staging APK/AAB/IPA and run acceptance tests before production profiles are enabled.

## Bus booking client contract (initial)

- `GET /api/v1/bus/trips` — public trip search; filters include `from_city_id`, `to_city_id`, `date`, `per_page`.
- `GET /api/v1/bus/trips/{trip}` — trip and seat availability.
- `POST /api/v1/bus/bookings/holds` — authenticated, temporary seat hold; send `trip_id`, `seat_labels[]`, contact details and an `idempotency_key`.
- `GET /api/v1/bus/bookings/mine` — authenticated customer's bookings.

A successful hold is not a confirmed ticket and does not represent payment. Payment processing is deliberately deferred. The app must display the hold expiry and must not show a QR ticket until a future server-side ticket issuance endpoint confirms the booking.
