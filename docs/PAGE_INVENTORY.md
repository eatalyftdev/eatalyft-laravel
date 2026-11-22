# Page Inventory — EataLyft Technologies

**Version:** 1.0  
**Date:** 2026-11-22  
**Status:** Complete  
**Total Pages:** 40+ views across public, auth, and admin sections

---

## 1. Page Classification

| Section | Count | Status |
|---------|-------|--------|
| Public / Marketing | 11 pages | ✅ All implemented |
| Authentication | 8 pages | ✅ All implemented |
| Authenticated User | 3 pages | ✅ All implemented |
| Admin / RBAC | 30+ pages | ✅ All implemented |
| Admin / Bus | 6 pages | ✅ All implemented |
| Admin / Hotel | 4 pages | ⚠️ Scaffolded |
| Admin / eSIM | 2 pages | ⚠️ Scaffolded |
| Admin / Listings | 1 page | ⚠️ Scaffolded |
| Admin / Boxo | 1 page | ⚠️ Scaffolded |
| Admin / FX/Currencies | 3 pages | ✅ All implemented |
| Admin / Integrations | 1 page | ⚠️ Scaffolded |
| Admin / Notifications | 1 page | ✅ All implemented |
| Errors | 1 page | ✅ Implemented |

---

## 2. Public / Marketing Pages

| Page | Route | View File | Layout | Owner | Access Policy | Status |
|------|-------|-----------|--------|-------|---------------|--------|
| Home | `/` | `public/home.blade.php` | `layouts/public` | PublicController | All (guest) | ✅ Implemented |
| Search | `/search` | `public/search.blade.php` | `layouts/public` | PublicController | All (guest) | ✅ Implemented |
| Services Index | `/services` | `public/services/index.blade.php` | `layouts/public` | PublicController | All (guest) | ✅ Implemented |
| Service Detail | `/services/{slug}` | `public/services/show.blade.php` | `layouts/public` | PublicController | All (guest) | ✅ Implemented |
| About | `/about` | `public/about.blade.php` | `layouts/public` | PublicController | All (guest) | ✅ Implemented |
| Agencies | `/agencies` | `public/agencies.blade.php` | `layouts/public` | PublicController | All (guest) | ✅ Implemented |
| Contact | `/contact` | `public/contact.blade.php` | `layouts/public` | ContactController | All (guest) | ✅ Implemented |
| FAQ | `/faq` | `public/faq.blade.php` | `layouts/public` | PublicController | All (guest) | ✅ Implemented |
| Legal: Terms | `/legal/terms` | `public/legal/terms.blade.php` | `layouts/public` | PublicController | All (guest) | ✅ Implemented |
| Legal: Privacy | `/legal/privacy` | `public/legal/privacy.blade.php` | `layouts/public` | PublicController | All (guest) | ✅ Implemented |
| Legal: Cookies | `/legal/cookies` | `public/legal/cookies.blade.php` | `layouts/public` | PublicController | All (guest) | ✅ Implemented |

---

## 3. Authentication Pages

| Page | Route | View File | Layout | Owner | Access Policy | Status |
|------|-------|-----------|--------|-------|---------------|--------|
| Login (2-step OTP) | `/login` | `auth/login.blade.php` | `layouts/public` | AuthController | Guest only | ✅ Implemented |
| Register (3-step OTP) | `/register` | `auth/register.blade.php` | `layouts/public` | AuthController | Guest only | ✅ Implemented |
| Forgot Password | `/forgot-password` | `auth/forgot-password.blade.php` | `layouts/public` | AuthController | Guest only | ✅ Implemented |
| Reset Password | `/reset-password/{token}` | `auth/reset-password.blade.php` | `layouts/public` | AuthController | Guest only | ✅ Implemented |
| Verify Email | `/verify-email` | `auth/verify-email.blade.php` | `layouts/public` | AuthController | Authenticated | ✅ Implemented |
| Confirm Password | `/confirm-password` | `auth/confirm-password.blade.php` | `layouts/public` | AuthController | Authenticated | ✅ Implemented |
| Update Password | `/password` | `auth/password.blade.php` | `layouts/public` | AuthController | Authenticated | ✅ Implemented |

---

## 4. Authenticated User Pages

