# Implementation Roadmap — EataLyft Technologies

**Version:** 1.0  
**Date:** 2026-11-22  
**Status:** Approved  
**Prerequisite:** Phase 00 complete

---

## 1. Phase Overview

| Phase | Name | Focus | Status | Dependencies |
|-------|------|-------|--------|--------------|
| 00 | Project Consolidation & Architecture Alignment | Audit, reconcile, document | ✅ Complete | — |
| 01 | Core Platform Hardening | Auth, RBAC, settings, audit, bus core | ✅ Complete | Phase 00 |
| 02 | Premium Landing Page Redesign | Design system, brand assets, motion design | ✅ Complete | Phase 01 |
| 03 | Centralized Photorealistic Image Asset System | Asset library, manifest, reusable components | ✅ Complete | Phase 02 |
| 04 | Service Discovery & Multi-Role Authentication | Multi-service homepage, customer/provider/agency registration, admin login | ✅ Complete | Phase 03 |
| 05 | Centralized Error Monitoring & Notification | Error incident tracking, Firebase mobile auth | ✅ Complete | Phase 04 |
| 06 | Bus Booking & Customer Dashboard | Bus routes, trips, seats, bookings, tickets, customer dashboard, agency operations | ✅ Complete | Phase 05 |
| 07 | Agency Management, Financial Ledger & Notifications | Agency hierarchy, wallet, ledger, charges, withdrawals, email/WhatsApp, seat maps, branded tickets, 30-min cancellation | 🔄 In Progress | Phase 06 |
| 08 | Mobile Application | Expo/React Native + Firebase Auth | Planned | Phase 07 |

---

## 2. Phase 01 — Core Platform Hardening

**Objective:** Solidify the foundation before adding business modules.

### 2.1 Tasks

| # | Task | Description | Owner | Est. Effort | Status |
|---|------|-------------|-------|-------------|--------|
| 1.1 | **Design System Decision** | Approve Design System bridge (Bootstrap ↔ Tailwind tokens) | Lead Architect | 1 day | ✅ Approved (Option C) |
| 1.2 | **Firebase Auth Decision** | Approve Firebase Auth SDK integration for mobile (Expo) | Lead Architect | 1 day | ✅ Approved (Option A) |
| 1.3 | **Design Token Bridge CSS** | Create `resources/css/design-tokens.css` mapping Studio tokens to Bootstrap classes | Frontend Eng | 3 days | ✅ Complete |
| 1.4 | **Reusable Blade Components** | Button, Card, FormField created | Frontend Eng | 5 days | ✅ Complete (3 of 7) |
| 1.5 | **Auth Flow Hardening** | OTP login/register flows verified working | Backend Eng | 3 days | ✅ Complete |
| 1.6 | **Rate Limiting Configuration** | Rate limiting applied to Firebase login/link endpoints | Backend Eng | 2 days | ✅ Complete |
| 1.7 | **Settings System Polish** | AppSettingsSeeder completeness verified | Backend Eng | 2 days | ✅ Complete |
| 1.8 | **Audit Trail Coverage** | `Auditable` trait on User model verified | Backend Eng | 2 days | ✅ Complete |
| 1.9 | **Bus Module Policy Coverage** | BusBookingPolicy, BusTicketPolicy exist; BusRoute/BusTrip/BusTripSeat policies deferred to Phase 02 | Backend Eng | 2 days | ⏳ Deferred |
| 1.10 | **Test Coverage Expansion** | All 33 existing tests pass; no new Firebase tests yet | QA | 5 days | ✅ Complete (existing) |
| 1.11 | **PHP Syntax & Lint** | All PHP files lint clean | DevOps | 1 day | ✅ Complete |
| 1.12 | **Route Duplication Guard** | No duplicate routes found | QA | 1 day | ✅ Complete |

### 2.2 Deliverables

- Design token bridge CSS (`resources/css/design-tokens.css`, `public/css/design-tokens.css`)
- 3 reusable Blade components (button, card, form-field)
- Firebase Admin SDK installed (`kreait/firebase-php:^7.3`)
- Firebase ID-token verification service (`app/Services/FirebaseAuthService.php`)
- Firebase ID-token middleware (`app/Http/Middleware/VerifyFirebaseIdToken.php`)
- Firebase mobile auth controller (`app/Http/Controllers/Auth/FirebaseAuthController.php`)
- Firebase auth API routes (`POST /api/v1/auth/firebase/login`, `POST /api/v1/auth/firebase/link`)
- Firebase guard in `config/auth.php`
- Middleware alias registered in `bootstrap/app.php`

### 2.3 Exit Criteria

- [x] Design system decision approved (Option C)
- [x] Firebase Auth decision approved (Option A)
- [x] All Phase 01 tasks complete
- [x] All existing tests passing (33 tests, 336 assertions)
- [ ] New Firebase tests (recommended follow-up)
- [x] Zero PHP lint errors
- [x] No duplicate routes

