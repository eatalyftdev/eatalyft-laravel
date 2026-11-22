# Conflict Register — EataLyft Technologies

**Version:** 1.0  
**Date:** 2026-11-22  
**Status:** Complete  
**Total Conflicts:** 18 (9 resolved, 9 pending approval)

---

## 1. Resolved Conflicts

| # | Conflict | Description | Resolution | Status |
|---|----------|-------------|------------|--------|
| 1 | `firebase_uid` column lifecycle | Column was removed in migration `2026_11_20_104300`, then needed again for Firebase Auth | Migration `2026_11_22_120000` restores `firebase_uid` with unique nullable constraint | ✅ Resolved |
| 2 | `otp_codes.destination_encrypted` NOT NULL | Log sender doesn't store destination, but column was NOT NULL, causing constraint violation | Migration `2026_11_21_113424` makes `destination_encrypted` nullable | ✅ Resolved |
| 3 | Admin agencies route conflict | Admin `/agencies` conflicted with public `/agencies` route | Admin agencies moved to `/admin/agencies` | ✅ Resolved |
| 4 | PSR-4 autoloading violation | `OtpSenders.php` contained multiple classes in one file | Split into 5 separate files under `app/Services/Otp/` | ✅ Resolved |
| 5 | OTP config first-test failure | `Config::set('otp.show_code_on_screen', true)` failed for first test alphabetically | Config modified to return true in `testing` env; test sets `max_attempts` first | ✅ Resolved |
| 6 | API `/me` returns 302 instead of 401 | Sanctum middleware redirected unauthenticated API requests to login page | GuestAccessTest fixed to send `Accept: application/json` header | ✅ Resolved |
| 7 | Blade `{{var}}` placeholder conflict | `__()` strings used `{{var}}` syntax conflicting with Blade curly braces | All replaced with `:var` syntax | ✅ Resolved |
| 8 | `HotelBookingController` debug code | `getGuardNames()` debug call caused 500 error | Removed debug call | ✅ Resolved |
| 9 | `config/menu.php` incorrect route names | Route names like `kyc.show→kyc.index`, `my.*→index`, `scanner.scan→dashboard`, `creator.*→dashboard` incorrect | Corrected all route names in menu config | ✅ Resolved |

---

## 2. Pending Approvals — Requiring Decision

| # | Conflict | Description | Options | Recommendation | Decision Maker | Due |
|---|----------|-------------|---------|----------------|----------------|-----|
| 10 | **Design System Unification** | Laravel uses Bootstrap 5 (Sneat Pro); Studio uses Tailwind v4 + shadcn/ui. No shared design token file exists. | **A)** Keep Bootstrap/Sneat Pro for admin; port Studio Tailwind tokens to public pages only<br>**B)** Full migration to Tailwind v4 + shadcn/ui patterns<br>**C)** Create token bridge (CSS variables mapping both systems) | **C** — Bridge allows gradual migration; Studio tokens are more comprehensive | Lead Architect | Week 1 |
| 11 | **Firebase Auth Integration** | Mobile (Expo) needs Firebase Auth. Laravel needs to verify Firebase ID tokens. | **A)** Implement Firebase Auth SDK for mobile + token verification in Laravel<br>**B)** Keep Sanctum for all; Firebase only for push notifications<br>**C)** Hybrid: Firebase Auth for mobile, Sanctum for web | **A** — Required for Expo mobile; Laravel verifies ID tokens | Lead Architect | Week 1 |
| 12 | **Hotel Module Fate** | Hotel module is scaffolded (routes, views, policies) but has no models, migrations, or business logic. | **A)** Keep current scaffolded implementation<br>**B)** Replace with new normalized schema matching bus patterns<br>**C)** Defer entirely to post-Phase-01 | **C** — Not in active scope; preserve schema only | Product | Week 2 |
| 13 | **eSIM Provider Integration** | eSIM module scaffolded but no provider selected. Provider details UNKNOWN. | **A)** Implement specific provider (Truphone, GigSky, etc.)<br>**B)** Build abstract provider interface first<br>**C)** Defer to dedicated phase | **B** — Need provider docs first | Product | Week 2 |
| 14 | **Payment Gateway** | Payment integration explicitly deferred per scope, but bus module needs payment confirmation eventually. | **A)** Stripe + local (Mobile Money, Card)<br>**B)** Flutterwave (African focus)<br>**C)** Abstract gateway interface; decide later | **C** — Explicitly deferred per scope | Product | Week 2 |
| 15 | **Legacy Studio Components Migration** | Studio has rich React components (Hero, Bento, RateCalculator, etc.) that could be ported. | **A)** Port all marketing components<br>**B)** Only port design tokens; rebuild Blade components<br>**C)** Keep Studio as reference; build fresh in Laravel | **B** — Blade components needed; Studio React components not directly portable | Lead Architect | Week 2 |
| 16 | **Duplicate API User Endpoints** | `/api/v1/user` (closure) and `/api/v1/auth/me` (AuthController) return same data. | **A)** Remove `/api/v1/user`<br>**B)** Keep both as alias<br>**C)** Redirect `/api/v1/user` to `/api/v1/auth/me` | **B** — Keep as alias for backward compatibility | Lead Architect | Week 1 |
| 17 | **Test Database Isolation** | PHPUnit uses `eatalyft_testing` but `.env.example` shows `eatalyft_dev`. Risk of running tests against dev database. | **A)** Keep current guard in TestCase.php<br>**B)** Add CI check to fail if testing against dev DB<br>**C)** Both A and B | **C** — Defense in depth | DevOps | Week 1 |
| 18 | **Bus Module Policy Coverage** | Only BusBookingPolicy and BusTicketPolicy exist. BusRoute, BusTrip, BusTripSeat lack policies. | **A)** Add policies for all bus models<br>**B)** Keep current policies; add later<br>**C)** Use middleware-only authorization | **A** — Consistent with RBAC architecture | Backend Eng | Week 2 |