| Page | Route | View File | Layout | Owner | Access Policy | Status |
|------|-------|-----------|--------|-------|---------------|--------|
| Dashboard | `/dashboard` | `dashboard.blade.php` | `layouts/app` | DashboardController | Auth + Verified | ✅ Implemented |
| Profile | `/profile` | `profile/show.blade.php` | `layouts/app` | Closure | Auth + Verified | ✅ Implemented |
| Settings | `/settings` | `settings/index.blade.php` | `layouts/app` | SettingsController | Auth + Verified | ✅ Implemented |

---

## 5. Admin Pages — RBAC

| Page | Route | View File | Layout | Owner | Access Policy | Status |
|------|-------|-----------|--------|-------|---------------|--------|
| Users Index | `/users` | `admin/users/index.blade.php` | `layouts/app` | UserController | `users.read` | ✅ Implemented |
| Users Show | `/users/{id}` | `admin/users/show.blade.php` | `layouts/app` | UserController | `users.read` | ✅ Implemented |
| Users Create | `/users/create` | `admin/users/create.blade.php` | `layouts/app` | UserController | `users.read` | ✅ Implemented |
| Users Edit | `/users/{id}/edit` | `admin/users/edit.blade.php` | `layouts/app` | UserController | `users.read` | ✅ Implemented |
| Roles Index | `/roles` | `admin/roles/index.blade.php` | `layouts/app` | RoleController | `roles.read` | ✅ Implemented |
| Roles Show | `/roles/{id}` | `admin/roles/show.blade.php` | `layouts/app` | RoleController | `roles.read` | ✅ Implemented |
| Roles Create | `/roles/create` | `admin/roles/create.blade.php` | `layouts/app` | RoleController | `roles.read` | ✅ Implemented |
| Roles Edit | `/roles/{id}/edit` | `admin/roles/edit.blade.php` | `layouts/app` | RoleController | `roles.read` | ✅ Implemented |

---

## 6. Admin Pages — Agency Management

| Page | Route | View File | Layout | Owner | Access Policy | Status |
|------|-------|-----------|--------|-------|---------------|--------|
| Agencies Index | `/admin/agencies` | `admin/agencies/index.blade.php` | `layouts/app` | AgencyController | `agencies.read` | ✅ Implemented |
| Agencies Show | `/admin/agencies/{id}` | `admin/agencies/show.blade.php` | `layouts/app` | AgencyController | `agencies.read` | ✅ Implemented |
| Agencies Create | `/admin/agencies/create` | `admin/agencies/create.blade.php` | `layouts/app` | AgencyController | `agencies.read` | ✅ Implemented |
| Agencies Edit | `/admin/agencies/{id}/edit` | `admin/agencies/edit.blade.php` | `layouts/app` | AgencyController | `agencies.read` | ✅ Implemented |
| Branches Index | `/admin/agencies/{id}/branches` | `admin/agencies/branches/index.blade.php` | `layouts/app` | AgencyController | `agencies.read` | ✅ Implemented |
| Branches Show | `/admin/agencies/{id}/branches/{branch}` | `admin/agencies/branches/show.blade.php` | `layouts/app` | AgencyController | `agencies.read` | ✅ Implemented |
| Branches Create | `/admin/agencies/{id}/branches/create` | `admin/agencies/branches/create.blade.php` | `layouts/app` | AgencyController | `agencies.read` | ✅ Implemented |
| Branches Edit | `/admin/agencies/{id}/branches/{branch}/edit` | `admin/agencies/branches/edit.blade.php` | `layouts/app` | AgencyController | `agencies.read` | ✅ Implemented |
| Staff Index | `/admin/agencies/{id}/staff` | `admin/agencies/staff/index.blade.php` | `layouts/app` | AgencyController | `agencies.read` | ✅ Implemented |
| Staff Show | `/admin/agencies/{id}/staff/{staff}` | `admin/agencies/staff/show.blade.php` | `layouts/app` | AgencyController | `agencies.read` | ✅ Implemented |
| Staff Create | `/admin/agencies/{id}/staff/create` | `admin/agencies/staff/create.blade.php` | `layouts/app` | AgencyController | `agencies.read` | ✅ Implemented |
| Staff Edit | `/admin/agencies/{id}/staff/{staff}/edit` | `admin/agencies/staff/edit.blade.php` | `layouts/app` | AgencyController | `agencies.read` | ✅ Implemented |

