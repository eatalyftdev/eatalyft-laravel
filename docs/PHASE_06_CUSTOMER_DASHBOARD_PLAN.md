# Phase 06 Customer Dashboard Plan — EataLyft Bus Booking

**Date:** 2026-11-22  
**Project:** `D:\Git Projects\eatalyft-laravel`  
**Status:** Complete  
**Baseline:** Phase 00-05 documentation preserved

---

## 1. Customer Dashboard Overview

The customer dashboard is the authenticated user's central hub for managing bus bookings and tickets. It uses the existing Laravel authentication system, Blade layouts, and the EataLyft design system.

### Dashboard Routes

| Route | Method | Purpose | Auth |
|-------|--------|---------|------|
| `/dashboard` | GET | Dashboard overview | auth, verified |
| `/dashboard/bookings` | GET | My bookings list | auth, verified |
| `/dashboard/bookings/{booking}` | GET | Booking detail | auth, verified (ownership) |
| `/dashboard/tickets` | GET | My tickets list | auth, verified |
| `/dashboard/tickets/{ticket}` | GET | Ticket detail with QR | auth, verified (ownership) |
| `/dashboard/profile` | GET | Profile management | auth, verified |
| `/dashboard/security` | GET | Security settings | auth, verified |
| `/dashboard/notifications` | GET | Notification list | auth, verified |

---

## 2. Dashboard Overview (`/dashboard`)

### Layout

```
┌─────────────────────────────────────────────────────────────┐
│  Welcome back, [First Name]!                                │
├─────────────────────────────────────────────────────────────┤
│  [ Upcoming Trips ]  [ Total Bookings ]  [ Active Tickets ] │
│     2 trips              5 bookings            3 tickets     │
├─────────────────────────────────────────────────────────────┤
│  Upcoming Trips                                          [+] │
│  ─────────────────────────────────────────────────────      │
│  ● Dakar → Bamako                                        │
│    Dec 15, 2026 • 09:30 • Seat 14A • Confirmed           │
│    Agency: EataLyft Express                              │
│    [View Details] [View Ticket]                          │
│                                                            │
│  ● Bamako → Dakar                                        │
│    Dec 20, 2026 • 14:00 • Seat 08B • Pending Payment     │
│    Agency: Sahel Transport                               │
│    [View Details] [Complete Payment]                     │
├─────────────────────────────────────────────────────────────┤
│  Recent Notifications                                      │
│  ─────────────────────────────────────────────────────      │
│  ● Your seat hold expires in 10 minutes                    │
│    [View Booking]                                         │
│                                                            │
│  ● Your ticket is ready                                    │
│    [View Ticket]                                          │
└─────────────────────────────────────────────────────────────┘
```

### Data Requirements

| Data | Source | Query |
|------|--------|-------|
| Upcoming trips | `bus_bookings` + `bus_trips` + `bus_routes` | Customer's confirmed/held bookings with departure_at > now() |
| Total bookings | `bus_bookings` | Count by customer_id |
| Active tickets | `bus_tickets` | Count by booking's customer_id with status = 'issued' |
| Recent notifications | Notifications table | User's unread notifications |

---

## 3. My Bookings (`/dashboard/bookings`)

### Layout

```
┌─────────────────────────────────────────────────────────────┐
│  My Bookings                                    [Search ▾]  │
│  [All] [Upcoming] [Completed] [Cancelled]                   │
├─────────────────────────────────────────────────────────────┤
│  Ref │ Agency    │ Route           │ Date     │ Status    │ │
│ ─────────────────────────────────────────────────────────   │
│  ELB-ABC123 | EataLyft Express | Dakar → Bamako | Dec 15   │ │
│                    Dec 15, 2026 09:30                      │ │
│                    2 passengers • Seat 14A, 14B           │ │
│                    XAF 25,000 • Confirmed                  │ │
│                    [View Details] [Download Ticket]        │ │
├─────────────────────────────────────────────────────────────┤
│  ELB-DEF456 | Sahel Transport | Bamako → Dakar | Dec 20    │ │
│                    Dec 20, 2026 14:00                      │ │
│                    1 passenger • Seat 08B                  │ │
│                    XAF 18,000 • Pending Payment            │ │
│                    [View Details] [Complete Payment]       │ │
└─────────────────────────────────────────────────────────────┘
```

### Columns

| Column | Source | Display |
|--------|--------|---------|
| Booking Reference | `bus_bookings.booking_ref` | Short code (e.g., ELB-ABC123) |
| Agency | `agencies.name` | Agency name |
| Route | `bus_routes.origin_city_id` → `bus_routes.destination_city_id` | City names |
| Departure Date | `bus_trips.departure_at` | Formatted date/time |
| Passengers | `bus_bookings.metadata` | Count + seat labels |
| Total | `bus_bookings.total_minor` | Formatted currency |
| Status | `bus_bookings.status` | Badge (color-coded) |
| Actions | — | Action buttons |

