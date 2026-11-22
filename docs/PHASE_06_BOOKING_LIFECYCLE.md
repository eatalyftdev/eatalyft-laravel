# Phase 06 Booking Lifecycle — EataLyft Bus Booking System

**Date:** 2026-11-22  
**Project:** `D:\Git Projects\eatalyft-laravel`  
**Status:** Complete  
**Baseline:** Phase 00-05 documentation preserved

---

## 1. Booking Lifecycle Overview

The EataLyft booking lifecycle follows a **temporary hold → confirmed booking → issued ticket** pattern. No payment processing is active in this phase; holds are temporary reservations only.

---

## 2. Booking States

| Status | Description | Can Transition To | Auto-Transition |
|--------|-------------|-------------------|-----------------|
| `held` | Seat temporarily reserved, no payment collected | `confirmed`, `expired` | `expired` (after hold_expires_at) |
| `confirmed` | Booking confirmed after payment verification | `cancelled`, `completed` | — |
| `completed` | Trip completed successfully | — | — |
| `cancelled` | Booking cancelled by customer or agency | — | — |
| `expired` | Hold expired without confirmation | — | — |

**Initial state:** `held` (all new bookings start as held)

---

## 3. Booking Lifecycle Flow

```
┌─────────────┐
│   Customer  │
│  Searches   │
│    Trips    │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Seat        │
│ Selection   │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Create    │
│   Booking   │
│   (HELD)    │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Hold      │
│  Expiration │
│   Window    │
│   (10 min)  │
└──────┬──────┘
       │
    ┌──┴──┐
    │     │
    ▼     ▼
┌─────────┐ ┌──────────────┐
│ Payment │ │ Hold Expires │
│ Confirmed │   (No Payment)   │
└─────────┘ └──────────────┘
       │                │
       ▼                ▼
┌─────────────┐ ┌─────────────┐
│   Issue     │ │   Mark      │
│   Ticket    │ │   Expired   │
└─────────────┘ └─────────────┘
       │
       ▼
┌─────────────┐
│   Passenger │
│   Boards    │
│   (Scan QR) │
└─────────────┘
       │
       ▼
┌─────────────┐
│   Trip      │
│  Completed  │
└─────────────┘
```

---

## 4. Detailed State Transitions

### 4.1 Booking Creation → `held`

**Trigger:** Customer submits seat selection via `POST /api/v1/bus/bookings/holds`

**Preconditions:**
- Trip exists and status is `scheduled` or `boarding`
- All requested seats exist and are in `available` status (or `held` with expired `hold_expires_at`)
- Departure time is in the future
- Sales cutoff has not passed (`now() < departure_at - sales_cutoff_minutes`)

**Database operations (within `DB::transaction`):**
1. `SELECT ... FOR UPDATE` on `bus_trips` row to lock the trip
2. `SELECT ... FOR UPDATE` on `bus_trip_seats` rows for requested seat labels
3. Release any expired holds (status = 'held', hold_expires_at < now())
4. Verify all seats are `available` after release
5. Create `bus_bookings` row with status = 'held', hold_expires_at = now() + 10 minutes
6. Update `bus_trip_seats` rows: status = 'held', booking_id = new booking id, hold_expires_at
7. Commit transaction

**Idempotency:** If `idempotency_key` is provided and already exists for this customer, return the existing booking without creating a new one.

---

### 4.2 Hold Expiration → `expired`

**Trigger:** 
- Scheduled command `expire:bus-seat-holds` (runs every minute)
- Or during booking attempt when an expired hold is encountered

**Conditions:**
- `status = 'held'` AND `hold_expires_at < now()`

**Database operations:**
1. Update `bus_bookings`: status = 'expired'
2. Update `bus_trip_seats`: status = 'available', booking_id = null, hold_expires_at = null

**Note:** The booking service also checks expiration inline during seat selection (`holdSeats()` method releases expired holds before allocating).

---

### 4.3 Payment Confirmation → `confirmed`

**Trigger:** Payment confirmation via payment integration boundary (Phase 5 - deferred)

**Preconditions:**
- Booking status is `held`
- Payment verification succeeds (external system)
- Seat is still held (not expired)

**Database operations:**
1. Update `bus_bookings`: status = 'confirmed', hold_expires_at = null
2. (Future) Create payment record linked to booking

**Important:** A temporary hold is **never** interpreted as a paid booking. Payment confirmation is required before confirmation.

