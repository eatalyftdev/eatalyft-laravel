# EataLyft — AI Engineering Standards & Architecture Rules

> This file governs every AI-assisted task in this repository. All code, migrations, components, and documentation MUST comply with these rules. Violations are not acceptable.

---

## 1. Brand Aesthetic & Zero "AI Slop"

### 1.1 Color Palette (Non-Negotiable)

| Token | Value | Usage |
|-------|-------|-------|
| Primary Crimson Red | `#DC2626` | CTAs, active states, brand accents, QR codes, ticket perforation |
| Deep Ink | `#0A0A0A` | Headings, ticket text, primary body text |
| Crisp White | `#FFFFFF` | Card surfaces, ticket backgrounds, light mode |
| Neutral Slate (Light) | `#F8FAFC` | Page backgrounds, subtle surfaces |
| Neutral Slate (Dark) | `#171717` | Dark mode surfaces, secondary text |

### 1.2 Typography

- **Headings, tickets, codes, monospace data:** `Poppins` (400, 600, 700)
- **UI labels, tables, body text:** `Inter` (400, 500, 600)
- Never use system fonts, generic serif, or decorative display fonts.

### 1.3 STRICTLY FORBIDDEN

- Purple, indigo, or violet gradients of any kind
- Generic glowing neon blobs or orbs
- Cluttered glassmorphism overlays (backdrop-blur panels stacked on panels)
- Cookie-cutter template layouts (centered card on gradient background)
- "AI-generated" placeholder illustrations or stock photo collages
- Excessive shadow stacking or nested rounded containers

### 1.4 Layout Parity (Responsive — All Panels)

Every panel (Customer, Agency Owner, Sub-branch/Teller, Provider, Admin) MUST be responsive:

- **Desktop (≥1024px):** Clean sidebar or topbar navigation, multi-column layouts, data tables
- **Mobile PWA (<768px):** Bottom navigation bar, touch targets ≥ 44px, single-column stack, no horizontal scroll
- **Tablet (768–1023px):** Adaptive — sidebar collapses to hamburger, tables become cards
- **Zero broken z-index overlays:** No modal/dialog stacking conflicts, no fixed elements covering interactive content
- **Touch targets:** Minimum 44×44px on all interactive elements in mobile view

### 1.5 Micro-Interactions (Framer Motion)

- Use `framer-motion` for all purposeful transitions
- Duration: 150ms–250ms spring transitions
- Required patterns:
  - Progressive form unlocking (fields enable sequentially after validation)
  - Animated green SVG checkmark circle on valid OTP verification
  - Tactile seat selection (scale + color transition on tap)
  - Page/route transitions (fade + slide, 200ms)
  - Loading skeletons (not spinners) for data fetching
- Never use jarring instant state changes or unanimated modals

---

## 2. Hybrid Laravel + Inertia React Coexistence (Zero Breakage)

### 2.1 Coexistence Rules

- **Preserve existing Blade views** — all current `resources/views/**/*.blade.php` must remain functional
- **Preserve `resources/js/app.js`** — legacy Laravel routes must never break
- **Inertia React entry:** `resources/js/inertia.tsx` (new, separate from `app.js`)
- **Inertia root view:** `resources/views/inertia.blade.php`
- **Configuration:** `Inertia::setRootView('inertia')` in a service provider or middleware
- **Vite config:** Both entries registered:
  ```js
  input: ['resources/js/app.js', 'resources/js/inertia.tsx']
  ```

### 2.2 When to Use Which

- **Blade:** Existing admin panels, public marketing pages, auth flows (until migrated)
- **Inertia React:** New agency dashboard, bus booking engine, ticket views, seat maps
- **Never** delete or break a working Blade route to add an Inertia page

### 2.3 TypeScript Strictness

- All Inertia components MUST use TypeScript (`.tsx` / `.ts`)
- No `any` types unless explicitly justified with a comment
- Props interfaces must be defined and exported
- Use Zod for runtime validation of form data (match spec schemas exactly)

---

## 3. Fintech & Concurrency Security

### 3.1 Monetary Values

- **ALL monetary values** stored as `BIGINT` integer minor units (XAF integers, USD cents, etc.)
- **ALWAYS** include explicit `currency` ISO code column (`XAF`, `USD`, `EUR`, `GBP`, `JPY`)
- **NEVER** use `FLOAT`, `DOUBLE`, or `DECIMAL` for money
- **NEVER** perform floating-point arithmetic on monetary values
- Rounding: use integer math (`round($value * 100)`) or `bcdiv`/`bcmul` with scale

