# Legacy Firebase Rules, Indexes & Bus Script — Migration Decision

The legacy source supplied for this rebuild contains Firestore composite indexes, broad Firestore security rules, and a client-side `bookSeats()` transaction over `busTrips`, `busBookings`, and `wallets`.

## Decisions for the new build

1. The old Firestore project is a legacy production environment. Do not deploy new rules or indexes to it as part of this work.
2. The new Firebase project is not a replacement Firestore database for the bus domain. Laravel + MySQL owns bus trips, seats, bookings, tickets, scans and audit history.
3. The legacy `busTrips` and `busBookings` collections are migration sources only. Their records require a separately planned, read-only export/import with reconciliation before any retirement.
4. The supplied old wallet debit and MoMo confirmation logic is deliberately not ported as executable payment code. Payments are deferred. A held seat is not a paid booking and must not issue a boarding ticket.
5. The old Firestore rules are not copied. They allow public `get` on `bus/{bookingId}` and broad client updates to trip/booking data; that is not acceptable for the new authoritative booking system.
6. The old Firestore indexes for `rides`, `parcels`, `food_orders`, `driver_requests`, `handyman_jobs`, `nanny_jobs`, chats, etc. are outside the new bus-only Firebase scope. The new Firestore composite index file is empty by design.

## Mapping from the supplied bus client types

| Legacy Firestore concept | New Laravel/MySQL concept |
|---|---|
| `busTrips` | `bus_trips` |
| embedded `seats[]` | normalized `bus_trip_seats` rows |
| `busBookings` | `bus_bookings` |
| `ticketCode` | `bus_tickets.ticket_number` plus one-time QR token hash |
| `trip.status` | `bus_trips.status` |
| `seat.status` | `bus_trip_seats.status` |
| `baseFare` / seat price | integer `price_minor`, currency `XAF` |
| `bookedAt` | Laravel timestamps / `sold_at` |
| wallet balance mutation | deferred; future ledger/payment contract only |
| `confirmMomoBooking()` | not implemented until payment provider/webhook architecture is approved |

## Index translation

The old Firestore trip queries by status/departure date and agency/status/departure date become MySQL composite indexes on `bus_trips(status, departure_at)` and `bus_trips(agency_id, status, departure_at)`. The old booking list queries become SQL indexes on `bus_bookings(customer_id, created_at)` and `(agency_id, status, created_at)`. MySQL does not need Firestore composite indexes for these queries.

## Data migration is not yet performed

No legacy Firestore export, data deletion, live Firebase change, or production migration has occurred. Before any data import, define field mapping, timestamp normalization, agency/user identity reconciliation, seat-state reconciliation, duplicate ticket handling, and a signed-off cutover/rollback plan.
