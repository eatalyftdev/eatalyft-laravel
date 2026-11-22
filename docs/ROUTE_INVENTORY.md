# Route Inventory — EataLyft Technologies

**Version:** 1.0  
**Date:** 2026-11-22  
**Status:** Complete  
**Total Routes:** ~70 web + ~20 API

---

## 1. Route Classification

| Classification | Count |
|----------------|-------|
| Public (no auth) | 13 web |
| Guest-only (auth redirects) | 20 web |
| Authenticated (web) | 30+ web |
| API (public) | 6 API |
| API (auth:sanctum) | 14 API |

---

## 2. Web Routes — Public (No Auth Required)

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| GET | `/` | `PublicController@home` | — | Public | ✅ Retain | Home page |
| GET | `/search` | `PublicController@search` | — | Public | ✅ Retain | Global search |
| GET | `/services` | `PublicController@services` | — | Public | ✅ Retain | Services index |
| GET | `/services/{slug}` | `PublicController@serviceShow` | — | Public | ✅ Retain | Service detail |
| GET | `/about` | `PublicController@about` | — | Public | ✅ Retain | About page |
| GET | `/agencies` | `PublicController@agencies` | — | Public | ✅ Retain | Agency listing |
| GET | `/contact` | `ContactController@show` | — | Public | ✅ Retain | Contact form |
| POST | `/contact` | `ContactController@store` | — | Public | ✅ Retain | Contact submission |
| GET | `/faq` | `PublicController@faq` | — | Public | ✅ Retain | FAQ page |
| GET | `/legal/terms` | `PublicController@terms` | — | Public | ✅ Retain | Terms of service |
| GET | `/legal/privacy` | `PublicController@privacy` | — | Public | ✅ Retain | Privacy policy |
| GET | `/legal/cookies` | `PublicController@cookies` | — | Public | ✅ Retain | Cookie policy |
| POST | `/theme` | `ThemeController@toggle` | — | Public | ✅ Retain | Theme toggle |
| GET | `/health` | `HealthController` | — | Core | ✅ Retain | Health check |

---

## 3. Web Routes — Guest-Only (Auth Redirects to Login)

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| GET | `/login` | `AuthController@showLogin` | guest | Auth | ✅ Retain | Login page |
| POST | `/login/otp/request` | `AuthController@loginOtpRequest` | guest | Auth | ✅ Retain | Login OTP request |
| POST | `/login/otp/verify` | `AuthController@loginOtpVerify` | guest | Auth | ✅ Retain | Login OTP verify |
| GET | `/register` | `AuthController@showRegister` | guest | Auth | ✅ Retain | Register page |
| POST | `/register/otp/request` | `AuthController@registerOtpRequest` | guest | Auth | ✅ Retain | Register OTP request |
| POST | `/register/otp/verify` | `AuthController@registerOtpVerify` | guest | Auth | ✅ Retain | Register OTP verify |
| POST | `/register/complete` | `AuthController@registerComplete` | guest | Auth | ✅ Retain | Register complete |
| GET | `/forgot-password` | `AuthController@showForgotPassword` | guest | Auth | ✅ Retain | Password request |
| POST | `/forgot-password` | `AuthController@forgotPassword` | guest | Auth | ✅ Retain | Password email |
| GET | `/reset-password/{token}` | `AuthController@showResetPassword` | guest | Auth | ✅ Retain | Password reset form |
| POST | `/reset-password` | `AuthController@resetPassword` | guest | Auth | ✅ Retain | Password store |
| GET | `/register/customer` | `AuthController@showRegisterCustomer` | guest | Auth | ✅ Retain | Customer registration entry |
| GET | `/register/provider` | `AuthController@showRegisterProvider` | guest | Auth | ✅ Retain | Provider registration entry |
| GET | `/register/agency` | `AuthController@showRegisterAgency` | guest | Auth | ✅ Retain | Agency registration entry |
| POST | `/register/provider/submit` | `ProviderRegistrationController@submit` | guest | Auth | ✅ Retain | Provider registration submission |
| POST | `/register/agency/submit` | `AgencyRegistrationController@submit` | guest | Auth | ✅ Retain | Agency registration submission |

### Admin Authentication Routes (Guest-Only)

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| GET | `/admin/login` | `AdminLoginController@showLogin` | guest | Auth | ✅ Retain | Admin login form |
| POST | `/admin/login` | `AdminLoginController@login` | guest | Auth | ✅ Retain | Admin login processing |

---

## 4. Web Routes — Authenticated (auth + verified Middleware)