---

## 7. Admin Pages — Bus Module

| Page | Route | View File | Layout | Owner | Access Policy | Status |
|------|-------|-----------|--------|-------|---------------|--------|
| Bus Routes Index | `/bus/routes` | `admin/bus/routes/index.blade.php` | `layouts/app` | BusController | `bus.routes.read` | ✅ Implemented |
| Bus Routes Show | `/bus/routes/{id}` | `admin/bus/routes/show.blade.php` | `layouts/app` | BusController | `bus.routes.read` | ✅ Implemented |
| Bus Routes Create | `/bus/routes/create` | `admin/bus/routes/create.blade.php` | `layouts/app` | BusController | `bus.routes.read` | ✅ Implemented |
| Bus Routes Edit | `/bus/routes/{id}/edit` | `admin/bus/routes/edit.blade.php` | `layouts/app` | BusController | `bus.routes.read` | ✅ Implemented |
| Bus Trips Index | `/bus/trips` | `admin/bus/trips/index.blade.php` | `layouts/app` | BusController | `bus.trips.read` | ✅ Implemented |
| Bus Trips Show | `/bus/trips/{id}` | `admin/bus/trips/show.blade.php` | `layouts/app` | BusController | `bus.trips.read` | ✅ Implemented |
| Bus Trips Create | `/bus/trips/create` | `admin/bus/trips/create.blade.php` | `layouts/app` | BusController | `bus.trips.read` | ✅ Implemented |
| Bus Trips Edit | `/bus/trips/{id}/edit` | `admin/bus/trips/edit.blade.php` | `layouts/app` | BusController | `bus.trips.read` | ✅ Implemented |
| Bus Inventory Index | `/bus/inventory` | `admin/bus/inventory/index.blade.php` | `layouts/app` | BusController | `bus.inventory.read` | ✅ Implemented |
| Bus Inventory Show | `/bus/inventory/{id}` | `admin/bus/inventory/show.blade.php` | `layouts/app` | BusController | `bus.inventory.read` | ✅ Implemented |
| Bus Inventory Create | `/bus/inventory/create` | `admin/bus/inventory/create.blade.php` | `layouts/app` | BusController | `bus.inventory.read` | ✅ Implemented |
| Bus Inventory Edit | `/bus/inventory/{id}/edit` | `admin/bus/inventory/edit.blade.php` | `layouts/app` | BusController | `bus.inventory.read` | ✅ Implemented |
| Bus Tickets Index | `/bus/tickets` | `admin/bus/tickets/index.blade.php` | `layouts/app` | BusController | `bus.tickets.read` | ✅ Implemented |
| Bus Tickets Show | `/bus/tickets/{id}` | `admin/bus/tickets/show.blade.php` | `layouts/app` | BusController | `bus.tickets.read` | ✅ Implemented |
| Bus Tickets Create | `/bus/tickets/create` | `admin/bus/tickets/create.blade.php` | `layouts/app` | BusController | `bus.tickets.read` | ✅ Implemented |
| Bus Tickets Edit | `/bus/tickets/{id}/edit` | `admin/bus/tickets/edit.blade.php` | `layouts/app` | BusController | `bus.tickets.read` | ✅ Implemented |
| Bus Bookings Index | `/bus/bookings` | `admin/bus/bookings/index.blade.php` | `layouts/app` | BusBookingController | `bus.bookings.read` | ✅ Implemented |
| Bus Bookings Show | `/bus/bookings/{id}` | `admin/bus/bookings/show.blade.php` | `layouts/app` | BusBookingController | `bus.bookings.read` | ✅ Implemented |
| Bus Bookings Create | `/bus/bookings/create` | `admin/bus/bookings/create.blade.php` | `layouts/app` | BusBookingController | `bus.bookings.read` | ✅ Implemented |
| Bus Bookings Edit | `/bus/bookings/{id}/edit` | `admin/bus/bookings/edit.blade.php` | `layouts/app` | BusBookingController | `bus.bookings.read` | ✅ Implemented |

---

## 8. Admin Pages — Hotel Module (Scaffolded)

