# Project Architecture — EataLyft Technologies

**Version:** 1.0  
**Date:** 2026-11-22  
**Status:** Authoritative Reference

---

## 1. High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           EATALYFT PLATFORM                                   │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐                  │
│  │   Web App    │    │  Mobile App  │    │  Admin Panel │                  │
│  │  (Blade +    │    │  (Expo/React │    │  (Blade +    │                  │
│  │  Livewire)   │    │   Native)    │    │  Livewire)   │                  │
│  └──────┬───────┘    └──────┬───────┘    └──────┬───────┘                  │
│         │                   │                   │                          │
│         └───────────────────┼───────────────────┘                          │
│                             ▼                                              │
│                    ┌──────────────────┐                                    │
│                    │   Laravel 12     │                                    │
│                    │   REST API       │                                    │
│                    │   /api/v1        │                                    │
│                    └────────┬─────────┘                                    │
│                             │                                              │
│         ┌───────────────────┼───────────────────┐                          │
│         ▼                   ▼                   ▼                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                     │
│  │   MySQL 8.4  │  │   Redis      │  │   Firebase   │                     │
│  │  (Primary)   │  │  (Cache/     │  │  (Auth/      │                     │
│  │              │  │   Queue/     │  │   Push/      │                     │
│  │  Business    │  │   Sessions)  │  │   Realtime)  │                     │
│  │  Data        │  │              │  │              │                     │
│  └──────────────┘  └──────────────┘  └──────────────┘                     │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Technology Stack

| Layer | Technology | Version | Purpose |
|-------|------------|---------|---------|
| **Backend Framework** | Laravel | 12.x | Core application |
| **Language** | PHP | 8.2+ | Server-side |
| **Primary Database** | MySQL | 8.4 | Business data (authoritative) |
| **Cache/Queue/Session** | Redis | 7.x | Performance, jobs, sessions |
| **API Authentication** | Laravel Sanctum | 4.x | Token-based API auth |
| **Web Authentication** | Session + OTP | Custom | Email/SMS/WhatsApp OTP |
| **Authorization** | Spatie Laravel Permission | 8.5 | RBAC (13 roles, 98 permissions) |
| **Firebase** | Firebase Admin SDK | Latest | Auth verification, FCM, limited realtime |
| **Frontend (Web)** | Blade + Bootstrap 5 | 5.3 (Sneat Pro) | Server-rendered UI |
| **Frontend (Mobile)** | Expo / React Native | Latest | iOS/Android apps |
| **CSS Framework** | Tailwind CSS | 4.0 (via Vite) | Design tokens (Studio reference) |
| **Build Tool** | Vite | 7.x | Asset compilation |
| **Testing** | PHPUnit 11 + Playwright 1.65 | | Unit, Feature, E2E |
| **Code Style** | Laravel Pint | 1.24 | PSR-12 + Laravel standards |

---

## 3. Application Structure (Modular Monolith)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/              # OTP authentication (web + API)
│   │   ├── Admin/             # 18 admin module controllers
│   │   ├── PublicController.php
│   │   ├── ContactController.php
│   │   ├── CurrencyController.php
│   │   ├── HealthController.php
│   │   ├── LanguageController.php
│   │   ├── SettingsController.php
│   │   └── ThemeController.php
│   ├── Middleware/
│   │   ├── TrustCloudflareProxies.php
│   │   └── RateLimitMiddleware.php
│   └── Requests/
│       └── Auth/              # Form requests for auth flows
├── Models/                    # Core models (User, Agency, Branch, etc.)
├── Modules/
│   └── Bus/                   # Bus transportation module
│       ├── Models/            # 7 Eloquent models
│       ├── Http/Controllers/  # 2 API controllers
│       └── Services/          # BusBookingService
├── Services/
│   ├── Otp/                   # OTP sender implementations (5 files)
│   ├── OtpService.php         # Core OTP logic
│   ├── FirebaseAuthService.php # Firebase ID-token verification
│   └── SettingsService.php    # Settings management
├── Policies/                  # 7 ownership-scoped policies
├── Traits/
│   └── Auditable.php          # Automatic audit logging
├── Console/Commands/          # Scheduled commands
├── Providers/
│   ├── AppServiceProvider.php
│   └── AuthServiceProvider.php
├── Http/
│   ├── Middleware/
│   │   ├── TrustCloudflareProxies.php
│   │   ├── RateLimitMiddleware.php
│   │   └── VerifyFirebaseIdToken.php
│   └── Controllers/
│       └── Auth/
│           └── FirebaseAuthController.php  # Mobile login + account linking
└── Observers/                 # Model observers (empty currently)
```

---

## 4. Request Lifecycle

### Web Request
```
HTTP Request
    ▼
