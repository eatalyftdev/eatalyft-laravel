# Database Schema — EataLyft Technologies

**Version:** 1.0  
**Date:** 2026-11-22  
**Status:** Canonical Reference  
**Database Engine:** MySQL 8.4.3  
**Authoritative Store:** MySQL (Firebase is supplementary only)

---

## 1. Design Principles

| Principle | Rule |
|-----------|------|
| **MySQL Authoritative** | All business data in MySQL; Firebase never stores business records |
| **Integer Minor Units** | All monetary amounts as `unsignedBigInteger` minor units + `char(3)` currency (default `XAF`) |
| **Row-Level Locking** | Seat holds and booking operations use `SELECT ... FOR UPDATE` inside `DB::transaction()` |
| **No Duplicate Entities** | Single `users`, `agencies`, `branches`, `bus_*` tables confirmed |
| **Status Over Soft Deletes** | Explicit `status` / `is_active` columns instead of `deleted_at` |
| **Audit Trail** | `audit_logs` table + `Auditable` trait on key models |
| **Hold ≠ Payment** | Temporary seat hold never interpreted as paid booking |

---

## 2. Complete Table Reference

### Core Module (6 tables)

| Table | Purpose | PK | FKs | Unique Constraints | Indexes | Model | Migration | Status |
|-------|---------|-----|-----|-------------------|---------|-------|-----------|--------|
| `users` | Users, profiles, RBAC, KYC | `id` | — | `email` (unique), `firebase_uid` (unique, nullable), `public_id` (unique) | — | `App\Models\User` | `0001_01_01_000000_create_users_table.php` | ✅ Implemented |
| `permissions` | Spatie permission definitions | `id` | — | `name` (unique) | — | Spatie | `create_permission_tables.php` | ✅ Implemented |
| `roles` | Role definitions | `id` | — | `name` (unique) | — | Spatie | `create_permission_tables.php` | ✅ Implemented |
| `model_has_permissions` | Permission assignments | `permission_id`, `model_type`, `model_id` | `permission_id` → `permissions.id` | — | `model_type`, `model_id` | Spatie | `create_permission_tables.php` | ✅ Implemented |
| `model_has_roles` | Role assignments | `role_id`, `model_type`, `model_id` | `role_id` → `roles.id` | — | `model_type`, `model_id` | Spatie | `create_permission_tables.php` | ✅ Implemented |
| `role_has_permissions` | Role-permission grants | `permission_id`, `role_id` | Both → respective tables | — | — | Spatie | `create_permission_tables.php` | ✅ Implemented |

### Audit & Settings Module (3 tables)

| Table | Purpose | PK | FKs | Unique Constraints | Indexes | Model | Migration | Status |
|-------|---------|-----|-----|-------------------|---------|-------|-----------|--------|
| `audit_logs` | Immutable audit trail | `id` | `user_id` (nullable) | — | `auditable_type`, `auditable_id`, `user_id`, `created_at` | `App\Models\AuditLog` | `create_audit_logs_table.php` | ✅ Implemented |
| `app_settings` | Key-value settings with audit | `id` | — | `key` (unique) | `key`, `updated_at` | `App\Models\AppSetting` | `create_app_settings_table.php` | ✅ Implemented |
| `device_tokens` | Push notification tokens | `id` | `user_id` → `users.id` | — | `user_id`, `token` | `App\Models\DeviceToken` | `create_device_tokens_table.php` | ✅ Implemented |

### Authentication Module (2 tables)

| Table | Purpose | PK | FKs | Unique Constraints | Indexes | Model | Migration | Status |
|-------|---------|-----|-----|-------------------|---------|-------|-----------|--------|
| `otp_codes` | OTP storage (hashed dest, HMAC code_hash) | `id` | — | — | `destination_hash`, `purpose`, `expires_at`, `used_at` | `App\Models\OtpCode` | `create_otp_codes_table.php` | ✅ Implemented |
| `personal_access_tokens` | Sanctum API tokens | `id` | `tokenable_id` (polymorphic) | `token` (unique) | `tokenable_type`, `tokenable_id` | (Laravel) | `create_personal_access_tokens_table.php` | ✅ Implemented |

### Agency Module (3 tables)