---

## 3. Conflict Details

### Conflict 10: Design System Unification

**Context:**
- Laravel views use Bootstrap 5 (Sneat Pro template) with `resources/css/app.css`
- Studio project uses Tailwind v4 with comprehensive CSS variable design system in `globals.css`
- No shared token file exists between the two

**Impact:**
- Frontend inconsistency between public and admin pages
- Duplicate design decisions
- Migration friction when porting Studio marketing sections to Laravel

**Options:**

| Option | Pros | Cons |
|--------|------|------|
| **A** — Bootstrap admin + Tailwind public | Leverages existing work; clear separation | Two CSS systems to maintain |
| **B** — Full Tailwind v4 migration | Single system; matches Studio | Rewrite all Blade views; large effort |
| **C** — Token bridge | Gradual migration; single source of truth for tokens | Initial bridge development effort |

**Recommendation: C** — Create `resources/css/design-tokens.css` mapping Studio tokens to Bootstrap-compatible utility classes. Allows gradual migration without duplicating frameworks.

**Decision Required From:** Lead Architect  
**Due:** Week 1

---

### Conflict 11: Firebase Auth Integration

**Context:**
- Mobile (Expo) app needs authentication
- Laravel has Sanctum for API token auth
- Firebase config is scaffolded but not integrated
- `firebase_uid` column restored on `users` table

**Impact:**
- Without decision, mobile auth cannot proceed
- Firebase emulator configured but unused
- `device_tokens` table exists but no push service

**Options:**

| Option | Pros | Cons |
|--------|------|------|
| **A** — Firebase Auth mobile + Laravel verification | Proper mobile auth; Laravel verifies ID tokens | Additional integration complexity |
| **B** — Sanctum only; Firebase for push only | Simpler; existing infrastructure | Poor mobile UX (no native auth) |
| **C** — Hybrid: Firebase mobile, Sanctum web | Clear separation | Two auth systems to maintain |

**Recommendation: A** — Implement Firebase Auth SDK for mobile (Expo), with Laravel verifying Firebase ID tokens via custom middleware. Sanctum remains for web API.

**Decision Required From:** Lead Architect  
**Due:** Week 1

---

### Conflict 12: Hotel Module Fate

**Context:**
- Hotel module has routes, views, and policies but no models, migrations, or business logic
- Hotel system excluded from active product scope (Phase 00 focus is bus)
- Existing hotel implementation needs inspection before integration decision

**Impact:**
- Scaffolded hotel code creates maintenance burden
- Unclear whether to invest in hotel or defer

**Options:**

| Option | Pros | Cons |
|--------|------|------|
| **A** — Keep scaffolded | No work now; code available | Maintenance burden; incomplete module |
| **B** — Replace with bus-pattern schema | Consistent architecture | Significant development effort |
| **C** — Defer to post-Phase-01 | Focus on bus module | Hotel functionality unavailable |

**Recommendation: C** — Defer hotel module entirely. Keep scaffolded routes/views as placeholders. Decide hotel fate after bus module is production-ready.

**Decision Required From:** Product  
**Due:** Week 2

---

### Conflict 13: eSIM Provider Integration

**Context:**
- eSIM module scaffolded with routes, views, controllers
- No eSIM provider selected; provider details UNKNOWN
- No provider documentation available

**Impact:**
- Cannot implement eSIM without provider
- Scaffolded code is incomplete

**Options:**

| Option | Pros | Cons |
|--------|------|------|
| **A** — Specific provider | Immediate implementation | Locked to one provider; docs needed |
| **B** — Abstract provider interface | Flexible; provider-agnostic | More upfront design work |
| **C** — Defer | Focus on core modules | eSIM unavailable |

**Recommendation: B** — Build abstract provider interface first. When provider docs become available, implement concrete provider.

**Decision Required From:** Product  
**Due:** Week 2

---

### Conflict 14: Payment Gateway

**Context:**
- Payment integration explicitly deferred per product scope
- Bus module needs payment confirmation for ticket issuance
- No payment provider selected

**Impact:**
- Bus bookings can be "held" but not "confirmed" (payment)
- Ticket issuance blocked without payment

**Options:**

| Option | Pros | Cons |
|--------|------|------|
| **A** — Stripe + local | Well-documented; African payment methods | Integration effort |
| **B** — Flutterwave | African-focused; mobile money | Regional coverage |
| **C** — Abstract interface + defer | Flexible; no commitment | Payment confirmation delayed |