TrustCloudflareProxies (real IP from CF headers)
    ▼
RateLimitMiddleware (configurable per route)
    ▼
Laravel Kernel → Router
    ▼
Controller / Livewire Component
    ▼
Service Layer (OtpService, BusBookingService, SettingsService)
    ▼
Models + Policies (authorization)
    ▼
Database (MySQL) + Cache (Redis)
    ▼
View (Blade) / JSON Response
```

### API Request
```
HTTP Request + Accept: application/json
    ▼
Sanctum Token Validation (if authenticated)
    ▼
Controller
    ▼
Service Layer
    ▼
Models + Policies
    ▼
JSON Response
```

---

## 5. Authentication & Authorization

### Authentication Flows

| Flow | Entry Point | Steps | Output |
|------|-------------|-------|--------|
| **Web Login** | `GET /login` | 1. Contact → 2. OTP verify → Dashboard | Session cookie |
| **Web Register (Customer)** | `GET /register/customer` | 1. Contact → 2. OTP verify → 3. Complete profile → Dashboard | Session cookie |
| **Web Register (Provider)** | `GET /register/provider` | 1. Form → 2. Submit → Pending review | Session cookie (on approval) |
| **Web Register (Agency)** | `GET /register/agency` | 1. Form → 2. Submit → Pending review | Session cookie (on approval) |
| **Admin Login** | `GET /admin/login` | 1. Credentials → 2. Role check → Dashboard | Session cookie |
| **API Login** | `POST /api/v1/auth/login/otp` | 1. Request OTP → 2. Verify OTP | Sanctum token |
| **API Register** | `POST /api/v1/auth/register/complete` | 1. Request OTP → 2. Verify OTP → 3. Complete | Sanctum token |
| **Firebase Mobile Login** | `POST /api/v1/auth/firebase/login` | 1. Firebase ID token → 2. Verify → 3. Resolve user → 4. Issue Sanctum token | Sanctum token |
| **Firebase Account Link** | `POST /api/v1/auth/firebase/link` | 1. Firebase ID token → 2. OTP verify → 3. Link firebase_uid → 4. Issue Sanctum token | Sanctum token |
| **Password Reset** | `GET /forgot-password` | Email → Token → New password | Session/Email |

### Firebase Authentication Architecture

**Mobile (Expo/React Native):**
- Client authenticates via Firebase Authentication SDK
- Firebase issues an ID token (JWT)
- Client sends ID token as Bearer token to Laravel API
- Laravel verifies token via `FirebaseAuthService` + `VerifyFirebaseIdToken` middleware
- Firebase UID mapped to local `User` record via `users.firebase_uid` column

**Web:**
- Continues using Laravel session-based authentication
- OTP login/register flows unchanged
- Firebase authentication NOT used for web

**Authorization:**
- Laravel remains the authority for roles, permissions, and business operations
- Firebase authentication does NOT replace Laravel authorization
- Client-supplied role/permission claims are never trusted
- Spatie Permission continues to manage roles and permissions

### Guards

| Guard | Driver | Provider | Purpose |
|-------|--------|----------|---------|
| `web` | session | users | Web session authentication |
| `firebase` | session | users | Documents Firebase mobile auth boundary (verification via middleware) |


---

## 6. Database Architecture

### Design Principles

1. **MySQL is Authoritative** — All business data lives in MySQL; Firebase is supplementary only
2. **Integer Minor Units** — All monetary amounts stored as `unsignedBigInteger` (minor units) + `char(3) currency` (default `XAF`)
3. **Row-Level Locking** — Critical operations (seat holds, bookings) use `SELECT ... FOR UPDATE`
4. **Soft Deletes Avoided** — Explicit status columns (`status`, `is_active`) instead
5. **Audit Trail** — `audit_logs` table + `Auditable` trait on key models
6. **No Duplicate Entities** — Single `users`, `agencies`, `branches`, `bus_*` tables

### Key Relationships

```
User (1) ──────< (M) DeviceToken
User (1) ──────< (M) OtpCode (via destination_hash)
User (1) ──────< (M) BusBooking (customer_id)
User (1) ──────< (M) BusBooking (sold_by_user_id)
User (1) ──────< (M) BusTicket (scanned_by)
User (1) ──────< (M) BusTicketEvent (actor_user_id)

Agency (1) ──────< (M) Branch
Agency (1) ──────< (M) BusTrip
Agency (1) ──────< (M) Bus (fleet)
Agency (1) ──────< (M) Crew
Agency (1) ──────< (M) SeatLayout
Agency (1) ──────< (M) BusRoute