| Table | Purpose | PK | FKs | Unique Constraints | Indexes | Model | Migration | Status |
|-------|---------|-----|-----|-------------------|---------|-------|-----------|--------|
| `agencies` | Agency profiles | `id` | `city_id` → `cities.id`, `owner_user_id` → `users.id` | `slug` (unique), `email` (unique) | `city_id`, `owner_user_id`, `status`, `is_active` | `App\Models\Agency` | `create_agencies_table.php` + `add_fields_to_agencies_table.php` | ✅ Implemented |
| `branches` | Physical locations | `id` | `agency_id` → `agencies.id`, `city_id` → `cities.id` | — | `agency_id`, `city_id`, `status` | `App\Models\Branch` | `create_bus_tables.php` | ✅ Implemented |
| `cities` | Geographic reference | `id` | — | — | `country`, `name_en`, `name_fr` | `App\Models\City` | `create_bus_tables.php` | ✅ Implemented |

### Bus Module Fleet & Infrastructure (10 tables)

| Table | Purpose | PK | FKs | Unique Constraints | Indexes | Model | Migration | Status |
|-------|---------|-----|-----|-------------------|---------|-------|-----------|--------|
| `bus_classes` | Service classes (VIP, Standard, Economy) | `id` | — | `code` (unique) | — | (no model yet) | `create_bus_tables.php` | ✅ Implemented |
| `amenities` | Bus amenities (WiFi, AC, etc.) | `id` | — | — | — | (no model yet) | `create_bus_tables.php` | ✅ Implemented |
| `buses` | Fleet vehicles | `id` | `agency_id` → `agencies.id`, `bus_class_id` → `bus_classes.id`, `seat_layout_id` → `seat_layouts.id` | `plate` (unique) | `agency_id`, `bus_class_id`, `seat_layout_id`, `status` | (no model yet) | `create_bus_tables.php` | ✅ Implemented |
| `bus_documents` | Vehicle documents (inspection, insurance) | `id` | `bus_id` → `buses.id` | — | `bus_id` | (no model yet) | `create_bus_tables.php` | ✅ Implemented |
| `maintenance_logs` | Maintenance records | `id` | `bus_id` → `buses.id` | — | `bus_id` | (no model yet) | `create_bus_tables.php` | ✅ Implemented |
| `crew` | Drivers and crew staff | `id` | `agency_id` → `agencies.id` | `licence_number` (unique) | `agency_id`, `role` | (no model yet) | `create_bus_tables.php` | ✅ Implemented |
| `seat_layouts` | Seat map templates per agency | `id` | `agency_id` → `agencies.id` | — | `agency_id` | (no model yet) | `create_bus_tables.php` | ✅ Implemented |
| `seat_layout_cells` | Individual seat positions in layout | `id` | `seat_layout_id` → `seat_layouts.id` | `seat_layout_id`, `row`, `col` | `seat_layout_id` | (no model yet) | `create_bus_tables.php` | ✅ Implemented |

### Bus Module Operational Tables (8 tables)