**Recommendation: C** — Create abstract `PaymentGateway` interface. Implement when payment phase is approved.

**Decision Required From:** Product  
**Due:** Week 2

---

### Conflict 15: Legacy Studio Components Migration

**Context:**
- Studio project has rich React components (Hero, Bento, RateCalculator, SiteFooter, etc.)
- Laravel needs Blade components for marketing pages
- React components not directly portable to Blade

**Impact:**
- Marketing pages need to be rebuilt in Blade
- Design inspiration available from Studio

**Options:**

| Option | Pros | Cons |
|--------|------|------|
| **A** — Port all components | Maximum Studio reuse | High effort; React→Blade conversion |
| **B** — Port tokens only; rebuild Blade | Clean Blade components; consistent design | Rebuild effort |
| **C** — Keep Studio as reference | No porting effort | Design drift between Studio and Laravel |

**Recommendation: B** — Port design tokens (colors, spacing, typography) to `design-tokens.css`. Rebuild Blade components using those tokens.

**Decision Required From:** Lead Architect  
**Due:** Week 2

---

### Conflict 16: Duplicate API User Endpoints

**Context:**
- `/api/v1/user` returns `$request->user()` via closure
- `/api/v1/auth/me` returns `AuthController@me`
- Both return the same authenticated user data

**Impact:**
- Minor duplication; no functional conflict
- API inconsistency

**Options:**

| Option | Pros | Cons |
|--------|------|------|
| **A** — Remove `/api/v1/user` | Cleaner API | Breaking change for any clients using it |
| **B** — Keep both as alias | Backward compatible | Minor duplication |
| **C** — Redirect `/api/v1/user` → `/api/v1/auth/me` | Single source of truth | Extra redirect |

**Recommendation: B** — Keep both. `/api/v1/user` is an alias for backward compatibility.

**Decision Required From:** Lead Architect  
**Due:** Week 1

---

### Conflict 17: Test Database Isolation

**Context:**
- PHPUnit configured for `eatalyft_testing` database
- `.env.example` shows `eatalyft_dev` as default
- `TestCase.php` has guard against `eatalyft_local`
- Risk of tests running against dev database if env misconfigured

**Impact:**
- Test data could contaminate dev database
- False test results

**Options:**

| Option | Pros | Cons |
|--------|------|------|
| **A** — Keep current guard | Simple; works | Single point of failure |
| **B** — Add CI check | Additional safety | CI complexity |
| **C** — Both A and B | Defense in depth | Slight overhead |

**Recommendation: C** — Keep TestCase.php guard AND add CI check that fails if tests run against non-testing database.

**Decision Required From:** DevOps  
**Due:** Week 1

---

### Conflict 18: Bus Module Policy Coverage

**Context:**
- `BusBookingPolicy` and `BusTicketPolicy` exist
- `BusRoute`, `BusTrip`, `BusTripSeat` lack policies
- Authorization for bus models inconsistent

**Impact:**
- Inconsistent authorization pattern
- Potential security gaps for bus resources

**Options:**

| Option | Pros | Cons |
|--------|------|------|
| **A** — Add policies for all bus models | Consistent architecture; security | Development effort |
| **B** — Keep current; add later | Focus on core flows | Inconsistent patterns |
| **C** — Middleware-only | Simpler | Less granular; harder to audit |

**Recommendation: A** — Add `BusRoutePolicy`, `BusTripPolicy`, `BusTripSeatPolicy` following existing patterns.

**Decision Required From:** Backend Eng  
**Due:** Week 2

---

## 4. Conflict Summary by Category

| Category | Resolved | Pending | Total |
|----------|----------|---------|-------|
| Database Schema | 2 | 0 | 2 |
| Routing | 1 | 1 | 2 |
| Authentication | 1 | 1 | 2 |
| Code Quality | 2 | 0 | 2 |
| Configuration | 2 | 1 | 3 |
| Design System | 0 | 2 | 2 |
| Module Scope | 1 | 3 | 4 |
| Authorization | 0 | 1 | 1 |
| **Total** | **9** | **9** | **18** |

---

## 5. Approval Tracking

| # | Conflict | Decision Required From | Status | Decision | Date |
|---|----------|-----------------------|--------|----------|------|
| 10 | Design System | Lead Architect | ⏳ Pending | — | — |
| 11 | Firebase Auth | Lead Architect | ⏳ Pending | — | — |
| 12 | Hotel Module | Product | ⏳ Pending | — | — |
| 13 | eSIM Provider | Product | ⏳ Pending | — | — |
| 14 | Payment Gateway | Product | ⏳ Pending | — | — |
| 15 | Studio Components | Lead Architect | ⏳ Pending | — | — |
| 16 | Duplicate API | Lead Architect | ⏳ Pending | — | — |
| 17 | Test DB Isolation | DevOps | ⏳ Pending | — | — |
| 18 | Bus Policies | Backend Eng | ⏳ Pending | — | — |

---

*This document is the single authoritative conflict register. Update it whenever conflicts are found, resolved, or decisions are made.*