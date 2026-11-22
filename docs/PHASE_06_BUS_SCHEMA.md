# Phase 06 Database Schema — Bus Booking Engine

**Date:** 2026-11-22  
**Project:** `D:\Git Projects\eatalyft-laravel`  
**Status:** Complete  
**Database:** MySQL 8.4.3

---

## 1. Schema Overview

The bus transportation schema consists of **17 tables** spread across two migrations. No new migrations are required for Phase 06 — the existing schema fully supports the bus booking engine.

### Migration Files

| Migration | Tables | Status |
|-----------|--------|--------|
| `2026_11_21_001204_create_bus_tables.php` | cities, bus_classes, amenities, branches, buses, bus_documents, maintenance_logs, crew, seat_layouts, seat_layout_cells | ✅ Applied |
| `2026_11_22_120000_restore_firebase_uid_and_add_bus_operations.php` | users (+firebase_uid), agencies (+owner_user_id), bus_routes, bus_route_stops, bus_route_fares, bus_schedules, bus_trips, bus_trip_seats, bus_bookings, bus_tickets, bus_ticket_events | ✅ Applied |

---

## 2. Complete Table Reference

### Geographic & Reference Tables

#### `cities`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| name_en | varchar | NO | — | — | English city name |
| name_fr | varchar | NO | — | — | French city name |
| region | varchar | YES | — | — | Region/state |
| country | char(2) | NO | — | 'CM' | ISO country code |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

#### `bus_classes`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| name | varchar | NO | — | — | Class name (e.g., VIP, Standard) |
| code | varchar | NO | UK | — | Unique service class code |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

#### `amenities`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| name | varchar | NO | — | — | Amenity name (WiFi, AC, etc.) |
| icon | varchar | YES | — | — | Icon identifier |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

---

### Fleet & Infrastructure Tables

#### `branches`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| agency_id | bigint | NO | FK | — | References agencies.id |
| city_id | bigint | NO | FK | — | References cities.id |
| name | varchar | NO | — | — | Branch name |
| type | varchar | NO | — | 'office' | 'office' or 'terminal' |
| phone | varchar | YES | — | — | Contact phone |
| status | varchar | NO | — | 'active' | 'active' or 'inactive' |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

#### `buses`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| agency_id | bigint | NO | FK | — | References agencies.id |
| plate | varchar | NO | UK | — | License plate (unique) |
| fleet_no | varchar | YES | — | — | Fleet number |
| make | varchar | YES | — | — | Vehicle make |
| model | varchar | YES | — | — | Vehicle model |
| year | unsignedSmallInteger | YES | — | — | Manufacturing year |
| bus_class_id | bigint | YES | FK | — | References bus_classes.id |
| seat_layout_id | bigint | YES | FK | — | References seat_layouts.id |
| capacity | unsignedSmallInteger | NO | — | 0 | Total seat capacity |
| amenities | json | YES | — | — | JSON array of amenity IDs |
| photos | json | YES | — | — | JSON array of photo URLs |
| status | varchar | NO | — | 'active' | 'active', 'maintenance', 'retired' |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

#### `bus_documents`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| bus_id | bigint | NO | FK | — | References buses.id |
| type | varchar | NO | — | — | Document type (inspection, insurance) |
| file_path | varchar | NO | — | — | File path |
| expiry_date | date | YES | — | — | Expiration date |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

#### `maintenance_logs`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| bus_id | bigint | NO | FK | — | References buses.id |
| date | date | NO | — | — | Maintenance date |
| type | varchar | NO | — | — | Maintenance type |
| odometer | unsignedInteger | YES | — | — | Odometer reading |
| notes | text | YES | — | — | Maintenance notes |
| next_due_date | date | YES | — | — | Next maintenance due |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

#### `crew`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| agency_id | bigint | NO | FK | — | References agencies.id |
| name | varchar | NO | — | — | Crew member name |
| phone | varchar | NO | — | — | Contact phone |
| role | varchar | NO | — | 'driver' | Crew role |
| licence_number | varchar | YES | — | — | Driver license number |
| licence_expiry | date | YES | — | — | License expiration |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

---

### Seat Layout Tables

#### `seat_layouts`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| agency_id | bigint | YES | FK | — | References agencies.id |
| name | varchar | NO | — | — | Layout name |
| rows | unsignedSmallInteger | NO | — | 0 | Number of rows |
| cols | unsignedSmallInteger | NO | — | 0 | Number of columns |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

#### `seat_layout_cells`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| seat_layout_id | bigint | NO | FK | — | References seat_layouts.id |
| row | unsignedSmallInteger | NO | — | — | Row number |
| col | unsignedSmallInteger | NO | — | — | Column number |
| type | varchar | NO | — | 'seat' | 'seat', 'aisle', 'door', 'driver', 'blocked' |
| label | varchar | YES | — | — | Seat label (e.g., '1A') |
| class | varchar | YES | — | — | CSS class for styling |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