| Table | Purpose | PK | FKs | Unique Constraints | Indexes | Model | Migration | Status |
|-------|---------|-----|-----|-------------------|---------|-------|-----------|--------|
| `bus_routes` | Route definitions (origin→destination) | `id` | `agency_id` → `agencies.id`, `origin_city_id` → `cities.id`, `destination_city_id` → `cities.id` | — | `agency_id`, `status` | `App\Modules\Bus\Models\BusRoute` | `restore_firebase_uid_and_add_bus_operations.php` | ✅ Implemented |
| `bus_route_stops` | Ordered stops per route | `id` | `bus_route_id` → `bus_routes.id`, `city_id` → `cities.id`, `branch_id` → `branches.id` | `bus_route_id`, `stop_order` | `bus_route_id` | `App\Modules\Bus\Models\BusRouteStop` | `restore_firebase_uid_and_add_bus_operations.php` | ✅ Implemented |
| `bus_route_fares` | Segment pricing per class | `id` | `bus_route_id` → `bus_routes.id`, `from_stop_id` → `bus_route_stops.id`, `to_stop_id` → `bus_route_stops.id`, `bus_class_id` → `bus_classes.id` | — | `bus_route_id`, `from_stop_id`, `to_stop_id` | (no model yet) | `restore_firebase_uid_and_add_bus_operations.php` | ✅ Implemented |
| `bus_schedules` | Recurring timetables | `id` | `bus_route_id` → `bus_routes.id`, `bus_id` → `buses.id`, `default_crew_id` → `crew.id` | — | `bus_route_id`, `is_active` | (no model yet) | `restore_firebase_uid_and_add_bus_operations.php` | ✅ Implemented |
| `bus_trips` | Concrete trip instances | `id` | `bus_route_id` → `bus_routes.id`, `agency_id` → `agencies.id`, `bus_id` → `buses.id`, `schedule_id` → `bus_schedules.id`, `crew_id` → `crew.id` | — | `status`, `departure_at` | `App\Modules\Bus\Models\BusTrip` | `restore_firebase_uid_and_add_bus_operations.php` | ✅ Implemented |
| `bus_trip_seats` | Seat inventory per trip (hold/available) | `id` | `bus_trip_id` → `bus_trips.id`, `booking_id` → `bus_bookings.id`, `ticket_id` → `bus_tickets.id` | `bus_trip_id`, `seat_label` | `bus_trip_id`, `status` | `App\Modules\Bus\Models\BusTripSeat` | `restore_firebase_uid_and_add_bus_operations.php` | ✅ Implemented |
| `bus_bookings` | Booking records (held/confirmed/expired) | `id` | `customer_id` → `users.id`, `agency_id` → `agencies.id`, `branch_id` → `branches.id`, `sold_by_user_id` → `users.id` | `booking_ref` (unique), `idempotency_key` (unique) | `customer_id`, `created_at` | `App\Modules\Bus\Models\BusBooking` | `restore_firebase_uid_and_add_bus_operations.php` | ✅ Implemented |
| `bus_tickets` | Issued tickets | `id` | `booking_id` → `bus_bookings.id`, `bus_trip_id` → `bus_trips.id`, `bus_trip_seat_id` → `bus_trip_seats.id`, `from_stop_id` → `bus_route_stops.id`, `to_stop_id` → `bus_route_stops.id`, `branch_id` → `branches.id`, `sold_by_user_id` → `users.id`, `scanned_by` → `users.id` | `ticket_number` (unique), `qr_token_hash` (unique) | `bus_trip_id`, `status` | `App\Modules\Bus\Models\BusTicket` | `restore_firebase_uid_and_add_bus_operations.php` | ✅ Schema only |
| `bus_ticket_events` | Ticket audit trail | `id` | `bus_ticket_id` → `bus_tickets.id`, `actor_user_id` → `users.id` | — | `bus_ticket_id`, `created_at` | `App\Modules\Bus\Models\BusTicketEvent` | `restore_firebase_uid_and_add_bus_operations.php` | ✅ Schema only |

### Navigation Module (2 tables)

| Table | Purpose | PK | FKs | Unique Constraints | Indexes | Model | Migration | Status |
|-------|---------|-----|-----|-------------------|---------|-------|-----------|--------|
| `menus` | Navigation menu definitions | `id` | — | `key` (unique) | — | `App\Models\Menu` | `create_menus_table.php` | ✅ Implemented |
| `menu_items` | Menu item links | `id` | `menu_id` → `menus.id`, `parent_id` → `menu_items.id` | — | `menu_id`, `parent_id` | `App\Models\MenuItem` | `create_menu_items_table.php` | ✅ Implemented |

### Partners Module (1 table)

| Table | Purpose | PK | FKs | Unique Constraints | Indexes | Model | Migration | Status |
|-------|---------|-----|-----|-------------------|---------|-------|-----------|--------|
| `partners` | External partner entities | `id` | — | — | — | `App\Models\Partner` | `create_partners_table.php` | ✅ Implemented |

### Base Laravel Tables (3 tables)

| Table | Purpose | PK | Migration | Status |
|-------|---------|-----|-----------|--------|
| `cache` | Laravel cache store | `key` | `create_cache_table.php` | ✅ |
| `jobs` | Failed job records | `id` | `create_jobs_table.php` | ✅ |
| `failed_jobs` | Failed job storage | `id` | `create_failed_jobs_table.php` | ✅ |

---

## 3. Bus Module Entity Relationship Diagram