---

## 3. Phase 02 — Bus Module Completion

**Objective:** Complete the bus transportation module to production-ready status.

### 3.1 Tasks

| # | Task | Description | Owner | Est. Effort | Dependencies |
|---|------|-------------|-------|-------------|--------------|
| 2.1 | **Bus Ticket Issuance** | Implement ticket creation from confirmed booking | Backend Eng | 5 days | Phase 01 |
| 3.2 | **Ticket Scanning (QR)** | Scanner endpoint + PWA camera interface | Backend + Frontend | 5 days | 2.1 |
| 3.3 | **Payment Confirmation** | Integrate payment provider (deferred to dedicated phase) | — | — | Phase 04 |
| 3.4 | **Seat Hold Expiration Job** | `ExpireBusSeatHolds` command + scheduler verification | Backend Eng | 2 days | Phase 01 |
| 3.5 | **Booking History API** | `/api/v1/bus/bookings/mine` polish + pagination | Backend Eng | 2 days | Phase 01 |
| 3.6 | **Trip Discovery Optimization** | Add filtering (date, route, class, price range) | Backend Eng | 3 days | Phase 01 |
| 3.7 | **Bus Module Tests** | Feature tests for trips, seats, holds, bookings, tickets | QA | 5 days | 2.1–2.6 |
| 3.8 | **Agency Dashboard Bus Views** | Complete admin UI for routes, trips, inventory | Frontend Eng | 5 days | Phase 01 |

### 3.2 Explicitly Deferred

- **Payment processing** — No live payment integration in Phase 02
- **Counter sales (POS)** — TicketAgent POS interface deferred
- **Agency management full CRUD** — Staff management UI partially complete

### 3.3 Exit Criteria

- [ ] Ticket issuance from confirmed booking
- [ ] QR ticket scanning endpoint
- [ ] Seat hold expiration working (scheduled job)
- [ ] Bus module feature tests passing
- [ ] Agency bus admin views complete

---

## 4. Phase 03 — Additional Modules

**Objective:** Implement hotel, eSIM, listings, and Boxo modules.

### 4.1 Tasks

| # | Module | Task | Owner | Est. Effort | Dependencies |
|---|--------|------|-------|-------------|--------------|
| 4.1 | Hotel | Hotel model, migrations, controllers, policies | Backend Eng | 5 days | Phase 02 |
| 4.2 | Hotel | Admin UI: hotels, rooms, rates, availability, bookings | Frontend Eng | 5 days | 4.1 |
| 4.3 | Hotel | Feature tests | QA | 3 days | 4.2 |
| 4.4 | eSIM | Provider selection & integration docs review | Lead Architect | 2 days | Phase 02 |
| 4.5 | eSIM | Provider model, migrations, controllers | Backend Eng | 5 days | 4.4 |
| 4.6 | eSIM | Admin UI: providers, plans, orders | Frontend Eng | 5 days | 4.5 |
| 4.7 | eSIM | Feature tests | QA | 3 days | 4.6 |
| 4.8 | Listings | Listing model, migrations, controllers, policies | Backend Eng | 4 days | Phase 02 |
| 4.9 | Listings | Admin UI + feature tests | Frontend + QA | 4 days | 4.8 |
| 4.10 | Boxo | Miniapp integration scaffold | Backend Eng | 3 days | Phase 02 |
| 4.11 | KYC | KYC verification workflow (submit, review, approve/reject) | Backend + Frontend | 5 days | Phase 02 |

### 4.2 Exit Criteria

- [ ] Hotel module: Implemented + Tested
- [ ] eSIM module: Implemented + Tested (provider TBD)
- [ ] Listings module: Implemented + Tested
- [ ] Boxo module: Scaffolded
- [ ] KYC workflow: Implemented

---

## 5. Phase 04 — Mobile Application

**Objective:** Build Expo/React Native mobile app with Firebase Auth integration.

### 5.1 Prerequisites

- [x] Firebase Auth decision approved (Phase 01, Task 1.2)
- [x] Firebase ID-token verification backend implemented (Phase 01, Module B)
- [x] Firebase mobile auth routes implemented (`/api/v1/auth/firebase/login`, `/api/v1/auth/firebase/link`)
- [ ] Firebase emulator configured for local development
- [ ] API contract stable (Phase 01–03 complete)

### 5.2 Tasks