---

### Operational Tables (Core Booking System)

#### `bus_routes`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| agency_id | bigint | NO | FK | — | References agencies.id |
| origin_city_id | bigint | NO | FK | — | References cities.id |
| destination_city_id | bigint | NO | FK | — | References cities.id |
| distance_km | unsignedInteger | YES | — | — | Route distance in km |
| duration_minutes | unsignedInteger | YES | — | — | Estimated duration in minutes |
| status | varchar | NO | — | 'active' | Route status |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

**Indexes:** `agency_id`, `status`

#### `bus_route_stops`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| bus_route_id | bigint | NO | FK | — | References bus_routes.id |
| city_id | bigint | NO | FK | — | References cities.id |
| branch_id | bigint | YES | FK | — | References branches.id |
| stop_order | unsignedSmallInteger | NO | — | — | Stop sequence number |
| offset_minutes | unsignedInteger | NO | — | 0 | Minutes from route start |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

**Unique:** `(bus_route_id, stop_order)`  
**Indexes:** `bus_route_id`

#### `bus_route_fares`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| bus_route_id | bigint | NO | FK | — | References bus_routes.id |
| from_stop_id | bigint | NO | FK | — | References bus_route_stops.id |
| to_stop_id | bigint | NO | FK | — | References bus_route_stops.id |
| bus_class_id | bigint | YES | FK | — | References bus_classes.id |
| price_minor | unsignedBigInteger | NO | — | — | Fare in minor currency units |
| currency | char(3) | NO | — | 'XAF' | ISO currency code |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

**Indexes:** `bus_route_id`, `from_stop_id`, `to_stop_id`

#### `bus_schedules`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| bus_route_id | bigint | NO | FK | — | References bus_routes.id |
| bus_id | bigint | NO | FK | — | References buses.id |
| default_crew_id | bigint | YES | FK | — | References crew.id |
| departure_time | time | NO | — | — | Scheduled departure time |
| days_of_week | json | NO | — | — | JSON array of active days |
| valid_from | date | NO | — | — | Schedule valid from date |
| valid_until | date | YES | — | — | Schedule valid until date |
| is_active | boolean | NO | — | true | Whether schedule is active |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

**Indexes:** `bus_route_id`, `is_active`

#### `bus_trips`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| bus_route_id | bigint | NO | FK | — | References bus_routes.id |
| agency_id | bigint | NO | FK | — | References agencies.id |
| bus_id | bigint | NO | FK | — | References buses.id |
| schedule_id | bigint | YES | FK | — | References bus_schedules.id |
| crew_id | bigint | YES | FK | — | References crew.id |
| departure_at | datetime | NO | — | — | Concrete departure datetime |
| arrival_at | datetime | YES | — | — | Concrete arrival datetime |
| status | varchar | NO | — | 'scheduled' | 'scheduled', 'boarding', 'departed', 'completed', 'cancelled' |
| capacity | unsignedSmallInteger | NO | — | — | Total seat capacity |
| sales_cutoff_minutes | unsignedSmallInteger | NO | — | 30 | Minutes before departure when sales close |
| allowed_channels | json | YES | — | — | Allowed booking channels |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

**Indexes:** `status`, `departure_at`, `agency_id`, `status`, `departure_at`

---

### Booking System Tables

#### `bus_trip_seats`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| bus_trip_id | bigint | NO | FK | — | References bus_trips.id |
| seat_label | varchar(20) | NO | — | — | Seat label (e.g., '1A') |
| seat_type | varchar | NO | — | 'standard' | Seat type |
| status | varchar | NO | — | 'available' | 'available', 'held', 'sold' |
| price_minor | unsignedBigInteger | NO | — | — | Seat price in minor units |
| currency | char(3) | NO | — | 'XAF' | ISO currency code |
| hold_expires_at | datetime | YES | — | — | Hold expiration timestamp |
| booking_id | bigint | YES | FK | — | References bus_bookings.id (if held/sold) |
| ticket_id | bigint | YES | FK | — | References bus_tickets.id (if ticketed) |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

**Unique:** `(bus_trip_id, seat_label)`  
**Indexes:** `bus_trip_id`, `status`