```
┌─────────────┐      ┌──────────────────┐      ┌─────────────────┐
│   Agencies  │      │   Bus Routes     │      │   Bus Schedules │
│             │      │                  │      │                 │
│ id (PK) ────┼─────<│ agency_id (FK)   │      │ bus_route_id    │
│ city_id (FK)│      │ origin_city_id   │      │ bus_id          │
│ owner_user_id│     │ destination_city │      │ default_crew_id │
└─────────────┘      │ status           │      │ departure_time  │
                     └────────┬─────────┘      │ days_of_week    │
                              │                 │ valid_from      │
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
│             │      │ booking_ref  │      │ ticket_number │
│ bus_trip_id │──────<│ customer_id  │──────<│ booking_id  │
│ seat_label  │      │ agency_id    │      │ bus_trip_id   │
│ seat_type   │      │ status       │      │ bus_trip_seat │
│ status      │      │ total_minor  │      │ from_stop_id  │
│ price_minor │      │ hold_expires │      │ to_stop_id    │
│ currency    │      │ idempotency  │      │ fare_minor    │
│ hold_expires│      │   _key       │      │ passenger_name│
│ booking_id  │      │              │      │ status        │
│ ticket_id   │      │              │      │ qr_token_hash │
└─────────────┘      └──────────────┘      └───────────────┘
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

## 4. Financial Amount Convention

All monetary values follow this pattern:

| Column | Type | Description |
|--------|------|-------------|
| `price_minor` | `unsignedBigInteger` | Price in minor units (e.g., XAF cents) |
| `currency` | `char(3)` | ISO 4217 currency code (default `XAF`) |
| `total_minor` | `unsignedBigInteger` | Booking total in minor units |
| `fare_minor` | `unsignedBigInteger` | Ticket fare in minor units |

**Example:** `10000` XAF = 10,000 XAF (no decimal conversion needed for XAF since it has no minor unit subdivision, but the column still stores whole units).

---

## 5. Constraints & Validation Rules

### Unique Constraints (Business Rules)

| Constraint | Table | Columns | Business Meaning |
|------------|-------|---------|------------------|
| Unique email | `users` | `email` | One account per email |
| Unique firebase_uid | `users` | `firebase_uid` | One Firebase account per user |
| Unique slug | `agencies` | `slug` | One URL per agency |
| Unique plate | `buses` | `plate` | One license plate per bus |
| Unique licence_number | `crew` | `licence_number` | One license per driver |
| Unique seat label per trip | `bus_trip_seats` | `bus_trip_id`, `seat_label` | No duplicate seats on same trip |
| Unique stop order per route | `bus_route_stops` | `bus_route_id`, `stop_order` | Sequential stops |
| Unique booking ref | `bus_bookings` | `booking_ref` | One reference per booking |
| Unique idempotency key | `bus_bookings` | `idempotency_key` | Prevent duplicate bookings |
| Unique ticket number | `bus_tickets` | `ticket_number` | One ticket number |
| Unique QR token hash | `bus_tickets` | `qr_token_hash` | One QR per ticket |

### Foreign Key Actions

| Table | FK Column | References | On Delete | On Update |
|-------|-----------|------------|-----------|-----------|
| `agencies` | `city_id` | `cities.id` | restrict | cascade |
| `agencies` | `owner_user_id` | `users.id` | nullOnDelete | cascade |
| `branches` | `agency_id` | `agencies.id` | cascade | cascade |
| `branches` | `city_id` | `cities.id` | cascade | cascade |
| `buses` | `agency_id` | `agencies.id` | cascade | cascade |
| `buses` | `bus_class_id` | `bus_classes.id` | nullOnDelete | cascade |
| `buses` | `seat_layout_id` | `seat_layouts.id` | nullOnDelete | cascade |
| `bus_routes` | `agency_id` | `agencies.id` | cascade | cascade |
| `bus_routes` | `origin_city_id` | `cities.id` | restrict | cascade |
| `bus_routes` | `destination_city_id` | `cities.id` | restrict | cascade |
| `bus_route_stops` | `bus_route_id` | `bus_routes.id` | cascade | cascade |
| `bus_route_stops` | `city_id` | `cities.id` | restrict | cascade |
| `bus_route_stops` | `branch_id` | `branches.id` | nullOnDelete | cascade |
| `bus_route_fares` | `bus_route_id` | `bus_routes.id` | cascade | cascade |
| `bus_route_fares` | `from_stop_id` | `bus_route_stops.id` | cascade | cascade |
| `bus_route_fares` | `to_stop_id` | `bus_route_stops.id` | cascade | cascade |
| `bus_route_fares` | `bus_class_id` | `bus_classes.id` | nullOnDelete | cascade |
| `bus_schedules` | `bus_route_id` | `bus_routes.id` | cascade | cascade |
| `bus_schedules` | `bus_id` | `buses.id` | restrict | cascade |
| `bus_schedules` | `default_crew_id` | `crew.id` | nullOnDelete | cascade |
| `bus_trips` | `bus_route_id` | `bus_routes.id` | restrict | cascade |
| `bus_trips` | `agency_id` | `agencies.id` | restrict | cascade |
| `bus_trips` | `bus_id` | `buses.id` | restrict | cascade |
| `bus_trips` | `schedule_id` | `bus_schedules.id` | nullOnDelete | cascade |
| `bus_trips` | `crew_id` | `crew.id` | nullOnDelete | cascade |
| `bus_trip_seats` | `bus_trip_id` | `bus_trips.id` | cascade | cascade |
| `bus_trip_seats` | `booking_id` | `bus_bookings.id` | nullOnDelete | cascade |
| `bus_trip_seats` | `ticket_id` | `bus_tickets.id` | nullOnDelete | cascade |
| `bus_bookings` | `customer_id` | `users.id` | nullOnDelete | cascade |
| `bus_bookings` | `agency_id` | `agencies.id` | restrict | cascade |
| `bus_bookings` | `branch_id` | `branches.id` | nullOnDelete | cascade |
| `bus_bookings` | `sold_by_user_id` | `users.id` | nullOnDelete | cascade |
| `bus_tickets` | `booking_id` | `bus_bookings.id` | restrict | cascade |
| `bus_tickets` | `bus_trip_id` | `bus_trips.id` | restrict | cascade |
| `bus_tickets` | `bus_trip_seat_id` | `bus_trip_seats.id` | restrict | cascade |
| `bus_tickets` | `from_stop_id` | `bus_route_stops.id` | nullOnDelete | cascade |
| `bus_tickets` | `to_stop_id` | `bus_route_stops.id` | nullOnDelete | cascade |
| `bus_tickets` | `branch_id` | `branches.id` | nullOnDelete | cascade |
| `bus_tickets` | `sold_by_user_id` | `users.id` | nullOnDelete | cascade |
| `bus_tickets` | `scanned_by` | `users.id` | nullOnDelete | cascade |
| `bus_ticket_events` | `bus_ticket_id` | `bus_tickets.id` | cascade | cascade |
| `bus_ticket_events` | `actor_user_id` | `users.id` | nullOnDelete | cascade |

---

## 6. Duplicate Entity Prevention

| Entity Type | Tables Found | Status |
|-------------|--------------|--------|
| Users | `users` only | ✅ Single |
| Agencies | `agencies` only | ✅ Single |
| Branches | `branches` only | ✅ Single |
| Bus Routes | `bus_routes` only | ✅ Single |
| Bus Trips | `bus_trips` only | ✅ Single |
| Bus Bookings | `bus_bookings` only | ✅ Single |
| Bus Tickets | `bus_tickets` only | ✅ Single |
| Bus Trip Seats | `bus_trip_seats` only | ✅ Single |
| Authentication | `users` + `personal_access_tokens` + `otp_codes` | ✅ Single each |

**No duplicate entities found.**

---

## 7. Migration Consistency

| Migration | Applied To | Status |
|-----------|------------|--------|
| `0001_01_01_000000_create_users_table.php` | All environments | ✅ |
| `0001_01_01_000001_create_cache_table.php` | All environments | ✅ |
| `0001_01_01_000002_create_jobs_table.php` | All environments | ✅ |
| `create_permission_tables.php` | All environments | ✅ |
| `add_role_kyc_currency_to_users_table.php` | All environments | ✅ |
| `create_audit_logs_table.php` | All environments | ✅ |
| `create_app_settings_table.php` | All environments | ✅ |
| `remove_firebase_uid_from_users_table.php` | All environments | ✅ |
| `create_device_tokens_table.php` | All environments | ✅ |
| `create_otp_codes_table.php` | All environments | ✅ |
| `create_agencies_table.php` | All environments | ✅ |
| `create_bus_tables.php` (fleet) | All environments | ✅ |
| `create_menus_table.php` | All environments | ✅ |
| `create_menu_items_table.php` | All environments | ✅ |
| `create_partners_table.php` | All environments | ✅ |
| `add_fields_to_agencies_table.php` | All environments | ✅ |
| `create_personal_access_tokens_table.php` | All environments | ✅ |
| `make_destination_encrypted_nullable_on_otp_codes_table.php` | All environments | ✅ |
| `restore_firebase_uid_and_add_bus_operations.php` | All environments | ✅ |

**Note:** Migrations have NOT been run against production. Testing database `eatalyft_testing` used for PHPUnit. Dev database `eatalyft_local` used for development.

---

## 8. Future Schema Considerations

| Consideration | Table(s) Affected | Priority |
|---------------|-------------------|----------|
| Handyman module (deferred schema only) | `handyman_providers`, `handyman_bookings` | Low |
| eSIM plans and orders | `esim_providers`, `esim_plans`, `esim_orders` | Medium |
| Hotel module | `hotels`, `hotel_rooms`, `hotel_rates`, `hotel_bookings` | High (post-Phase 01) |
| Flights module | `flights`, `flight_seats`, `flight_bookings` | Future |
| Payment records | `payments`, `payment_transactions` | Future (deferred phase) |
| Wallet balances | `wallets`, `wallet_transactions` | Future |

---

*This document is the single authoritative database schema reference. Update it whenever tables, columns, indexes, or relationships change.*