### Core Routes

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| GET | `/dashboard` | `DashboardController@index` | auth, verified | Core | ✅ Retain | User dashboard |
| GET | `/profile` | Closure | auth, verified | Core | ✅ Retain | Profile view |
| GET | `/settings` | Closure | auth, verified | Core | ✅ Retain | Settings view |
| PUT | `/settings` | `SettingsController@update` | auth, verified | Core | ✅ Retain | Update user settings |
| PUT | `/password` | `SettingsController@updatePassword` | auth, verified | Core | ✅ Retain | Update password |
| POST | `/logout` | `AuthController@logoutWeb` | auth | Auth | ✅ Retain | Logout |
| GET | `/verify-email` | `AuthController@showVerifyEmail` | auth | Auth | ✅ Retain | Verification notice |
| GET | `/verify-email/{id}/{hash}` | `AuthController@verifyEmail` | auth, signed, throttle | Auth | ✅ Retain | Verify email |
| POST | `/email/verification-notification` | `AuthController@sendVerificationEmail` | auth, throttle | Auth | ✅ Retain | Resend verification |
| GET | `/confirm-password` | `AuthController@showConfirmPassword` | auth | Auth | ✅ Retain | Confirm password |
| POST | `/confirm-password` | `AuthController@confirmPassword` | auth | Auth | ✅ Retain | Confirm password |
| GET | `/currency/current` | `FrontendCurrencyController@current` | auth, verified | FX | ✅ Retain | Get current currency |
| POST | `/currency/switch` | `FrontendCurrencyController@switch` | auth, verified | FX | ✅ Retain | Switch currency |
| GET | `/language/current` | `LanguageController@current` | auth, verified | Core | ✅ Retain | Get current language |
| POST | `/language/switch` | `LanguageController@switch` | auth, verified | Core | ✅ Retain | Switch language |

### RBAC Routes

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| Resource | `/users` | `UserController` (index, show, create, store, edit, update, destroy) | auth, verified, can:users.read | RBAC | ✅ Retain | User management |
| Resource | `/roles` | `RoleController` (index, show, create, store, edit, update, destroy) | auth, verified, can:roles.read | RBAC | ✅ Retain | Role management |

### KYC Routes

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| Resource | `/kyc` | `KycController` (index, show, edit, update) | auth, verified, can:kyc.read | KYC | ✅ Retain | KYC reviews |

### Audit Routes

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| Resource | `/audit` | `AuditController` (index, show) | auth, verified, can:audit.read | Core | ✅ Retain | Audit logs |

### System Settings Routes

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| GET | `/system/settings` | `SettingsController@index` | auth, verified, can:system.settings.read | Core | ✅ Retain | System settings index |
| PUT | `/system/settings` | `SettingsController@updateSystem` | auth, verified, can:system.settings.read | Core | ✅ Retain | System settings update |

### Agency Routes

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| Resource | `/admin/agencies` | `AgencyController` (index, show, create, store, edit, update) | auth, verified, can:agencies.read | Agency | ✅ Retain | Agency CRUD |
| Resource | `/admin/agencies.branches` | `AgencyController` (index, show, create, store, edit, update) | auth, verified, can:agencies.read | Agency | ✅ Retain | Branch CRUD (nested) |
| Resource | `/admin/agencies.staff` | `AgencyController` (index, show, create, store, edit, update) | auth, verified, can:agencies.read | Agency | ✅ Retain | Staff CRUD (nested) |

### Bus Routes

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| Resource | `/bus/routes` | `BusController` (index, show, create, store, edit, update) | auth, verified, can:bus.routes.read | Bus | ✅ Retain | Route management |
| Resource | `/bus/trips` | `BusController` (index, show, create, store, edit, update) | auth, verified, can:bus.trips.read | Bus | ✅ Retain | Trip management |
| Resource | `/bus/inventory` | `BusController` (index, show, create, store, edit, update) | auth, verified, can:bus.inventory.read | Bus | ✅ Retain | Inventory management |
| Resource | `/bus/tickets` | `BusController` (index, show, create, store, edit, update) | auth, verified, can:bus.tickets.read | Bus | ✅ Retain | Ticket management |
| Resource | `/bus/bookings` | `BusBookingController` (index, show, create, store, edit, update) | auth, verified, can:bus.bookings.read | Bus | ✅ Retain | Booking management |