### Filters

| Filter | Values | Source |
|--------|--------|--------|
| All | All bookings | — |
| Upcoming | status = 'confirmed' AND departure_at > now() | `bus_trips.departure_at` |
| Completed | status = 'completed' | `bus_bookings.status` |
| Cancelled | status = 'cancelled' | `bus_bookings.status` |

---

## 4. My Tickets (`/dashboard/tickets`)

### Layout

```
┌─────────────────────────────────────────────────────────────┐
│  My Tickets                                    [Search ▾]   │
├─────────────────────────────────────────────────────────────┤
│  [Valid] [Used] [Cancelled] [Expired]                       │
├─────────────────────────────────────────────────────────────┤
│  Ticket # │ Agency   │ Route           │ Seat │ Status    │ │
│ ─────────────────────────────────────────────────────────   │
│  ELB-ABC1 | EataLyft   | Dakar → Bamako   | 14A   │ Valid  │ │
│           | Dec 15, 2026 09:30                     │ │ │
│           [View Ticket] [Print] [Download QR]               │ │
├─────────────────────────────────────────────────────────────┤
│  ELB-DEF4 | Sahel Trans. | Bamako → Dakar | 08B   │ Valid  │ │
│           | Dec 20, 2026 14:00                     │ │ │
│           [View Ticket] [Print] [Download QR]               │ │ │
└─────────────────────────────────────────────────────────────┘
```

### Ticket Detail (`/dashboard/tickets/{ticket}`)

```
┌─────────────────────────────────────────────────────────────┐
│  E-Ticket: ELB-ABC123                                        │
│  ─────────────────────────────────────────────────────      │
│  [QR CODE IMAGE]                                             │
│                                                            │
│  Route: Dakar → Bamako                                     │
│  Date: Dec 15, 2026 • 09:30                                │
│  Seat: 14A • Bus: Express Class                             │
│  Passenger: John Doe                                       │
│  Agency: EataLyft Express                                  │
│  Status: Valid                                             │
│                                                            │
│  [Print Ticket] [Download PDF]                             │
└─────────────────────────────────────────────────────────────┘
```

### QR Code

- **Not** stored as plain text
- Contains only: `ticket_number` reference (e.g., `ELB-ABC123`)
- Scanned by agency staff at boarding
- Status checked server-side at scan time

---

## 5. Profile (`/dashboard/profile`)

### Editable Fields

| Field | Type | Validation |
|-------|------|------------|
| First Name | text | Required, max 100 |
| Last Name | text | Required, max 100 |
| Email | email | Required, unique, max 190 |
| Phone | tel | Required, max 20, E.164 format |
| Profile Picture | file | Optional, image, max 2MB |

### Security

- Email changes require re-verification
- Phone changes require OTP verification
- Password changes via existing `/password` endpoint

---

## 6. Security (`/dashboard/security`)

### Features

| Feature | Description |
|---------|-------------|
| Password Change | Change password with current password verification |
| Active Sessions | View and revoke active sessions (Sanctum tokens) |
| Linked Accounts | View linked Firebase account (if any) |
| 2FA Setup | (Future) Two-factor authentication setup |

---

## 7. Notifications (`/dashboard/notifications`)

### Layout

```
┌─────────────────────────────────────────────────────────────┐
│  My Notifications                                    [Mark All Read] │
├─────────────────────────────────────────────────────────────┤
│  ● Your seat hold expires in 10 minutes                     │ │
│    Your seat 14A on Dakar → Bamako will be released soon.   │ │
│    Dec 15, 2026 09:20 • [View Booking]                     │ │
├─────────────────────────────────────────────────────────────┤
│  ● Your ticket is ready                                     │ │
│    Your e-ticket ELB-ABC123 is now available.               │ │ │
│    Dec 14, 2026 20:00 • [View Ticket]                      │ │ │
└─────────────────────────────────────────────────────────────┘
```

---

## 8. Authorization & Security

| Rule | Implementation |
|------|----------------|
| Customers only access own bookings | `where('customer_id', auth()->id())` on all queries |
| Customers only access own tickets | Join through `bus_bookings.customer_id` |
| No cross-customer access | Route model binding + ownership check |
| No admin routes accessible | `can:bus.bookings.read` etc. not required for customer |
| Rate limiting | Standard Laravel rate limiting on API endpoints |

---

## 9. Frontend Implementation Plan

### Blade Views to Create