| Page | Route | View File | Layout | Owner | Access Policy | Status |
|------|-------|-----------|--------|-------|---------------|--------|
| Hotels Index | `/hotels` | `admin/hotels/index.blade.php` | `layouts/app` | HotelController | `hotels.read` | ⚠️ Scaffolded |
| Hotels Show | `/hotels/{id}` | `admin/hotels/show.blade.php` | `layouts/app` | HotelController | `hotels.read` | ⚠️ Scaffolded |
| Hotels Create | `/hotels/create` | `admin/hotels/create.blade.php` | `layouts/app` | HotelController | `hotels.read` | ⚠️ Scaffolded |
| Hotels Edit | `/hotels/{id}/edit` | `admin/hotels/edit.blade.php` | `layouts/app` | HotelController | `hotels.read` | ⚠️ Scaffolded |
| Hotel Rooms Index | `/hotels/rooms` | `admin/hotels/rooms/index.blade.php` | `layouts/app` | HotelController | `hotels.rooms.read` | ⚠️ Scaffolded |
| Hotel Rooms Show | `/hotels/rooms/{id}` | `admin/hotels/rooms/show.blade.php` | `layouts/app` | HotelController | `hotels.rooms.read` | ⚠️ Scaffolded |
| Hotel Rooms Create | `/hotels/rooms/create` | `admin/hotels/rooms/create.blade.php` | `layouts/app` | HotelController | `hotels.rooms.read` | ⚠️ Scaffolded |
| Hotel Rooms Edit | `/hotels/rooms/{id}/edit` | `admin/hotels/rooms/edit.blade.php` | `layouts/app` | HotelController | `hotels.rooms.read` | ⚠️ Scaffolded |
| Hotel Rates Index | `/hotels/rates` | `admin/hotels/rates/index.blade.php` | `layouts/app` | HotelController | `hotels.rates.read` | ⚠️ Scaffolded |
| Hotel Rates Show | `/hotels/rates/{id}` | `admin/hotels/rates/show.blade.php` | `layouts/app` | HotelController | `hotels.rates.read` | ⚠️ Scaffolded |
| Hotel Rates Create | `/hotels/rates/create` | `admin/hotels/rates/create.blade.php` | `layouts/app` | HotelController | `hotels.rates.read` | ⚠️ Scaffolded |
| Hotel Rates Edit | `/hotels/rates/{id}/edit` | `admin/hotels/rates/edit.blade.php` | `layouts/app` | HotelController | `hotels.rates.read` | ⚠️ Scaffolded |
| Hotel Availability Index | `/hotels/availability` | `admin/hotels/availability/index.blade.php` | `layouts/app` | HotelController | `hotels.availability.read` | ⚠️ Scaffolded |
| Hotel Availability Show | `/hotels/availability/{id}` | `admin/hotels/availability/show.blade.php` | `layouts/app` | HotelController | `hotels.availability.read` | ⚠️ Scaffolded |
| Hotel Availability Create | `/hotels/availability/create` | `admin/hotels/availability/create.blade.php` | `layouts/app` | HotelController | `hotels.availability.read` | ⚠️ Scaffolded |
| Hotel Availability Edit | `/hotels/availability/{id}/edit` | `admin/hotels/availability/edit.blade.php` | `layouts/app` | HotelController | `hotels.availability.read` | ⚠️ Scaffolded |
| Hotel Bookings Index | `/hotels/bookings` | `admin/hotels/bookings/index.blade.php` | `layouts/app` | HotelBookingController | `hotels.bookings.read` | ⚠️ Scaffolded |
| Hotel Bookings Show | `/hotels/bookings/{id}` | `admin/hotels/bookings/show.blade.php` | `layouts/app` | HotelBookingController | `hotels.bookings.read` | ⚠️ Scaffolded |
| Hotel Bookings Create | `/hotels/bookings/create` | `admin/hotels/bookings/create.blade.php` | `layouts/app` | HotelBookingController | `hotels.bookings.read` | ⚠️ Scaffolded |
| Hotel Bookings Edit | `/hotels/bookings/{id}/edit` | `admin/hotels/bookings/edit.blade.php` | `layouts/app` | HotelBookingController | `hotels.bookings.read` | ⚠️ Scaffolded |

---

## 9. Admin Pages — Listings Module (Scaffolded)

