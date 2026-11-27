# EataLyft P0 Security & Integrity Remediation Plan

**Repository:** eatalyftdev/eatalyft-laravel  
**Base branch:** main  
**Working branch:** fix/p0-security-integrity  
**Baseline commit:** 7205902ebc2eea5234780ac2cbebf50e8736aabe  
**Status:** PLAN RECORDED — IMPLEMENTATION NOT YET VERIFIED

## Guardrails

- Keep `main` unchanged; all work belongs on the dedicated branch.
- Preserve the Phase 00 modular-monolith architecture and existing Laravel 12, Blade, Bootstrap/Sneat, Vite, Sanctum, Spatie Permission and MySQL boundaries.
- Do not access production credentials or production databases.
- Use disposable MySQL 8.4 for migration and concurrency verification.
- Do not activate live payments or deploy automatically.
- Do not rewrite migrations that may already have been applied in a deployed environment; use forward-only corrective migrations where required.
- Do not claim tests passed unless they have actually been executed and results recorded.

## P0 workstreams

### 1. Authorization
- Inventory every mutating web/API route and its effective middleware.
- Verify controller-level policies and resource ownership checks.
- Replace read-only route protection on write actions with operation-specific authorization.
- Add negative tests for read-only roles, cross-agency access and cross-branch access.
- Preserve intended administrator access and existing role mappings.

### 2. Firebase identity and Sanctum
- Establish one canonical Firebase ID-token verification path.
- Separate first-time account linking from already-linked Firebase login.
- Require proof of ownership for both identities before linking.
- Make UID linking transactional and enforce unique UID ownership.
- Fail closed when token verification or required account-status checks fail.
- Define Sanctum token abilities, revocation and disabled-account behavior.
- Preserve existing web-session authentication and documented mobile API contracts where feasible.

### 3. Bus seat-hold integrity
- Preserve transaction and row-locking protections.
- Make idempotency race-safe and validate customer plus request-payload identity.
- Ensure identical retries return the original booking; conflicting reuse is rejected.
- Make seat transitions, expiry and cancellation atomic.
- Test overlapping concurrent requests against disposable MySQL.
- Never mark a booking paid or confirmed without verified payment settlement.

### 4. Diagnostic script isolation
- Remove or isolate destructive and diagnostic root PHP entry points from deployable artifacts.
- Ensure the web server document root is `public/`.
- Add CI checks for diagnostic entry points, destructive SQL patterns and committed secrets.
- Do not execute destructive scripts.

### 5. Migration and schema integrity
- Verify fresh MySQL 8.4 migration, rollback and re-migration using disposable databases.
- Compare generated schema against a clean-install baseline.
- Verify foreign keys, unique constraints and required indexes, including hold-expiry, booking, ticket, trip/seat and route-stop constraints.
- Reconcile migration history and legacy SQL without rewriting migrations already applied outside this branch.

## Required verification

- PHPUnit and focused feature tests.
- Authorization matrix tests for every write action.
- Firebase invalid/expired/wrong-audience and account-linking tests.
- Sanctum token lifecycle tests.
- MySQL concurrent seat-hold and idempotency tests.
- Fresh-install and migration replay checks on disposable MySQL.
- Static analysis and frontend regression checks where configured.

## Required PR evidence

The implementation PR must include:
1. Changed-file inventory and rationale.
2. Before/after authorization matrix.
3. Firebase/Sanctum contract and test results.
4. Seat-hold concurrency results.
5. Diagnostic-script deployment verification.
6. MySQL migration/schema comparison.
7. CI results and unresolved risks.

## Current execution record

- Dedicated branch created from `main`.
- No application code has been changed in this planning commit.
- No database has been accessed or modified.
- No tests have been run by this planning step.
- Implementation and runtime verification remain pending.