#### `bus_bookings`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| booking_ref | varchar(40) | NO | UK | — | Unique booking reference |
| customer_id | bigint | YES | FK | — | References users.id |
| agency_id | bigint | NO | FK | — | References agencies.id |
| branch_id | bigint | YES | FK | — | References branches.id |
| sold_by_user_id | bigint | YES | FK | — | References users.id |
| contact_name | varchar | NO | — | — | Lead passenger name |
| contact_phone | varchar(40) | NO | — | — | Contact phone |
| contact_email | varchar | YES | — | — | Contact email |
| channel | varchar | NO | — | 'web' | Booking channel |
| status | varchar | NO | — | 'held' | 'held', 'confirmed', 'cancelled', 'expired', 'completed' |
| hold_expires_at | datetime | YES | — | — | Hold expiration |
| total_minor | unsignedBigInteger | NO | — | — | Total price in minor units |
| currency | char(3) | NO | — | 'XAF' | ISO currency code |
| idempotency_key | varchar(100) | YES | UK | — | Idempotency key (unique) |
| metadata | json | YES | — | — | Additional booking metadata |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

**Unique:** `booking_ref`, `idempotency_key`  
**Indexes:** `customer_id`, `created_at`, `agency_id`, `status`, `created_at`

#### `bus_tickets`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| ticket_number | varchar(48) | NO | UK | — | Unique ticket number |
| booking_id | bigint | NO | FK | — | References bus_bookings.id |
| bus_trip_id | bigint | NO | FK | — | References bus_trips.id |
| bus_trip_seat_id | bigint | NO | FK | — | References bus_trip_seats.id |
| from_stop_id | bigint | YES | FK | — | References bus_route_stops.id |
| to_stop_id | bigint | YES | FK | — | References bus_route_stops.id |
| passenger_name | varchar | NO | — | — | Passenger name |
| passenger_phone | varchar(40) | YES | — | — | Passenger phone |
| fare_minor | unsignedBigInteger | NO | — | — | Ticket fare in minor units |
| currency | char(3) | NO | — | 'XAF' | ISO currency code |
| channel | varchar | NO | — | 'web' | Booking channel |
| branch_id | bigint | YES | FK | — | References branches.id |
| sold_by_user_id | bigint | YES | FK | — | References users.id |
| sold_at | datetime | YES | — | — | Ticket issuance timestamp |
| scanned_at | datetime | YES | — | — | Ticket scan timestamp |
| scanned_by | bigint | YES | FK | — | References users.id |
| qr_token_hash | varchar(64) | YES | UK | — | Hashed QR token (unique) |
| status | varchar | NO | — | 'issued' | 'issued', 'used', 'cancelled', 'expired' |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

**Unique:** `ticket_number`, `qr_token_hash`  
**Indexes:** `bus_trip_id`, `status`, `branch_id`, `sold_at`

#### `bus_ticket_events`
| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| id | bigint | NO | PK | — | Auto-increment |
| bus_ticket_id | bigint | NO | FK | — | References bus_tickets.id |
| actor_user_id | bigint | YES | FK | — | References users.id |
| event_type | varchar | NO | — | — | Event type (issued, scanned, cancelled) |
| payload | json | YES | — | — | Event payload data |
| source | varchar | NO | — | 'system' | Event source |
| created_at | timestamp | YES | — | NULL | Laravel timestamp |
| updated_at | timestamp | YES | — | NULL | Laravel timestamp |

**Indexes:** `bus_ticket_id`, `created_at`

---

## 3. Entity Relationship Diagram