| Page | Route | View File | Layout | Owner | Access Policy | Status |
|------|-------|-----------|--------|-------|---------------|--------|
| Listings Index | `/listings` | `admin/listings/index.blade.php` | `layouts/app` | ListingController | `listings.read` | ⚠️ Scaffolded |
| Listings Show | `/listings/{id}` | `admin/listings/show.blade.php` | `layouts/app` | ListingController | `listings.read` | ⚠️ Scaffolded |
| Listings Create | `/listings/create` | `admin/listings/create.blade.php` | `layouts/app` | ListingController | `listings.read` | ⚠️ Scaffolded |
| Listings Edit | `/listings/{id}/edit` | `admin/listings/edit.blade.php` | `layouts/app` | ListingController | `listings.read` | ⚠️ Scaffolded |

---

## 10. Admin Pages — eSIM Module (Scaffolded)

| Page | Route | View File | Layout | Owner | Access Policy | Status |
|------|-------|-----------|--------|-------|---------------|--------|
| eSIM Providers Index | `/esim/providers` | `admin/esim/providers/index.blade.php` | `layouts/app` | EsimController | `esim.read` | ⚠️ Scaffolded |
| eSIM Providers Show | `/esim/providers/{id}` | `admin/esim/providers/show.blade.php` | `layouts/app` | EsimController | `esim.read` | ⚠️ Scaffolded |
| eSIM Providers Create | `/esim/providers/create` | `admin/esim/providers/create.blade.php` | `layouts/app` | EsimController | `esim.read` | ⚠️ Scaffolded |
| eSIM Providers Edit | `/esim/providers/{id}/edit` | `admin/esim/providers/edit.blade.php` | `layouts/app` | EsimController | `esim.read` | ⚠️ Scaffolded |
| eSIM Plans Index | `/esim/plans` | `admin/esim/plans/index.blade.php` | `layouts/app` | EsimController | `esim.read` | ⚠️ Scaffolded |
| eSIM Plans Show | `/esim/plans/{id}` | `admin/esim/plans/show.blade.php` | `layouts/app` | EsimController | `esim.read` | ⚠️ Scaffolded |
| eSIM Plans Create | `/esim/plans/create` | `admin/esim/plans/create.blade.php` | `layouts/app` | EsimController | `esim.read` | ⚠️ Scaffolded |
| eSIM Plans Edit | `/esim/plans/{id}/edit` | `admin/esim/plans/edit.blade.php` | `layouts/app` | EsimController | `esim.read` | ⚠️ Scaffolded |
| eSIM Orders Index | `/esim/orders` | `admin/esim/orders/index.blade.php` | `layouts/app` | EsimController | `esim.read` | ⚠️ Scaffolded |
| eSIM Orders Show | `/esim/orders/{id}` | `admin/esim/orders/show.blade.php` | `layouts/app` | EsimController | `esim.read` | ⚠️ Scaffolded |

---

## 11. Admin Pages — Boxo Module (Scaffolded)

| Page | Route | View File | Layout | Owner | Access Policy | Status |
|------|-------|-----------|--------|-------|---------------|--------|
| Boxo Miniapps Index | `/boxo/miniapps` | `admin/boxo/miniapps/index.blade.php` | `layouts/app` | BoxoController | `boxo.miniapps.read` | ⚠️ Scaffolded |
| Boxo Miniapps Show | `/boxo/miniapps/{id}` | `admin/boxo/miniapps/show.blade.php` | `layouts/app` | BoxoController | `boxo.miniapps.read` | ⚠️ Scaffolded |
| Boxo Miniapps Create | `/boxo/miniapps/create` | `admin/boxo/miniapps/create.blade.php` | `layouts/app` | BoxoController | `boxo.miniapps.read` | ⚠️ Scaffolded |
| Boxo Miniapps Edit | `/boxo/miniapps/{id}/edit` | `admin/boxo/miniapps/edit.blade.php` | `layouts/app` | BoxoController | `boxo.miniapps.read` | ⚠️ Scaffolded |

---

## 12. Admin Pages — FX / Currencies Module