### Hotel Routes

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| Resource | `/hotels/bookings` | `HotelBookingController` (index, show, create, store, edit, update) | auth, verified, can:hotels.bookings.read | Hotel | ⚠️ Retain (scaffolded) | Hotel bookings |
| Resource | `/hotels/rooms` | `HotelController` (index, show, create, store, edit, update) | auth, verified, can:hotels.read | Hotel | ⚠️ Retain (scaffolded) | Room management |
| Resource | `/hotels/rates` | `HotelController` (index, show, create, store, edit, update) | auth, verified, can:hotels.read | Hotel | ⚠️ Retain (scaffolded) | Rate management |
| Resource | `/hotels/availability` | `HotelController` (index, show, create, store, edit, update) | auth, verified, can:hotels.read | Hotel | ⚠️ Retain (scaffolded) | Availability management |
| Resource | `/hotels` | `HotelController` (index, show, create, store, edit, update) | auth, verified, can:hotels.read | Hotel | ⚠️ Retain (scaffolded) | Hotel management |

### Listings Routes

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| Resource | `/listings` | `ListingController` (index, show, create, store, edit, update) | auth, verified, can:listings.read | Listings | ⚠️ Retain (scaffolded) | Listing management |

### eSIM Routes

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| Resource | `/esim/providers` | `EsimController` (index, show, create, store, edit, update) | auth, verified, can:esim.read | eSIM | ⚠️ Retain (scaffolded) | Provider management |
| Resource | `/esim/plans` | `EsimController` (index, show, create, store, edit, update) | auth, verified, can:esim.read | eSIM | ⚠️ Retain (scaffolded) | Plan management |
| Resource | `/esim/orders` | `EsimController` (index, show) | auth, verified, can:esim.read | eSIM | ⚠️ Retain (scaffolded) | Order management |

### Boxo Routes

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| Resource | `/boxo/miniapps` | `BoxoController` (index, show, create, store, edit, update) | auth, verified, can:boxo.miniapps.read | Boxo | ⚠️ Retain (scaffolded) | Miniapp management |

### Integration Routes

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| Resource | `/integrations` | `IntegrationController` (index, show) | auth, verified, can:integrations.read | Core | ⚠️ Retain (scaffolded) | Integration management |

### Currency/FX Routes

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| Resource | `/currencies` | `CurrencyController` (index, show, create, store, edit, update) | auth, verified, can:currencies.read | FX | ✅ Retain | Currency management |
| Resource | `/fx/rates` | `FxController` (index, show, create, store, edit, update) | auth, verified, can:fx.rates.read | FX | ✅ Retain | Exchange rate management |
| Resource | `/fx/margins` | `FxController` (index, show, create, store, edit, update) | auth, verified, can:fx.rates.read | FX | ✅ Retain | Margin management |
| Resource | `/fx/history` | `FxController` (index, show) | auth, verified, can:fx.rates.read | FX | ✅ Retain | Rate history |

### Notification Routes

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| Resource | `/notifications` | `NotificationController` (index, show) | auth, verified, can:notifications.read | Core | ✅ Retain | Notification management |

---

## 5. API Routes

### Public API Routes (No Auth)

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| POST | `/api/v1/auth/otp/request` | `AuthController@requestOtp` | — | Auth | ✅ Implemented | Request OTP (any channel) |
| POST | `/api/v1/auth/otp/verify` | `AuthController@verifyOtp` | — | Auth | ✅ Implemented | Verify OTP code |
| POST | `/api/v1/auth/login/otp/request` | `AuthController@loginOtpRequest` | — | Auth | ✅ Implemented | Login OTP request |
| POST | `/api/v1/auth/login/otp` | `AuthController@loginOtp` | — | Auth | ✅ Implemented | Login with OTP |
| POST | `/api/v1/auth/register/complete` | `AuthController@completeRegister` | — | Auth | ✅ Implemented | Complete registration |
| GET | `/api/v1/health` | `HealthController` | — | Core | ✅ Implemented | Health check |
| GET | `/api/v1/bus/trips` | `BusDiscoveryController@index` | — | Bus | ✅ Implemented | Discover trips |
| GET | `/api/v1/bus/trips/{trip}` | `BusDiscoveryController@show` | — | Bus | ✅ Implemented | Trip detail |

### Authenticated API Routes (auth:sanctum)