```
┌─────────────┐      ┌──────────────────┐      ┌─────────────────┐
│   Agencies  │      │   Bus Routes     │      │   Bus Schedules │
│             │      │                  │      │                 │
│ id (PK) ────┼─────<│ agency_id (FK)   │      │ bus_route_id    │
│ city_id (FK)│      │ origin_city_id   │      │ bus_id          │
│ owner_user_id│     │ destination_city │      │ default_crew_id │
└─────────────┘      │ status           │      │ departure_time  │
                     │                  │      │ days_of_week    │
                     └────────┬─────────┘      │ valid_from      │
                              │                 │ valid_to        │
        ┌────────────────────┼─────────────────┘
        │                    │
        ▼                    ▼
┌─────────────┐      ┌──────────────────┐
│ Bus Route   │      │ Bus Route Stops  │
│ Fare        │      │                  │
│             │      │ id (PK)          │
│ bus_route_id│      │ bus_route_id (FK)│
│ from_stop_id│──────<│ city_id (FK)     │
│ to_stop_id  │      │ branch_id (FK)   │
│ price_minor │      │ stop_order       │
│ currency    │      │ offset_minutes   │
└─────────────┘      └────────┬─────────┘
                              │
                              ▼
                      ┌─────────────────┐
                      │   Bus Trips     │
                      │                 │
                      │ bus_route_id (FK│
                      │ agency_id (FK)  │
                      │ bus_id (FK)     │
                      │ departure_at    │
                      │ arrival_at      │
                      │ status          │
                      │ capacity        │
                      └────────┬────────┘
                               │
        ┌──────────────────────┼──────────────────────┐
        │                      │                      │
        ▼                      ▼                      ▼
┌─────────────┐      ┌──────────────┐      ┌───────────────┐
│ Bus Trip    │      │ Bus Booking  │      │ Bus Ticket    │
│ Seats       │      │              │      │               │
│             │      │ booking_ref  │──────<│ ticket_number │
│ bus_trip_id │──────<│ customer_id  │──────<│ bus_trip_id   │
│ seat_label  │      │ agency_id    │      │ bus_trip_seat │
│ seat_type   │      │ status       │      │ from_stop_id  │
│ status      │      │ hold_expires │      │ to_stop_id    │
│ price_minor │      │ total_minor  │      │ fare_minor    │
│ currency    │      │ idempotency  │      │ passenger_name│
│ hold_expires│      │   _key       │      │ status        │
│ booking_id  │      │              │      │ qr_token_hash │
│ ticket_id   │      │              │      │ sold_at       │
└─────────────┘      └──────────────┘      │ scanned_at    │
                                            │ scanned_by    │
                                            └───────────────┘
                                                    │
                                                    ▼
                                            ┌───────────────┐
                                            │ Bus Ticket    │
                                            │ Events        │
                                            │               │
                                            │ bus_ticket_id │
                                            │ event_type    │
                                            │ payload       │
                                            └───────────────┘
```

---

## 4. Financial Convention

All monetary amounts use **integer minor units** with an explicit `char(3)` currency code (default `XAF` - Central African CFA Franc).

| Column | Type | Description |
|--------|------|-------------|
| `price_minor` | unsignedBigInteger | Price in minor units (e.g., 10000 = 10,000 XAF) |
| `total_minor` | unsignedBigInteger | Booking total in minor units |
| `fare_minor` | unsignedBigInteger | Ticket fare in minor units |
| `currency` | char(3) | ISO 4217 currency code (default 'XAF') |

---

## 5. Index Summary

| Table | Index | Columns | Type |
|-------|-------|---------|------|
| bus_routes | agency_id | agency_id | Regular |
| bus_routes | agency_id + status | agency_id, status | Composite |
| bus_route_stops | bus_route_id | bus_route_id | Regular |
| bus_route_stops | bus_route_id + stop_order | bus_route_id, stop_order | Unique |
| bus_route_fares | bus_route_id | bus_route_id | Regular |
| bus_route_fares | bus_route_id + from_stop + to_stop | bus_route_id, from_stop_id, to_stop_id | Composite |
| bus_schedules | bus_route_id | bus_route_id | Regular |
| bus_schedules | bus_route_id + is_active | bus_route_id, is_active | Composite |
| bus_trips | status | status | Regular |
| bus_trips | departure_at | departure_at | Regular |
| bus_trips | agency_id + status + departure_at | agency_id, status, departure_at | Composite |
| bus_trip_seats | bus_trip_id | bus_trip_id | Regular |
| bus_trip_seats | bus_trip_id + status | bus_trip_id, status | Composite |
| bus_trip_seats | bus_trip_id + seat_label | bus_trip_id, seat_label | Unique |
| bus_bookings | customer_id | customer_id | Regular |
| bus_bookings | customer_id + created_at | customer_id, created_at | Composite |
| bus_bookings | agency_id + status + created_at | agency_id, status, created_at | Composite |
| bus_bookings | booking_ref | booking_ref | Unique |
| bus_bookings | idempotency_key | idempotency_key | Unique |
| bus_tickets | bus_trip_id | bus_trip_id | Regular |
| bus_tickets | bus_trip_id + status | bus_trip_id, status | Composite |
| bus_tickets | branch_id + sold_at | branch_id, sold_at | Composite |
| bus_tickets | ticket_number | ticket_number | Unique |
| bus_tickets | qr_token_hash | qr_token_hash | Unique |
| bus_ticket_events | bus_ticket_id | bus_ticket_id | Regular |
| bus_ticket_events | bus_ticket_id + created_at | bus_ticket_id, created_at | Composite |

---

## 6. No New Migrations Required

The existing schema fully supports Phase 06 functionality:
- ✅ Bus routes, stops, and fares
- ✅ Bus schedules and trips
- ✅ Seat inventory with hold expiration
- ✅ Bookings with idempotency
- ✅ Tickets with QR token hashes
- ✅ Ticket event audit trail
- ✅ Agency, branch, city, crew, and fleet management

No new database tables or columns are required for the customer booking flow.

---

*This document is part of the Phase 06 audit. It is preserved alongside Phase 00-05 documents.*