| Page | Route | View File | Layout | Owner | Access Policy | Status |
|------|-------|-----------|--------|-------|---------------|--------|
| Currencies Index | `/currencies` | `admin/currencies/index.blade.php` | `layouts/app` | CurrencyController | `currencies.read` | ✅ Implemented |
| Currencies Show | `/currencies/{id}` | `admin/currencies/show.blade.php` | `layouts/app` | CurrencyController | `currencies.read` | ✅ Implemented |
| Currencies Create | `/currencies/create` | `admin/currencies/create.blade.php` | `layouts/app` | CurrencyController | `currencies.read` | ✅ Implemented |
| Currencies Edit | `/currencies/{id}/edit` | `admin/currencies/edit.blade.php` | `layouts/app` | CurrencyController | `currencies.read` | ✅ Implemented |
| FX Rates Index | `/fx/rates` | `admin/fx/rates/index.blade.php` | `layouts/app` | FxController | `fx.rates.read` | ✅ Implemented |
| FX Rates Show | `/fx/rates/{id}` | `admin/fx/rates/show.blade.php` | `layouts/app` | FxController | `fx.rates.read` | ✅ Implemented |
| FX Rates Create | `/fx/rates/create` | `admin/fx/rates/create.blade.php` | `layouts/app` | FxController | `fx.rates.read` | ✅ Implemented |
| FX Rates Edit | `/fx/rates/{id}/edit` | `admin/fx/rates/edit.blade.php` | `layouts/app` | FxController | `fx.rates.read` | ✅ Implemented |
| FX Margins Index | `/fx/margins` | `admin/fx/margins/index.blade.php` | `layouts/app` | FxController | `fx.rates.read` | ✅ Implemented |
| FX Margins Show | `/fx/margins/{id}` | `admin/fx/margins/show.blade.php` | `layouts/app` | FxController | `fx.rates.read` | ✅ Implemented |
| FX Margins Create | `/fx/margins/create` | `admin/fx/margins/create.blade.php` | `layouts/app` | FxController | `fx.rates.read` | ✅ Implemented |
| FX Margins Edit | `/fx/margins/{id}/edit` | `admin/fx/margins/edit.blade.php` | `layouts/app` | FxController | `fx.rates.read` | ✅ Implemented |
| FX History Index | `/fx/history` | `admin/fx/history/index.blade.php` | `layouts/app` | FxController | `fx.rates.read` | ✅ Implemented |
| FX History Show | `/fx/history/{id}` | `admin/fx/history/show.blade.php` | `layouts/app` | FxController | `fx.rates.read` | ✅ Implemented |

---

## 13. Admin Pages — KYC Module (Scaffolded)

| Page | Route | View File | Layout | Owner | Access Policy | Status |
|------|-------|-----------|--------|-------|---------------|--------|
| KYC Index | `/kyc` | `admin/kyc/index.blade.php` | `layouts/app` | KycController | `kyc.read` | ⚠️ Scaffolded |
| KYC Show | `/kyc/{id}` | `admin/kyc/show.blade.php` | `layouts/app` | KycController | `kyc.read` | ⚠️ Scaffolded |
| KYC Edit | `/kyc/{id}/edit` | `admin/kyc/edit.blade.php` | `layouts/app` | KycController | `kyc.read` | ⚠️ Scaffolded |
| KYC Update | `/kyc/{id}` | `admin/kyc/edit.blade.php` | `layouts/app` | KycController | `kyc.read` | ⚠️ Scaffolded |

---

## 14. Admin Pages — Audit Module

| Page | Route | View File | Layout | Owner | Access Policy | Status |
|------|-------|-----------|--------|-------|---------------|--------|
| Audit Index | `/audit` | `admin/audit/index.blade.php` | `layouts/app` | AuditController | `audit.read` | ✅ Implemented |
| Audit Show | `/audit/{id}` | `admin/audit/show.blade.php` | `layouts/app` | AuditController | `audit.read` | ✅ Implemented |

---

## 15. Admin Pages — System Settings Module

| Page | Route | View File | Layout | Owner | Access Policy | Status |
|------|-------|-----------|--------|-------|---------------|--------|
| System Settings Index | `/system/settings` | `admin/settings/index.blade.php` | `layouts/app` | SettingsController | `system.settings.read` | ✅ Implemented |

---

## 16. Admin Pages — Integrations Module (Scaffolded)