Branch (1) ──────< (M) BusRouteStop
Branch (1) ──────< (M) BusBooking (branch_id)
Branch (1) ──────< (M) BusTicket (branch_id)

City (1) ──────< (M) Agency
City (1) ──────< (M) Branch
City (1) ──────< (M) BusRouteStop

BusRoute (1) ──────< (M) BusRouteStop
BusRoute (1) ──────< (M) BusRouteFare
BusRoute (1) ──────< (M) BusSchedule
BusRoute (1) ──────< (M) BusTrip

BusTrip (1) ──────< (M) BusTripSeat
BusTrip (1) ──────< (M) BusTicket

BusBooking (1) ──────< (M) BusTicket
BusBooking (1) ──────< (M) BusTripSeat (via booking_id)

BusTicket (1) ──────< (M) BusTicketEvent
```

---

## 7. Module Boundaries

| Module | Namespace | Tables | Public API | Admin UI | Dependencies |
|--------|-----------|--------|------------|----------|--------------|
| **Core** | `App\` | 6 | Auth, Health | Dashboard, Settings | — |
| **RBAC** | `Spatie\Permission` | 5 | — | Users, Roles | Core |
| **Agency** | `App\Models\` | 3 | — | Agencies, Branches, Staff | Core, City |
| **Bus** | `App\Modules\Bus\` | 17 | Discovery, Holds | Routes, Trips, Inventory, Tickets, Bookings | Agency, Branch, City, BusClass |
| **Hotel** | `App\Http\Controllers\Admin\` | 4+ | — | Hotels, Rooms, Rates, Bookings | Agency |
| **eSIM** | `App\Http\Controllers\Admin\` | 3 | — | Providers, Plans, Orders | — |
| **Boxo** | `App\Http\Controllers\Admin\` | 1 | — | Miniapps | — |
| **FX** | `App\Http\Controllers\Admin\` | 3+ | Currency/Lang | Currencies, Rates, Margins | Core |
| **Menu** | `App\Models\` | 2 | — | — | Core |

---

## 8. Security Architecture

### Defense Layers

1. **Network** — Cloudflare proxy (WAF, DDoS, TLS)
2. **IP Resolution** — `TrustCloudflareProxies` middleware validates CF-Connecting-IP against Cloudflare IP ranges
3. **Rate Limiting** — `RateLimitMiddleware` + Laravel RateLimiter (configurable per route)
4. **Authentication** — OTP (web login), Sanctum tokens (API), Firebase ID-token verification (mobile)
5. **Authorization** — Spatie permissions + custom policies (ownership scoping)
6. **Input Validation** — Form requests on all mutating endpoints
7. **SQL Injection** — Eloquent ORM + parameter binding
8. **XSS** — Blade `{{ }}` escaping + CSP headers
9. **CSRF** — Laravel CSRF tokens on all web forms
9. **Audit** — Immutable `audit_logs` on all sensitive operations

### Firebase Security

- **Firestore Rules:** Deny-all by default; no business data stored
- **Storage Rules:** User avatars only (`users/{uid}/avatars/*`); all else denied
- **Authentication:** Firebase Admin SDK (`kreait/firebase-php`) verifies ID tokens from mobile; no client SDK in web
- **ID-token verification:** `FirebaseAuthService` verifies signature, issuer, audience, expiration, and required claims
- **Token trust:** Never decodes JWT and treats payload as proof; always uses SDK verification
- **Environments:** Separate projects for development, staging, production via `.env`
- **Account linking:** `firebase_uid` column on `users` table with unique constraint; OTP-based linking flow

---

## 9. Asynchronous Processing

### Queue System (Redis)

| Job | Trigger | Purpose |
|-----|---------|---------|
| `ExpireBusSeatHolds` | Scheduled (daily 02:00) | Release expired seat holds |
| `SendOtpNotification` | OTP request | Deliver via Log/Mailgun/Twilio/Meta |
| `AuditLog` | Model events | Async audit trail (via `Auditable` trait) |

### Scheduled Commands (`routes/console.php`)

```php
Schedule::command('expire:bus-seat-holds')->dailyAt('02:00');
Schedule::command('translations:check')->weekly();
```

---

## 10. Frontend Architecture

### Web (Blade + Bootstrap 5)

- **Layouts:** `public.blade.php` (marketing), `app.blade.php` (authenticated)
- **Components:** Partial-based (no component library yet) + reusable Blade components (button, card, form-field)
- **CSS:** `resources/css/app.css` (Sneat Pro), `resources/css/landing.css` (marketing tokens), `resources/css/design-tokens.css` (design token bridge)
- **JS:** `resources/js/app.js` (Bootstrap init, currency/lang switchers)
- **Build:** Vite + Laravel Vite Plugin + Tailwind v4

### Mobile (Expo / React Native) — Implemented (Auth Boundary)

- **API Contract:** `/api/v1` (same as web)
- **Auth:** Firebase Auth → ID token → Laravel verification → Sanctum token (implemented)
- **Routes:** `POST /api/v1/auth/firebase/login`, `POST /api/v1/auth/firebase/link`
- **Push:** FCM via `device_tokens` table (planned)
- **Offline:** Local-first with sync queue (future)

---

## 11. Deployment Architecture

### Environments

| Environment | Branch | Database | Firebase | Purpose |
|-------------|--------|----------|----------|---------|
| **Local** | feature/* | `eatalyft_local` | Emulator | Development |
| **Testing** | CI | `eatalyft_testing` | Emulator | Automated tests |
| **Staging** | `staging` | Managed MySQL | Staging project | Pre-production |
| **Production** | `main` | Managed MySQL | Production project | Live |

### CI/CD Pipeline (Conceptual)

```
Push → GitHub Actions
    ▼
Lint (Pint) + Static Analysis
    ▼
Unit/Feature Tests (PHPUnit, SQLite/MySQL)
    ▼
Build Assets (Vite)
    ▼
Deploy to Environment
    ▼
Run Migrations (non-destructive)
    ▼
Health Check
    ▼
Smoke Tests
```

---

## 12. Observability

### Logging

- **Channels:** `single`, `daily`, `stack`, `otp` (dedicated OTP channel)
- **Format:** Structured JSON in production
- **Levels:** `debug` (local), `info`/`warning`/`error` (production)

### Health Endpoint (`/health`, `/api/v1/health`)

| Check | Public | Authenticated (system.health) |
|-------|--------|------------------------------|
| Database connectivity | ✅ | ✅ |
| Redis connectivity | ❌ | ✅ |
| Queue workers | ❌ | ✅ |
| Disk space | ❌ | ✅ |
| Firebase config | ❌ | ✅ |

### Metrics (Future)

- Laravel Prometheus exporter
- Custom business metrics (bookings, revenue, active users)

---

## 13. Configuration Management

### Environment Files

| File | Purpose | Committed? |
|------|---------|------------|
| `.env.example` | Template with all keys | ✅ |
| `.env` | Local overrides | ❌ (gitignored) |
| `.env.testing` | Test overrides | ❌ (gitignored) |

### Key Configuration Files

| File | Scope |
|------|-------|
| `config/app.php` | Application basics |
| `config/auth.php` | Guards, providers, passwords |
| `config/sanctum.php` | Token expiration, prefixes |
| `config/permission.php` | Spatie RBAC |
| `config/otp.php` | OTP behavior |
| `config/firebase.php` | Firebase credentials |
| `config/currencies.php` | Supported currencies |
| `config/menu.php` | Navigation structure |
| `config/logging.php` | Log channels |

---

## 14. Extensibility Points

| Extension Point | Mechanism | Example Use |
|-----------------|-----------|-------------|
| **New OTP Channel** | Implement `OtpSender` interface | WhatsApp Business API |
| **New Payment Gateway** | Implement `PaymentGateway` interface | Stripe, Flutterwave |
| **New Module** | `app/Modules/{Module}/` + ServiceProvider | Handyman, Flights |
| **New Permission** | Spatie seeder + policy | `handyman.bookings.read` |
| **New Menu Item** | `config/menu.php` + migration | Custom admin links |
| **New Setting** | `AppSetting` model + seeder | Feature flags |

---

## 15. Architectural Decision Records (ADRs)

| ADR | Title | Status | Date |
|-----|-------|--------|------|
| 001 | MySQL as Authoritative Store | Accepted | 2026-11-20 |
| 002 | OTP-Based Authentication (No Passwords) | Accepted | 2026-11-20 |
| 003 | Spatie Laravel Permission for RBAC | Accepted | 2026-11-20 |
| 004 | Modular Bus Domain in `app/Modules/Bus` | Accepted | 2026-11-21 |
| 005 | Firebase for Auth/Push Only (No Firestore Business Data) | Accepted | 2026-11-21 |
| 006 | Integer Minor Units for All Money | Accepted | 2026-11-21 |
| 007 | Row-Level Locking for Seat Inventory | Accepted | 2026-11-21 |
| 008 | PSR-4 Compliance for All Classes | Accepted | 2026-11-21 |
| 009 | Design Token Bridge (Bootstrap ↔ Tailwind) | Accepted | 2026-11-22 |
| 010 | Firebase ID-Token Verification for Mobile Auth | Accepted | 2026-11-22 |

---

*This document is the single authoritative architecture reference. Update it whenever architecture, schema, routes, or module boundaries change.*