---

### 4.4 Ticket Issuance → `completed` (booking) / `issued` (ticket)

**Trigger:** After payment confirmation

**Preconditions:**
- Booking status is `confirmed`
- Seat is held (not expired)
- No existing ticket for this booking seat

**Database operations (within `DB::transaction`):**
1. Generate unique `ticket_number` (format: `ELB-XXXXXXXXXXXX`)
2. Generate `qr_token_hash` (SHA-256 hash of ticket data, not raw ticket data)
3. Create `bus_tickets` row with status = 'issued'
4. Update `bus_trip_seats`: ticket_id = new ticket id
5. Update `bus_bookings`: status = 'confirmed' (already confirmed)
6. Create `bus_ticket_events` record: event_type = 'issued'

---

### 4.5 Ticket Validation → `used`

**Trigger:** Agency staff scans QR code at boarding

**Database operations:**
1. Verify `qr_token_hash` matches provided token
2. Verify ticket status is `issued`
3. Verify bus_trip departure is today (or within valid window)
4. Update `bus_tickets`: status = 'used', scanned_at = now(), scanned_by = actor_user_id
5. Create `bus_ticket_events` record: event_type = 'scanned', actor_user_id = scanner id

---

### 4.6 Cancellation → `cancelled`

**Trigger:** Customer or agency cancels booking

**Preconditions:**
- Booking status is `held` or `confirmed`
- Trip departure has not passed
- Cancellation is within allowed policy window

**Database operations:**
1. Update `bus_bookings`: status = 'cancelled'
2. Update `bus_trip_seats`: status = 'available', booking_id = null, hold_expires_at = null, ticket_id = null
3. If ticket exists: update `bus_tickets`: status = 'cancelled'
4. Create `bus_ticket_events` record: event_type = 'cancelled'

---

## 5. Concurrency Protection

### 5.1 Row Locking

All seat selection and booking operations use `SELECT ... FOR UPDATE` to lock:
1. The `bus_trips` row (to prevent concurrent modifications to trip capacity)
2. The `bus_trip_seats` rows (to prevent double-booking the same seat)

### 5.2 Hold Expiration Enforcement

Expired holds are released **during** the booking transaction, not just by the scheduled cleanup command. This ensures that even if the scheduler hasn't run, expired holds are released when a customer attempts to book.

### 5.3 Idempotency

The `idempotency_key` column on `bus_bookings` prevents duplicate bookings from repeated API calls (e.g., network retries).

---

## 6. State Transition Matrix

| From → To | Allowed | Condition |
|-----------|---------|-----------|
| held → confirmed | ✅ | Payment verified |
| held → expired | ✅ | hold_expires_at passed |
| held → cancelled | ✅ | Customer/agency cancels |
| confirmed → completed | ✅ | Trip completed |
| confirmed → cancelled | ✅ | Cancellation before departure |
| expired → — | ❌ | Terminal state |
| cancelled → — | ❌ | Terminal state |
| completed → — | ❌ | Terminal state |

---

## 7. Notification Triggers

| Event | Notification | Channel |
|-------|--------------|---------|
| Booking created (held) | "Seat hold created" | Email + In-app |
| Booking confirmed | "Booking confirmed" | Email + In-app |
| Ticket issued | "Ticket ready" | Email + In-app |
| Hold expired | "Seat hold expired" | Email + In-app |
| Booking cancelled | "Booking cancelled" | Email + In-app |
| Trip reminder | "Trip departing soon" | Email + In-app |

**Note:** Notifications are idempotent - duplicate notifications are prevented.

---

## 8. API Endpoint Summary

| Endpoint | Method | Purpose | Auth |
|----------|--------|---------|------|
| `/api/v1/bus/trips` | GET | Discover trips | Public |
| `/api/v1/bus/trips/{trip}` | GET | Trip detail | Public |
| `/api/v1/bus/bookings/holds` | POST | Create seat hold | Sanctum |
| `/api/v1/bus/bookings/mine` | GET | My bookings | Sanctum |

**Future endpoints (Phase 06):**
- `POST /api/v1/bus/bookings/{id}/cancel` - Cancel booking
- `GET /api/v1/bus/tickets/{id}` - Ticket detail
- `POST /api/v1/bus/tickets/{id}/scan` - Scan ticket (agency only)

---

*This document is part of the Phase 06 audit. It is preserved alongside Phase 00-05 documents.*