| View | File | Purpose |
|------|------|---------|
| Dashboard Overview | `resources/views/customer/dashboard/index.blade.php` | Overview with upcoming trips |
| My Bookings | `resources/views/customer/bookings/index.blade.php` | Booking list |
| Booking Detail | `resources/views/customer/bookings/show.blade.php` | Single booking detail |
| My Tickets | `resources/views/customer/tickets/index.blade.php` | Ticket list |
| Ticket Detail | `resources/views/customer/tickets/show.blade.php` | Ticket detail with QR |
| Profile | `resources/views/customer/profile/index.blade.php` | Profile management |
| Security | `resources/views/customer/security/index.blade.php` | Security settings |
| Notifications | `resources/views/customer/notifications/index.blade.php` | Notification list |

### Routes to Add (`routes/web.php`)

```php
Route::middleware(['auth', 'verified'])->group(function () {
    // Customer dashboard
    Route::get('/dashboard/bookings', [Customer\BookingController::class, 'index'])->name('dashboard.bookings');
    Route::get('/dashboard/bookings/{booking}', [Customer\BookingController::class, 'show'])->name('dashboard.bookings.show');
    Route::get('/dashboard/tickets', [Customer\TicketController::class, 'index'])->name('dashboard.tickets');
    Route::get('/dashboard/tickets/{ticket}', [Customer\TicketController::class, 'show'])->name('dashboard.tickets.show');
    Route::get('/dashboard/profile', [Customer\ProfileController::class, 'edit'])->name('dashboard.profile');
    Route::put('/dashboard/profile', [Customer\ProfileController::class, 'update'])->name('dashboard.profile.update');
    Route::get('/dashboard/notifications', [Customer\NotificationController::class, 'index'])->name('dashboard.notifications');
});
```

### Controllers to Create

| Controller | File | Methods |
|------------|------|---------|
| `BookingController` | `app/Http/Controllers/Customer/BookingController.php` | index(), show() |
| `TicketController` | `app/Http/Controllers/Customer/TicketController.php` | index(), show() |
| `ProfileController` | `app/Http/Controllers/Customer/ProfileController.php` | edit(), update() |
| `NotificationController` | `app/Http/Controllers/Customer/NotificationController.php` | index() |

---

## 10. Design Requirements

| Element | Requirement |
|---------|-------------|
| Layout | Uses `layouts.app` (existing Sneat Pro) |
| Design tokens | Uses `--ely-*` CSS variables from `design-tokens.css` |
| Responsive | Mobile-first, works on 360px–1920px |
| Buttons | Bootstrap 5 `btn-primary`, `btn-outline-secondary`, `btn-success`, `btn-danger` |
| Tables | Bootstrap 5 `table table-hover` |
| Badges | Bootstrap 5 `badge bg-success`, `badge bg-warning`, `badge bg-danger` |
| Empty states | "No bookings found" with call-to-action button |
| Loading states | Skeleton loader or spinner |
| Error states | Alert banner with dismiss button |

---

## 11. API Contracts for Frontend

### GET `/api/v1/bus/bookings/mine`

Returns paginated bookings with trip and agency details:

```json
{
  "data": [
    {
      "id": 1,
      "booking_ref": "ELB-ABC123",
      "status": "confirmed",
      "total_minor": 25000,
      "currency": "XAF",
      "created_at": "2026-11-14T10:00:00Z",
      "trip": {
        "id": 5,
        "departure_at": "2026-12-15T09:30:00Z",
        "arrival_at": "2026-12-15T17:00:00Z",
        "route": {
          "origin_city": {"name_en": "Dakar", "name_fr": "Dakar"},
          "destination_city": {"name_en": "Bamako", "name_fr": "Bamako"}
        },
        "agency": {"name": "EataLyft Express", "slug": "eatalyft-express"}
      },
      "seats": [
        {"seat_label": "14A", "seat_type": "standard", "price_minor": 12500},
        {"seat_label": "14B", "seat_type": "standard", "price_minor": 12500}
      ]
    }
  ],
  "meta": { ...pagination }
}
```

### GET `/api/v1/bus/tickets/{ticket}`

Returns ticket details with QR token hash (not raw QR data):

```json
{
  "id": 1,
  "ticket_number": "ELB-ABC123",
  "qr_token_hash": "a1b2c3d4...",
  "status": "issued",
  "passenger_name": "John Doe",
  "fare_minor": 12500,
  "currency": "XAF",
  "trip": {
    "departure_at": "2026-12-15T09:30:00Z",
    "route": {
      "origin_city": {"name_en": "Dakar"},
      "destination_city": {"name_en": "Bamako"}
    },
    "agency": {"name": "EataLyft Express"}
  },
  "seat": {
    "seat_label": "14A"
  },
  "created_at": "2026-11-14T10:00:00Z"
}
```

---

*This document is part of the Phase 06 audit. It is preserved alongside Phase 00-05 documents.*
