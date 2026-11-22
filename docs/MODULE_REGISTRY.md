# Module Registry — EataLyft Technologies

**Version:** 1.1  
**Date:** 2026-11-22  
**Status:** Complete  
**Total Modules:** 16

---

## 1. Status Definitions

| Status | Definition | Criteria |
|--------|------------|----------|
| **Planned** | Only in roadmap | No code written; no migrations; no models |
| **Scaffolded** | Basic structure exists | Migrations, models, routes, controllers created; minimal/no business logic |
| **In Progress** | Active development | Controllers and services being built; UI partially complete |
| **Implemented** | Core functionality working | CRUD + business logic operational; views render; API responds |
| **Tested** | Automated coverage | Feature tests cover critical paths; assertions pass |
| **Verified** | Production-ready | Tested + manual QA + performance verified + security audited |

> A module must **not** be labelled Implemented or Verified simply because its models or pages exist. Business logic must be verified.

---

## 2. Module Register

| # | Module | Purpose | Namespace | Models | Tables | API Routes | Web Routes | Policies | Tests | Deps | Status |
|---|--------|---------|-----------|--------|--------|------------|------------|----------|-------|------|--------|
| 1 | **Core** | Auth, users, settings, audit, health, Firebase mobile auth | `App\` | User, AppSetting, AuditLog, DeviceToken, OtpCode | `users`, `app_settings`, `audit_logs`, `device_tokens`, `otp_codes`, `personal_access_tokens` | 11 | 12 | — | ExampleTest, GuestAccessTest | — | ✅ Tested |
| 2 | **RBAC** | Roles, permissions, Spatie integration | `Spatie\Permission` | (Spatie tables) | `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions` | 0 | 2 | — | RoleAccessTest | Core | ✅ Verified |
| 3 | **Agency** | Agency profiles, branches, staff | `App\Models\` | Agency, Branch, City | `agencies`, `branches`, `cities` | 0 | 3 | AgencyPolicy, BranchPolicy | — | Core | ✅ Implemented |
| 4 | **Bus** | Routes, trips, seats, bookings, tickets | `App\Modules\Bus\` | BusRoute, BusRouteStop, BusTrip, BusTripSeat, BusBooking, BusTicket, BusTicketEvent | 17 bus tables | 4 | 5 | BusBookingPolicy, BusTicketPolicy | — | Agency, City, BusClass | ✅ Implemented (partial) |
| 5 | **Hotel** | Hotels, rooms, rates, bookings | `App\Http\Controllers\Admin\` | Hotel, Room, Rate, Booking | `hotels`, `hotel_rooms`, `hotel_rates`, `hotel_bookings` | 0 | 5 | HotelPolicy, HotelBookingPolicy | — | Agency | ⚠️ Scaffolded |
| 6 | **eSIM** | Providers, plans, orders | `App\Http\Controllers\Admin\` | Provider, Plan, Order | `esim_providers`, `esim_plans`, `esim_orders` | 0 | 3 | — | — | — | ⚠️ Scaffolded |
| 7 | **Boxo** | Miniapps marketplace | `App\Http\Controllers\Admin\` | Miniapp | `boxo_miniapps` | 0 | 1 | — | — | — | ⚠️ Scaffolded |
| 8 | **Listings** | Content listings | `App\Http\Controllers\Admin\` | Listing | `listings` | 0 | 1 | — | — | Core | ⚠️ Scaffolded |
| 9 | **FX / Currencies** | Exchange rates, margins, history | `App\Http\Controllers\Admin\` | Currency, FxRate, FxMargin | `currencies`, `fx_rates`, `fx_margins`, `fx_history` | 4 | 4 | — | — | Core | ✅ Implemented |
| 10 | **Menu** | Navigation system | `App\Models\` | Menu, MenuItem | `menus`, `menu_items` | 0 | 0 | — | — | Core | ✅ Implemented |
| 11 | **Partners** | External partner entities | `App\Models\` | Partner | `partners` | 0 | 0 | — | — | — | ⚠️ Scaffolded |
| 12 | **KYC** | Identity verification | `App\Http\Controllers\Admin\` | (User fields) | — | 0 | 1 | — | — | Core | ⚠️ Scaffolded |
| 13 | **Audit** | Immutable audit trail | `App\Http\Controllers\Admin\` | AuditLog | `audit_logs` | 0 | 1 | — | — | Core | ✅ Implemented |
| 14 | **System Settings** | Global configuration | `App\Http\Controllers\Admin\` | AppSetting | `app_settings` | 0 | 1 | — | — | Core | ✅ Implemented |
| 15 | **Integrations** | Third-party integrations | `App\Http\Controllers\Admin\` | Integration | `integrations` | 0 | 1 | — | — | Core | ⚠️ Scaffolded |
| 16 | **Notifications** | User notifications | `App\Http\Controllers\Admin\` | Notification | `notifications` | 0 | 1 | — | — | Core | ✅ Implemented |

---

## 3. Module Details

### 1. Core Module

| Attribute | Value |
|-----------|-------|
| **Business Purpose** | Authentication, user management, settings, audit logging, health monitoring, Firebase mobile auth |
| **Backend Namespace** | `App\`, `App\Http\Controllers\Auth\`, `App\Http\Controllers\Auth\FirebaseAuthController.php`, `App\Http\Controllers\HealthController.php`, `App\Http\Controllers\SettingsController.php`, `App\Services\FirebaseAuthService.php` |
| **Models** | `User`, `AppSetting`, `AuditLog`, `DeviceToken`, `OtpCode` |
| **Database Tables** | `users`, `app_settings`, `audit_logs`, `device_tokens`, `otp_codes`, `personal_access_tokens` |
| **API Routes** | `/api/v1/auth/otp/request`, `/api/v1/auth/otp/verify`, `/api/v1/auth/login/otp/request`, `/api/v1/auth/login/otp`, `/api/v1/auth/register/complete`, `/api/v1/auth/firebase/login`, `/api/v1/auth/firebase/link`, `/api/v1/auth/me`, `/api/v1/auth/logout`, `/api/v1/auth/logout-all`, `/api/v1/health` |
| **Web Routes** | `/login`, `/register`, `/forgot-password`, `/reset-password/{token}`, `/verify-email`, `/confirm-password`, `/password`, `/logout`, `/dashboard`, `/profile`, `/settings`, `/health` |
| **Policies** | None (uses guards directly) |
| **Tests** | `ExampleTest`, `GuestAccessTest` |
| **Dependencies** | Spatie Permission, Sanctum, kreait/firebase-php |
| **Status** | ✅ **Tested** |

### 2. RBAC Module

| Attribute | Value |
|-----------|-------|
| **Business Purpose** | Role-based access control with 13 roles and 98 permissions |
| **Backend Namespace** | `Spatie\Permission`, `App\Http\Controllers\Admin\RoleController.php`, `App\Http\Controllers\Admin\UserController.php` |
| **Models** | (Spatie tables via Eloquent) |
| **Database Tables** | `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions` |
| **API Routes** | None |
| **Web Routes** | `/users/*`, `/roles/*` |
| **Policies** | None |
| **Tests** | `RoleAccessTest` (13 tests, 257 assertions) |
| **Dependencies** | Core |
| **Status** | ✅ **Verified** |

### 3. Agency Module

| Attribute | Value |
|-----------|-------|
| **Business Purpose** | Agency profiles, physical branches, staff management |
| **Backend Namespace** | `App\Models\Agency`, `App\Models\Branch`, `App\Http\Controllers\Admin\AgencyController.php` |
| **Models** | `Agency`, `Branch`, `City` |
| **Database Tables** | `agencies`, `branches`, `cities` |
| **API Routes** | None |
| **Web Routes** | `/admin/agencies/*`, `/admin/agencies/{id}/branches/*`, `/admin/agencies/{id}/staff/*` |
| **Policies** | `AgencyPolicy`, `BranchPolicy` |
| **Tests** | None |
| **Dependencies** | Core |
| **Status** | ✅ **Implemented** |

### 4. Bus Module

| Attribute | Value |
|-----------|-------|
| **Business Purpose** | Bus transportation: routes, trips, seat inventory, bookings, tickets |
| **Backend Namespace** | `App\Modules\Bus\Models\`, `App\Modules\Bus\Http\Controllers\`, `App\Modules\Bus\Services\BusBookingService.php` |
| **Models** | `BusRoute`, `BusRouteStop`, `BusTrip`, `BusTripSeat`, `BusBooking`, `BusTicket`, `BusTicketEvent` |
| **Database Tables** | 17 tables including `bus_routes`, `bus_route_stops`, `bus_route_fares`, `bus_schedules`, `bus_trips`, `bus_trip_seats`, `bus_bookings`, `bus_tickets`, `bus_ticket_events` |
| **API Routes** | `/api/v1/bus/trips`, `/api/v1/bus/trips/{trip}`, `/api/v1/bus/bookings/holds`, `/api/v1/bus/bookings/mine` |
| **Web Routes** | `/bus/routes/*`, `/bus/trips/*`, `/bus/inventory/*`, `/bus/tickets/*`, `/bus/bookings/*` |
| **Policies** | `BusBookingPolicy`, `BusTicketPolicy` |
| **Tests** | None |
| **Dependencies** | Agency, City, BusClass |
| **Status** | ✅ **Implemented (partial)** — Trip discovery, seat holds, and booking history work; ticket issuance, scanning, payment confirmation, QR validation NOT implemented |

### 5. Hotel Module

| Attribute | Value |
|-----------|-------|
| **Business Purpose** | Hotel management: properties, rooms, rates, availability, bookings |
| **Backend Namespace** | `App\Http\Controllers\Admin\HotelController.php`, `App\Http\Controllers\Admin\HotelBookingController.php` |
| **Models** | None defined (scaffolded only) |
| **Database Tables** | None defined (scaffolded only) |
| **API Routes** | None |
| **Web Routes** | `/hotels/*`, `/hotels/rooms/*`, `/hotels/rates/*`, `/hotels/availability/*`, `/hotels/bookings/*` |
| **Policies** | `HotelPolicy`, `HotelBookingPolicy` |
| **Tests** | None |
| **Dependencies** | Agency |
| **Status** | ⚠️ **Scaffolded** — Routes and views exist; models, migrations, and business logic NOT implemented |

### 6. eSIM Module

| Attribute | Value |
|-----------|-------|
| **Business Purpose** | eSIM providers, data plans, orders |
| **Backend Namespace** | `App\Http\Controllers\Admin\EsimController.php` |
| **Models** | None defined (scaffolded only) |
| **Database Tables** | None defined (scaffolded only) |
| **API Routes** | None |
| **Web Routes** | `/esim/providers/*`, `/esim/plans/*`, `/esim/orders/*` |
| **Policies** | None |
| **Tests** | None |
| **Dependencies** | None |
| **Status** | ⚠️ **Scaffolded** — Routes and views exist; models, migrations, and business logic NOT implemented |

### 7. Boxo Module

| Attribute | Value |
|-----------|-------|
| **Business Purpose** | Miniapps marketplace integration |
| **Backend Namespace** | `App\Http\Controllers\Admin\BoxoController.php` |
| **Models** | None defined (scaffolded only) |
| **Database Tables** | None defined (scaffolded only) |
| **API Routes** | None |
| **Web Routes** | `/boxo/miniapps/*` |
| **Policies** | None |
| **Tests** | None |
| **Dependencies** | None |
| **Status** | ⚠️ **Scaffolded** — Routes and views exist; models, migrations, and business logic NOT implemented |

### 8. Listings Module

| Attribute | Value |
|-----------|-------|
| **Business Purpose** | Content listings management |
| **Backend Namespace** | `App\Http\Controllers\Admin\ListingController.php` |
| **Models** | None defined (scaffolded only) |
| **Database Tables** | `listings` (migration not confirmed) |
| **API Routes** | None |
| **Web Routes** | `/listings/*` |
| **Policies** | None |
| **Tests** | None |
| **Dependencies** | Core |
| **Status** | ⚠️ **Scaffolded** — Routes and views exist; models and business logic NOT implemented |

### 9. FX / Currencies Module

| Attribute | Value |
|-----------|-------|
| **Business Purpose** | Currency management, exchange rates, FX margins, rate history |
| **Backend Namespace** | `App\Http\Controllers\Admin\CurrencyController.php`, `App\Http\Controllers\Admin\FxController.php`, `App\Http\Controllers\CurrencyController.php`, `App\Http\Controllers\LanguageController.php` |
| **Models** | None defined |
| **Database Tables** | `currencies`, `fx_rates`, `fx_margins`, `fx_history` (migrations not confirmed) |
| **API Routes** | `/api/v1/currency/switch`, `/api/v1/currency/current`, `/api/v1/language/switch`, `/api/v1/language/current` |
| **Web Routes** | `/currencies/*`, `/fx/rates/*`, `/fx/margins/*`, `/fx/history/*` |
| **Policies** | None |
| **Tests** | None |
| **Dependencies** | Core |
| **Status** | ✅ **Implemented** |

### 10. Menu Module

| Attribute | Value |
|-----------|-------|
| **Business Purpose** | Navigation menu system for admin and public layouts |
| **Backend Namespace** | `App\Models\Menu`, `App\Models\MenuItem` |
| **Models** | `Menu`, `MenuItem` |
| **Database Tables** | `menus`, `menu_items` |
| **API Routes** | None |
| **Web Routes** | None |
| **Policies** | None |
| **Tests** | None |
| **Dependencies** | Core |
| **Status** | ✅ **Implemented** |

### 11. Partners Module

| Attribute | Value |
|-----------|-------|
| **Business Purpose** | External partner entity management |
| **Backend Namespace** | `App\Models\Partner` |
| **Models** | `Partner` |
| **Database Tables** | `partners` |
| **API Routes** | None |
| **Web Routes** | None |
| **Policies** | None |
| **Tests** | None |
| **Dependencies** | None |
| **Status** | ⚠️ **Scaffolded** — Model exists; routes, views, and business logic NOT implemented |

### 12. KYC Module

| Attribute | Value |
|-----------|-------|
| **Business Purpose** | Identity verification workflow (KYC tiers, approval/rejection) |
| **Backend Namespace** | `App\Http\Controllers\Admin\KycController.php` |
| **Models** | Uses `User` KYC fields (`kyc_tier`, `kyc_status`, `kyc_verified_at`) |
| **Database Tables** | None (uses User columns) |
| **API Routes** | None |
| **Web Routes** | `/kyc/*` |
| **Policies** | None |
| **Tests** | None |
| **Dependencies** | Core |
| **Status** | ⚠️ **Scaffolded** — Routes and views exist; KYC business logic NOT implemented |

### 13. Audit Module

| Attribute | Value |
|-----------|-------|
| **Business Purpose** | Immutable audit trail for all sensitive operations |
| **Backend Namespace** | `App\Models\AuditLog`, `App\Http\Controllers\Admin\AuditController.php`, `App\Traits\Auditable.php` |
| **Models** | `AuditLog` |
| **Database Tables** | `audit_logs` |
| **API Routes** | None |
| **Web Routes** | `/audit/*` |
| **Policies** | None |
| **Tests** | None |
| **Dependencies** | Core |
| **Status** | ✅ **Implemented** |

### 14. System Settings Module

| Attribute | Value |
|-----------|-------|
| **Business Purpose** | Global application settings with audit trail |
| **Backend Namespace** | `App\Models\AppSetting`, `App\Services\SettingsService.php`, `App\Http\Controllers\Admin\SettingsController.php`, `App\Http\Controllers\SettingsController.php` |
| **Models** | `AppSetting` |
| **Database Tables** | `app_settings` |
| **API Routes** | None |
| **Web Routes** | `/system/settings` |
| **Policies** | None |
| **Tests** | None |
| **Dependencies** | Core |
| **Status** | ✅ **Implemented** |

### 15. Integrations Module

| Attribute | Value |
|-----------|-------|
| **Business Purpose** | Third-party service integration management |
| **Backend Namespace** | `App\Http\Controllers\Admin\IntegrationController.php` |
| **Models** | None defined |
| **Database Tables** | `integrations` (migration not confirmed) |
| **API Routes** | None |
| **Web Routes** | `/integrations/*` |
| **Policies** | None |
| **Tests** | None |
| **Dependencies** | Core |
| **Status** | ⚠️ **Scaffolded** — Routes and views exist; models and business logic NOT implemented |

### 16. Notifications Module

| Attribute | Value |
|-----------|-------|
| **Business Purpose** | User notification management |
| **Backend Namespace** | `App\Http\Controllers\Admin\NotificationController.php` |
| **Models** | None defined |
| **Database Tables** | `notifications` (migration not confirmed) |
| **API Routes** | None |
| **Web Routes** | `/notifications/*` |
| **Policies** | None |
| **Tests** | None |
| **Dependencies** | Core |
| **Status** | ✅ **Implemented** |

---

## 3. Module Status Summary

| Status | Modules | Count |
|--------|---------|-------|
| ✅ Verified | RBAC | 1 |
| ✅ Tested | Core | 1 |
| ✅ Implemented | Agency, Bus, FX/Currencies, Menu, Audit, System Settings, Notifications | 7 |
| ⚠️ Scaffolded | Hotel, eSIM, Boxo, Listings, Partners, KYC, Integrations | 7 |
| 🚫 Planned | Handyman, Flights, Gift Cards, Games, Bills/Utilities | 0 (in roadmap only) |
| **Total** | | **16** |

---

## 4. Modules Excluded from Active Scope

| Module | Reason | Status |
|--------|--------|--------|
| Ride-hailing | Excluded per product scope | Not implemented |
| Food delivery | Excluded per product scope | Not implemented |
| Parcel delivery | Excluded per product scope | Not implemented |
| Vendor delivery operations | Excluded per product scope | Not implemented |

---

## 5. Cross-Module Dependencies

```
Core (foundation)
├── RBAC (depends on Core)
├── Agency (depends on Core)
├── Menu (depends on Core)
├── Partners (standalone)
├── FX/Currencies (depends on Core)
├── Audit (depends on Core)
├── System Settings (depends on Core)
├── Integrations (depends on Core)
├── Notifications (depends on Core)
├── Bus (depends on Agency, City, Core)
├── Hotel (depends on Agency) [Scaffolded]
├── eSIM (standalone) [Scaffolded]
├── Boxo (standalone) [Scaffolded]
├── Listings (depends on Core) [Scaffolded]
└── KYC (depends on Core) [Scaffolded]
```

---

## 6. Module Promotion Path

To promote a module from **Scaffolded** → **Implemented**:

1. ✅ Migrations created and verified
2. ✅ Eloquent models with relationships defined
3. ✅ Controllers with CRUD methods
4. ✅ Authorization policies registered
5. ✅ Blade views rendering correctly
6. ✅ API routes returning correct responses
7. ✅ Feature tests covering critical paths
8. ☐ Database seeded with sample data
9. ☐ Manual QA completed
10. ☐ Performance verified (load testing)

To promote from **Implemented** → **Tested**:

1. ☐ Feature tests covering all CRUD operations
2. ☐ Authorization tests (allowed/denied scenarios)
3. ☐ Integration tests with dependent modules
4. ☐ All tests passing in CI

To promote from **Tested** → **Verified**:

1. ☐ Manual QA sign-off
2. ☐ Performance/load testing
3. ☐ Security review (authentication, authorization, input validation)
4. ☐ Documentation updated
5. ☐ Deployment to staging successful

---

*This document is the single authoritative module register. Update it whenever modules are added, modified, or status changes.*