| Method | URI | Controller / Action | Middleware | Module | Status | Action |
|--------|-----|---------------------|------------|--------|--------|--------|
| GET | `/api/v1/auth/me` | `AuthController@me` | auth:sanctum | Auth | ✅ Implemented | Current user |
| POST | `/api/v1/auth/logout` | `AuthController@logout` | auth:sanctum | Auth | ✅ Implemented | Logout |
| POST | `/api/v1/auth/logout-all` | `AuthController@logoutAll` | auth:sanctum | Auth | ✅ Implemented | Logout all devices |
| POST | `/api/v1/bus/bookings/holds` | `BusBookingApiController@store` | auth:sanctum | Bus | ✅ Implemented | Create seat hold |
| GET | `/api/v1/bus/bookings/mine` | `BusBookingApiController@mine` | auth:sanctum | Bus | ✅ Implemented | My bookings |
| GET | `/api/v1/user` | Closure | auth:sanctum | Core | ✅ Implemented | Current user (alias) |
| POST | `/api/v1/currency/switch` | `CurrencyController@switch` | auth:sanctum | FX | ✅ Implemented | Switch currency |
| GET | `/api/v1/currency/current` | `CurrencyController@current` | auth:sanctum | FX | ✅ Implemented | Current currency |
| POST | `/api/v1/language/switch` | `LanguageController@switch` | auth:sanctum | Core | ✅ Implemented | Switch language |
| GET | `/api/v1/language/current` | `LanguageController@current` | auth:sanctum | Core | ✅ Implemented | Current language |

---

## 6. Console Commands

| Command | Schedule | Purpose | Module | Status |
|---------|----------|---------|--------|--------|
| `expire:bus-seat-holds` | Daily at 02:00 | Expire expired seat holds | Bus | ✅ Implemented |
| `translations:check` | Weekly | Check translation completeness | Core | ✅ Implemented |

---

## 7. Route Duplication Check

**Result:** No duplicate URI/method combinations found.

| Potential Conflict | Resolution |
|--------------------|------------|
| Public `/agencies` vs Admin `/admin/agencies` | Admin moved to `/admin/agencies`; no conflict |
| API `/api/v1/user` vs `/api/v1/auth/me` | Both return same data; `/user` is alias, retained for compatibility |
| `/bus/bookings` web vs `/api/v1/bus/bookings/*` API | Separate URI prefixes; no conflict |

---

## 8. Route Ownership Summary

| Module | Web Routes | API Routes | Policies | Tests | Status |
|--------|-----------|------------|----------|-------|--------|
| Public | 13 | 0 | — | GuestAccessTest | ✅ Implemented |
| Auth | 20 | 5 | — | OtpTest, GuestAccessTest | ✅ Implemented |
| Core (Dashboard, Settings, Health, Audit, System Settings, Integrations, Notifications) | 9 | 4 | — | — | ✅ Implemented |
| RBAC (Users, Roles) | 2 | 0 | — | RoleAccessTest | ✅ Verified |
| KYC | 1 | 0 | — | — | ⚠️ Scaffolded |
| Agency | 3 | 0 | AgencyPolicy, BranchPolicy | — | ✅ Implemented |
| Bus | 5 | 4 | BusBookingPolicy, BusTicketPolicy | — | ✅ Implemented (partial) |
| Hotel | 5 | 0 | HotelPolicy, HotelBookingPolicy | — | ⚠️ Scaffolded |
| Listings | 1 | 0 | — | — | ⚠️ Scaffolded |
| eSIM | 3 | 0 | — | — | ⚠️ Scaffolded |
| Boxo | 1 | 0 | — | — | ⚠️ Scaffolded |
| FX/Currencies | 4 | 4 | — | — | ✅ Implemented |
| Menu/Navigation | 0 | 0 | — | — | ✅ Implemented |
| Partners | 0 | 0 | — | — | ⚠️ Scaffolded |

---

## 9. Routes Requiring Attention

| URI | Issue | Recommended Action | Priority |
|-----|-------|-------------------|----------|
| `/hotels/*` | Scaffolded only; hotel system needs inspection | Decide: integrate/adapt/replace per Phase 00 scope | High |
| `/esim/*` | Scaffolded only; no eSIM provider selected | Defer to dedicated phase | Medium |
| `/boxo/miniapps` | Scaffolded only; Boxo integration pending | Defer to dedicated phase | Medium |
| `/api/v1/bus/bookings/holds` | Creates hold only; no payment confirmation | Expected; payment deferred | Low |
| `/api/v1/user` | Duplicate of `/api/v1/auth/me` | Keep as alias for backward compatibility | Low |

---

## 10. Route Naming Convention

| Pattern | Example | Usage |
|---------|---------|-------|
| Resource singular | `users.index` | Admin resource listing |
| Nested resource | `agencies.branches.index` | Branch under agency |
| Public page | `home`, `about`, `services.index` | Marketing pages |
| Auth action | `login`, `register`, `logout` | Authentication flows |
| OTP action | `login.otp.request`, `register.otp.verify` | OTP flows |
| API v1 | `api/v1/auth/me` | REST API endpoints |
| Settings | `currency.switch`, `language.switch` | User preferences |
| System | `system.settings` | Admin system configuration |

---

*This document is the single authoritative route inventory. Update it whenever routes are added, modified, or deprecated.*