### 3.2 Concurrency & Atomicity

- Every wallet debit/credit MUST execute inside `DB::transaction()`
- Every bus seat booking MUST use pessimistic locking: `lockForUpdate()` on `bus_trip_seats`
- Idempotency keys required on all financial and booking operations
- Duplicate payment webhooks must NOT create duplicate ledger entries
- Use unique constraints on `(idempotency_key)` or `(provider, payment_reference)` scoped correctly

### 3.3 Security Middleware

- `RateLimiter` on all public auth and OTP endpoints
- CSRF protection on all web forms (Laravel default)
- Sanctum token auth on all API endpoints
- Google reCAPTCHA v3 verification on public auth and OTP endpoints
- Strict `FormRequest` validation on every mutating endpoint
- Never trust client-supplied role, permission, or financial data

### 3.4 Authorization

- Server-side authorization via Laravel Policies and Spatie permissions
- Hiding a button in Blade/React is NOT authorization
- Agency A must never access Agency B's data
- Branch A must never access Branch B's data
- Ticket agents cannot access wallet/ledger/withdrawal endpoints

---

## 4. Source of Truth

### 4.1 Migration Spec

**ALWAYS** cross-reference `d:\Git Projects\studio\LARAVEL_MIGRATION_SPEC.md` before creating:

- Models and their fields
- Enums and their values
- Routes and their paths
- Seat grid layouts (75, 65, 50, 30, 18 seaters)
- Email template structures
- Booking flow logic
- City/neighborhood data

### 4.2 Existing Documentation

Before implementing any feature, read:

- `docs/PROJECT_ARCHITECTURE.md` — architecture overview
- `docs/DATABASE_SCHEMA.md` — current schema
- `docs/IMPLEMENTATION_ROADMAP.md` — phase tracking
- `docs/CONFLICT_REGISTER.md` — known conflicts
- `docs/PHASE_07_AUDIT.md` — Phase 07 audit findings
- `docs/RBAC.md` — roles and permissions

### 4.3 Field Name Parity

All field names, Zod rules, enum values, and seat-grid arrays MUST match the migration spec verbatim. Do not rename, abbreviate, or "improve" field names from the spec.

---

## 5. Database Rules

### 5.1 Migration Safety

- NEVER use `migrate:fresh`, `db:wipe`, or destructive resets
- All migrations must be backward-compatible and incremental
- Use `Schema::hasColumn()` guards for idempotent migrations
- Test migrations against `eatalyft_testing` database only

### 5.2 Table Conventions

- Use `unsignedBigInteger` for all monetary amounts
- Use `char(3)` for currency codes
- Use `immutable_datetime` casts for timestamps
- Use explicit `status` columns instead of soft deletes
- Add indexes on all foreign keys and frequently queried columns
- Use `restrictOnDelete` for critical FKs, `cascadeOnDelete` for owned children

---

## 6. Testing Rules

- Run `php artisan test` after every change
- Never report tests as passing unless they actually passed
- Test database must be `eatalyft_testing` (never `eatalyft_local`)
- All financial operations must have idempotency and concurrency tests
- All authorization must have tenant isolation tests

---

## 7. Code Style

- Laravel Pint (PSR-12 + Laravel standards) for PHP
- ESLint + Prettier for TypeScript/React
- No inline comments unless explaining non-obvious business logic
- No debug statements (`dd()`, `dump()`, `console.log`) in committed code
- Use Form Requests for all validation
- Use service classes for business logic (not controllers)

---

## 8. Git & Deployment

- Never commit, push, or deploy without explicit user authorization
- Never expose secrets or credentials in code or commits
- Preserve existing uncommitted changes — inspect `git status` before and after work
- Commit messages: conventional commits (`feat:`, `fix:`, `docs:`, `chore:`)

## Unslop Workflow (Mandatory for ALL UI)

Every new or modified page, component, modal, ticket, or dashboard MUST undergo the unslop workflow before task completion:
1. Audit: Identify AI-UI tells (generic pills, symmetrical grids, vague copy, over-rounded cards, blobs)
2. Direction: Define aesthetic direction (Swiss-precision transit & fintech for Cameroon)
3. 9 Ordered Passes: spacing, color, icons, copy, typography, layout, details, motion, final
4. Eliminate ALL AI tells before completion

Reference: .claude/skills/unslop/SKILL.md