| Page | Route | View File | Layout | Owner | Access Policy | Status |
|------|-------|-----------|--------|-------|---------------|--------|
| Integrations Index | `/integrations` | `admin/integrations/index.blade.php` | `layouts/app` | IntegrationController | `integrations.read` | ⚠️ Scaffolded |
| Integrations Show | `/integrations/{id}` | `admin/integrations/show.blade.php` | `layouts/app` | IntegrationController | `integrations.read` | ⚠️ Scaffolded |

---

## 17. Admin Pages — Notifications Module

| Page | Route | View File | Layout | Owner | Access Policy | Status |
|------|-------|-----------|--------|-------|---------------|--------|
| Notifications Index | `/notifications` | `admin/notifications/index.blade.php` | `layouts/app` | NotificationController | `notifications.read` | ✅ Implemented |
| Notifications Show | `/notifications/{id}` | `admin/notifications/show.blade.php` | `layouts/app` | NotificationController | `notifications.read` | ✅ Implemented |

---

## 18. Error Pages

| Page | Route | View File | Layout | Owner | Access Policy | Status |
|------|-------|-----------|--------|-------|---------------|--------|
| Error (generic) | N/A | `errors/*` | `layouts/app` | Laravel | All | ✅ Implemented |

---

## 19. Page Ownership Summary

| Module | Total Pages | Implemented | Scaffolded | Policies | Layout |
|--------|-------------|-------------|------------|----------|--------|
| Public / Marketing | 11 | 11 | 0 | — | `layouts/public` |
| Authentication | 8 | 8 | 0 | — | `layouts/public` |
| Authenticated User | 3 | 3 | 0 | — | `layouts/app` |
| RBAC | 8 | 8 | 0 | UserPolicy | `layouts/app` |
| Agency | 12 | 12 | 0 | AgencyPolicy, BranchPolicy | `layouts/app` |
| Bus | 22 | 22 | 0 | BusBookingPolicy, BusTicketPolicy | `layouts/app` |
| Hotel | 20 | 0 | 20 | HotelPolicy, HotelBookingPolicy | `layouts/app` |
| Listings | 4 | 0 | 4 | — | `layouts/app` |
| eSIM | 10 | 0 | 10 | — | `layouts/app` |
| Boxo | 4 | 0 | 4 | — | `layouts/app` |
| FX / Currencies | 14 | 14 | 0 | — | `layouts/app` |
| KYC | 4 | 0 | 4 | — | `layouts/app` |
| Audit | 2 | 2 | 0 | — | `layouts/app` |
| System Settings | 1 | 1 | 0 | — | `layouts/app` |
| Integrations | 2 | 0 | 2 | — | `layouts/app` |
| Notifications | 2 | 2 | 0 | — | `layouts/app` |
| Errors | 1 | 1 | 0 | — | `layouts/app` |
| **Total** | **~120** | **~82** | **~38** | | |

---

## 20. Layout Reference

| Layout File | Path | Used By | Features |
|-------------|------|---------|----------|
| Public Layout | `resources/views/layouts/public.blade.php` | Marketing + Auth pages | Navigation, language toggle, footer, social links, dark mode |
| App Layout | `resources/views/layouts/app.blade.php` | Authenticated + Admin pages | Sneat Pro sidebar, topbar, user menu, currency/language switchers, notifications |

---

## 21. Pages Requiring Attention

| Page | Route | Issue | Recommended Action | Priority |
|------|-------|-------|-------------------|----------|
| Hotel pages (20) | `/hotels/*` | Scaffolded only; hotel system needs inspection | Decide: integrate/adapt/replace per Phase 00 scope | High |
| Listings pages (4) | `/listings/*` | Scaffolded only | Defer to dedicated phase | Medium |
| eSIM pages (10) | `/esim/*` | Scaffolded only; no eSIM provider selected | Defer to dedicated phase | Medium |
| Boxo pages (4) | `/boxo/miniapps/*` | Scaffolded only; Boxo integration pending | Defer to dedicated phase | Medium |
| KYC pages (4) | `/kyc/*` | Scaffolded only; needs KYC workflow implementation | Implement KYC verification flow | Medium |
| Integrations pages (2) | `/integrations/*` | Scaffolded only | Defer to dedicated phase | Low |

---

*This document is the single authoritative page inventory. Update it whenever pages are added, modified, or deprecated.*