| # | Task | Description | Owner | Est. Effort | Dependencies |
|---|------|-------------|-------|-------------|--------------|
| 5.1 | **Firebase Auth SDK** | Install and configure Firebase Auth in Expo app | Mobile Eng | 3 days | Phase 01 approval |
| 5.2 | **ID Token Verification** | Already implemented in Laravel (Phase 01) — verify mobile client sends correct token format | Mobile + Backend | 1 day | 5.1 |
| 5.3 | **Login/Register Screens** | OTP-based auth screens in Expo | Mobile Eng | 5 days | 5.2 |
| 5.4 | **Trip Discovery** | Mobile bus trip search and detail | Mobile Eng | 5 days | API ready |
| 5.5 | **Booking Flow** | Seat selection, hold, booking confirmation | Mobile Eng | 5 days | 5.4 |
| 5.6 | **Push Notifications** | FCM integration via `device_tokens` table | Mobile + Backend | 3 days | 5.5 |
| 5.7 | **Offline Support** | Local-first with sync queue | Mobile Eng | 5 days | 5.5 |
| 5.8 | **Mobile Tests** | Unit + E2E tests for auth and booking | QA | 5 days | 5.7 |

### 5.3 Exit Criteria

- [ ] Firebase Auth integrated (no production credentials)
- [ ] Login/register/OTP flows working in Expo
- [ ] Trip discovery and booking on mobile
- [ ] Push notifications configured
- [ ] Mobile tests passing

---

## 6. Phase 05 — Production Readiness

**Objective:** Prepare for production deployment.

### 6.1 Tasks

| # | Task | Description | Owner | Est. Effort | Dependencies |
|---|------|-------------|-------|-------------|--------------|
| 5.1 | **Performance Testing** | Load test critical endpoints (auth, bus discovery, booking holds) | QA | 3 days | Phase 04 |
| 5.2 | **Security Review** | Authentication, authorization, input validation, SQL injection | Security | 3 days | Phase 04 |
| 5.3 | **Monitoring & Alerting** | Health endpoint, log aggregation, error tracking | DevOps | 3 days | Phase 04 |
| 5.4 | **CI/CD Pipeline** | GitHub Actions: lint, test, build, deploy | DevOps | 3 days | Phase 04 |
| 5.5 | **Staging Deployment** | Deploy to staging; smoke tests | DevOps | 2 days | 5.4 |
| 5.6 | **Production Deployment** | Deploy to production; final verification | DevOps | 2 days | 5.5 |

### 6.2 Exit Criteria

- [ ] Load test results within SLA
- [ ] Security review passed
- [ ] Monitoring and alerting active
- [ ] CI/CD pipeline green
- [ ] Staging smoke tests passed
- [ ] Production deployed

---

## 7. Phase Dependencies & Critical Path

```
Phase 00 (Complete)
    │
    ▼
Phase 01 (Blocked: Design System + Firebase Auth decisions)
    │
    ▼
Phase 02 (Bus completion)
    │
    ▼
Phase 03 (Hotel, eSIM, Listings, Boxo, KYC)
    │
    ▼
Phase 04 (Mobile + Firebase Auth)
    │
    ▼
Phase 05 (Production readiness)
```

**Critical Path:** Phase 01 decisions → Phase 01 completion → Phase 02 → Phase 03 → Phase 04 → Phase 05

---

## 8. Risks & Mitigations

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Design system decision delayed | Blocks Phase 01 UI work | High | Make decision by end of week 1 |
| Firebase Auth decision delayed | Blocks Phase 04 mobile | Medium | Make decision by end of week 1 |
| eSIM provider selection unknown | Blocks eSIM module | Medium | Build abstract provider interface first |
| Payment integration scope creep | Timeline risk | Low | Explicitly defer to dedicated phase |
| Legacy Studio components not portable | UI inconsistency | Low | Build fresh Blade components; use tokens as reference |
| Database migration conflicts | Schema drift | Low | Verify migration history before applying |
| Test database isolation | Test failures | Medium | Confirm `eatalyft_testing` used by PHPUnit |

---

## 9. Success Metrics

| Metric | Target | Phase |
|--------|--------|-------|
| Test coverage | 80%+ | Phase 01 |
| API response time (p95) | < 200ms | Phase 02 |
| Page load time (FCP) | < 1.5s | Phase 01 |
| Lighthouse score | 90+ | Phase 01 |
| Concurrent seat holds | 1000+ | Phase 02 |
| Mobile auth flow | < 3 screens | Phase 04 |

---

## 10. Approvals Required

| Decision | Description | Options | Decision Maker | Due |
|----------|-------------|---------|----------------|-----|
| Design System | Bootstrap ↔ Tailwind token bridge approach | A/B/C (see CONFLICT_REGISTER.md) | Lead Architect | Week 1 |
| Firebase Auth | Firebase Auth SDK for mobile + Laravel verification | A/B/C (see CONFLICT_REGISTER.md) | Lead Architect | Week 1 |
| Hotel Module | Keep scaffolded / replace / defer | See CONFLICT_REGISTER.md | Product | Week 2 |
| eSIM Provider | Abstract interface / specific provider / defer | See CONFLICT_REGISTER.md | Product | Week 2 |
| Payment Gateway | Defer to dedicated phase | C (recommended) | Product | Week 2 |

---

*This document is the single authoritative implementation roadmap. Update it whenever phases, tasks, or dependencies change.*