# Phase 07 Audit — Agency Management, Branch Operations, Financial Ledger, Ticket Notifications & Dynamic Airline-Style Tickets

**Date:** 2026-11-23
**Project:** `D:\Git Projects\eatalyft-laravel`
**Status:** Audit Complete — Awaiting Implementation
**Baseline:** Phase 00-06 complete

---

## 1. Repository State

- **Current branch:** `cleanup/purge-legacy` (tracking `origin/master`)
- **Remote:** `https://github.com/eatalyftdev/eatalyft-laravel.git`
- **Last commit:** `822a028` — Phase 00-06 complete
- **Uncommitted changes:** None (all changes committed and pushed)

### Git Branches

| Branch | Status |
|--------|--------|
| `cleanup/purge-legacy` (local) | Tracks `origin/master` |
| `master` (local) | Points to `822a028` |
| `main` (remote) | Points to `822a028` |
| `master` (remote) | Points to `822a028` |

---

## 2. Test Suite Reconciliation

### Actual Test Run Results (2026-11-23)

Command: `php artisan test` (MySQL `eatalyft_testing`)

| Suite | Tests | Assertions | Status |
|-------|-------|------------|--------|
| `Tests\Unit\ExampleTest` | 1 | 1 | ✅ Pass |
| `Tests\Feature\ExampleTest` | 2 | 2 | ✅ Pass |
| `Tests\Feature\GuestAccessTest` | 2 | 11 | ✅ Pass |
| `Tests\Feature\OtpTest` | 14 | 56 | ⚠️ 2 failing (web flows) |
| `Tests\Feature\RBAC\DebugTest` | 1 | 10 | ✅ Pass |
| `Tests\Feature\RBAC\RoleAccessTest` | 13 | 257 | ✅ Pass |
| `Tests\Feature\ErrorMonitoringServiceTest` | 8 | 24 | ✅ Pass |
| `Tests\Feature\ErrorMonitoringControllerTest` | 8 | 28 | ❌ Multiple failing (bcrypt) |
| `Tests\Feature\AdminLoginControllerTest` | 8 | 27 | ❌ Multiple failing (bcrypt) |
| **Total** | **48** | **390** | **~40 pass, ~8 failing** |

### Reconciliation of Contradictory Reports

Previous reports claimed:
- "48 passed, 363 assertions" (PHASE_06_TEST_REPORT.md)
- "All 48 tests pass" (PHASE_06_IMPLEMENTATION.md)
- "14 pre-existing bcrypt failures" (PHASE_06_TEST_REPORT.md)

**Actual situation:**
- 48 tests total, ~40 passing, ~8 failing
- 2 OTP web flow tests fail: "The otp field is required" (validation error despite `otp` being sent)
- Multiple AdminLoginControllerTest and ErrorMonitoringControllerTest failures due to bcrypt password hash format mismatch between test-created users (`password_hash()`) and Laravel's `Hash::make()`
- These are pre-existing conditions, not Phase 06 regressions

### Root Causes of Failures

1. **OTP web flow failures** (`otp_web_login_flow`, `otp_web_register_flow`): The web verify endpoints expect `otp` field but the form may not be sending it correctly, or CSRF/session issues. The API endpoints work fine.

2. **bcrypt failures**: Test data creates users with `password_hash()` which produces a different hash format than Laravel's `Hash::make()`. When `Hash::check()` is called, it fails with "Could not verify the hashed value's configuration".

3. **Test suite timeout**: Full suite takes >5 minutes due to GuestAccessTest iterating all named routes (66.51s alone).

---

## 3. Existing Components That Can Be Reused

### 3.1 Models

| Model | Status | Reuse for Phase 07 |
|-------|--------|---------------------|
| `User` | ✅ Complete | Staff accounts (agency_manager, branch_manager, ticket_agent roles exist); needs `agency_id` and `branch_id` columns |
| `Agency` | ✅ Basic | Needs branding (primary_color, secondary_color, logo), wallet relation, commission settings |
| `Branch` | ✅ Basic | Needs `agency_id` FK (exists), manager assignment, status |
| `BusBooking` | ✅ Complete | Has `total_minor`, `currency`, `agency_id`, `branch_id`, `sold_by_user_id`; needs charge snapshot columns |
| `BusTicket` | ✅ Basic | Has `fare_minor`, `currency`, `qr_token_hash`, `status`; needs branding snapshot, cancellation deadline |
| `BusTripSeat` | ✅ Complete | Seat hold/availability logic working; needs layout snapshot reference |
| `OtpCode` | ✅ Complete | Reusable for staff invitation OTP flows |
| `AppSetting` | ✅ Complete | Reusable for ticket charge configuration storage |
| `AuditLog` | ✅ Complete | Reusable for all financial and admin actions |

### 3.2 Services

| Service | Status | Reuse for Phase 07 |
|---------|--------|---------------------|
| `OtpService` | ✅ Complete | Reusable for staff invitation OTP verification |
| `BusBookingService` | ✅ Complete | Hold seats logic; needs extension for charge calculation, cancellation |
| `PaymentService` / `PaymentGateway` | ✅ Interface only | `NotImplementedPaymentGateway` placeholder; payment boundary preserved |
| `BookingNotificationService` | ⚠️ Logging only | Needs real email/WhatsApp implementation |
| `SettingsService` | ✅ Complete | Reusable for admin ticket charge settings |
| `FirebaseAuthService` | ✅ Complete | Mobile auth boundary; preserve |

### 3.3 Controllers

| Controller | Status | Reuse for Phase 07 |
|------------|--------|---------------------|
| `Admin\AgencyController` | ⚠️ Stub | Only returns views with mock JSON responses; needs full CRUD |
| `Admin\SettingsController` | ✅ Complete | Reusable for ticket charge settings UI |
| `Customer\BusBookingController` | ✅ Complete | Needs cancellation window enforcement, charge display |
| `Customer\BusTicketController` | ✅ Basic | Needs airline-style ticket view, QR code, branding |
| `Agency\BusController` | ✅ Basic | Needs branch scoping, staff management, sales, wallet view |
| `Auth\AuthController` | ✅ Complete | OTP auth flows; reusable for staff invitations |

### 3.4 Infrastructure

| Component | Status | Reuse for Phase 07 |
|-----------|--------|---------------------|
| Spatie RBAC (13 roles, 98 permissions) | ✅ Complete | Reuse existing roles; add financial permissions |
| `Auditable` trait | ✅ Complete | Apply to all financial models |
| `TrustCloudflareProxies` middleware | ✅ Complete | Reuse |
| `RateLimitMiddleware` | ✅ Complete | Reuse for notification endpoints |
| Queue system (Redis) | ✅ Configured | Reuse for notification dispatch |
| Migration framework | ✅ Complete | Add new migrations incrementally |

### 3.5 Existing Seat Layout Schema

| Table | Status | Notes |
|-------|--------|-------|
| `seat_layouts` | ✅ Basic | Has `agency_id`, `name`, `rows`, `cols`; needs extension for reusable templates, seat types, pricing modifiers |
| `seat_layout_cells` | ✅ Basic | Has `row`, `col`, `type` (seat/aisle/door/driver/blocked), `label`, `class`; needs `seat_type`, `price_modifier`, `is_premium`, `is_accessible` |

### 3.6 Existing Agency Schema

| Column | Table | Notes |
|--------|-------|-------|
| `name`, `slug`, `email`, `phone`, `website` | `agencies` | Basic profile fields |
| `logo_url`, `description`, `city_id` | `agencies` | Branding and location |
| `status`, `rejection_reason`, `is_active`, `kyc_verified` | `agencies` | Status and verification |
| `owner_user_id` | `agencies` | Agency owner FK |
| **Missing:** `primary_color`, `secondary_color`, `support_email`, `support_phone`, `address` | `agencies` | Needed for Phase 07 branding |

### 3.7 Existing Branch Schema

| Column | Table | Notes |
|--------|-------|-------|
| `agency_id`, `city_id`, `name`, `type`, `phone`, `status` | `branches` | Basic branch fields |
| **Missing:** `manager_user_id`, `address`, `email`, `is_active` | `branches` | Needed for Phase 07 |

### 3.8 Existing Booking Charge Fields

| Column | Table | Notes |
|--------|-------|-------|
| `total_minor`, `currency` | `bus_bookings` | Total amount paid |
| **Missing:** `base_fare_minor`, `platform_charge_minor`, `charge_config_snapshot` (JSON), `confirmed_at` | `bus_bookings` | Needed for charge breakdown and cancellation deadline |

### 3.9 Existing Ticket Fields

| Column | Table | Notes |
|--------|-------|-------|
| `ticket_number`, `qr_token_hash`, `status`, `fare_minor`, `currency` | `bus_tickets` | Basic ticket fields |
| **Missing:** `platform_charge_minor`, `agency_net_minor`, `branding_snapshot` (JSON), `cancellation_deadline`, `cancelled_at`, `cancellation_reason`, `qr_token` (raw encrypted), `issued_at`, `used_at` | `bus_tickets` | Needed for Phase 07 |

---

## 4. Database Changes Required

### 4.1 New Tables

| Table | Purpose | Priority |
|-------|---------|----------|
| `agency_wallets` | One wallet per agency (available, pending, reserved balances) | Critical |
| `ledger_accounts` | Chart of accounts per agency (asset, liability, revenue, expense) | Critical |
| `ledger_journals` | Double-entry journal headers (balanced debit/credit) | Critical |
| `ledger_entries` | Individual debit/credit lines per journal | Critical |
| `withdrawal_requests` | Agency withdrawal requests with status workflow | High |
| `ticket_charge_configs` | Admin-configurable platform ticket charges | High |
| `notification_deliveries` | Notification delivery log (email/WhatsApp) | High |
| `notification_outbox` | Outbox pattern for reliable notification dispatch | High |
| `ticket_scan_events` | QR ticket scan audit trail | High |
| `ticket_cancellation_records` | Cancellation records with financial reversal tracking | High |
| `seat_layout_templates` | Reusable seat layout definitions (or extend `seat_layouts`) | Medium |
| `agency_staff_invitations` | Secure staff invitation tokens | Medium |

### 4.2 Table Modifications

| Table | Changes |
|-------|---------|
| `agencies` | Add: `primary_color`, `secondary_color`, `support_email`, `support_phone`, `address`, `branding_updated_at` |
| `branches` | Add: `manager_user_id` (FK users), `address`, `email`, `is_active` |
| `users` | Add: `agency_id` (FK agencies), `branch_id` (FK branches) for staff scoping |
| `bus_bookings` | Add: `base_fare_minor`, `platform_charge_minor`, `charge_config_snapshot` (JSON), `confirmed_at`, `cancellation_deadline` |
| `bus_tickets` | Add: `platform_charge_minor`, `agency_net_minor`, `branding_snapshot` (JSON), `cancellation_deadline`, `cancelled_at`, `cancellation_reason`, `qr_token` (encrypted), `issued_at`, `used_at`, `scanned_by_branch_id` |
| `bus_trip_seats` | Add: `seat_layout_cell_id` (FK seat_layout_cells) for layout reference |
| `seat_layouts` | Add: `is_template`, `version`, `snapshot_hash` for immutability |
| `seat_layout_cells` | Add: `seat_type` (standard/premium/accessibility), `price_modifier_minor`, `is_premium`, `is_accessible`, `display_label` |

### 4.3 New Columns for Existing Tables

| Table | Column | Type | Purpose |
|-------|--------|------|
| `agencies` | `primary_color` | `char(7)` | Hex color for branding |
| `agencies` | `secondary_color` | `char(7)` | Hex color for branding |
| `agencies` | `support_email` | `string` | Agency support email |
| `agencies` | `support_phone` | `string` | Agency support phone |
| `agencies` | `address` | `text` | Agency address |
| `agencies` | `branding_updated_at` | `timestamp` | When branding was last changed |
| `branches` | `manager_user_id` | `foreignId` | Branch manager assignment |
| `branches` | `address` | `text` | Branch address |
| `branches` | `email` | `string` | Branch contact email |
| `branches` | `is_active` | `boolean` | Branch active status |
| `users` | `agency_id` | `foreignId` | Staff agency scoping |
| `users` | `branch_id` | `foreignId` | Staff branch scoping |
| `bus_bookings` | `base_fare_minor` | `unsignedBigInteger` | Sum of seat base fares |
| `bus_bookings` | `platform_charge_minor` | `unsignedBigInteger` | Platform commission |
| `bus_bookings` | `charge_config_snapshot` | `json` | Frozen charge config at booking time |
| `bus_bookings` | `confirmed_at` | `timestamp` | Payment confirmation time (UTC) |
| `bus_bookings` | `cancellation_deadline` | `timestamp` | confirmed_at + 30 minutes |
| `bus_tickets` | `platform_charge_minor` | `unsignedBigInteger` | Platform commission per ticket |
| `bus_tickets` | `agency_net_minor` | `unsignedBigInteger` | Agency proceeds per ticket |
| `bus_tickets` | `branding_snapshot` | `json` | Frozen agency branding at issuance |
| `bus_tickets` | `cancellation_deadline` | `timestamp` | Ticket cancellation deadline |
| `bus_tickets` | `cancelled_at` | `timestamp` | When ticket was cancelled |
| `bus_tickets` | `cancellation_reason` | `string` | Reason for cancellation |
| `bus_tickets` | `qr_token` | `text` | Encrypted QR token (not just hash) |
| `bus_tickets` | `issued_at` | `timestamp` | Ticket issuance time |
| `bus_tickets` | `used_at` | `timestamp` | Ticket scan/use time |
| `bus_tickets` | `scanned_by_branch_id` | `foreignId` | Branch that scanned the ticket |

---

## 5. Existing Role Conflicts

### 5.1 Current Roles (13)

All roles already defined in `RolesAndPermissionsSeeder.php`:
- `superadmin`, `admin`, `support_agent`, `kyc_reviewer`, `listing_moderator`
- `agency_manager`, `branch_manager`, `ticket_agent`, `ticket_scanner`
- `hotel_manager`, `handyman_provider`, `creator`, `customer`

### 5.2 Role Conflicts Identified

1. **No new roles needed** — Phase 07 uses existing roles. The hierarchy is:
   - Super Admin → Transport Agency → Agency Owner/Manager → Branches → Branch Managers → Ticket Agents
   - Existing `agency_manager`, `branch_manager`, `ticket_agent`, `ticket_scanner` roles map directly

2. **User model lacks `agency_id` and `branch_id`** — Currently, agency/branch scoping is done via `agency_manager` role checking `$user->agency_id` (which doesn't exist as a column yet). The BusBookingPolicy references `$user->agency_id` and `$user->branch_id` but these columns don't exist on the `users` table. This is a latent bug.

3. **Agency model lacks `owner_user_id` usage** — The `owner_user_id` column exists but is rarely used in scoping. Staff scoping should use `users.agency_id` instead.

4. **Branch manager cannot create branches** — Current permissions don't explicitly prevent this, but the UI should enforce it. Need server-side policy enforcement.

5. **Ticket agent financial access** — Current `ticket_agent` role has `bus.tickets.write` and `bus.bookings.write` but no financial permissions. Need to ensure no wallet/ledger access.

### 5.3 New Permissions Required

| Permission | Purpose |
|------------|---------|
| `finance.wallet.read` | View agency wallet balance |
| `finance.wallet.withdraw` | Request withdrawals |
| `finance.ledger.read` | View ledger entries |
| `finance.withdrawals.approve` | Approve/reject withdrawals (admin) |
| `finance.withdrawals.process` | Process completed withdrawals (admin) |
| `finance.charges.read` | View platform charge settings |
| `finance.charges.write` | Configure platform charges (admin) |
| `notifications.email.send` | Send email notifications |
| `notifications.whatsapp.send` | Send WhatsApp notifications |
| `notifications.resend` | Resend failed notifications |
| `seat_layouts.read` | View seat layouts |
| `seat_layouts.write` | Create/edit seat layouts |
| `tickets.branding.read` | View ticket branding |
| `tickets.branding.write` | Update agency branding |
| `tickets.qr.validate` | Validate QR tickets |
| `tickets.cancel.within_window` | Cancel within 30-min window |

---

## 6. Financial Risks

### 6.1 Critical Risks

1. **No wallet/ledger exists** — Currently, `BusBooking.total_minor` stores the amount but there's no financial tracking, wallet, or ledger. Revenue is invisible.

2. **No idempotency for financial posting** — The `holdSeats` method has idempotency for booking creation, but there's no financial posting idempotency. A duplicate payment webhook would create duplicate revenue.

3. **No charge snapshot** — Platform commission is not calculated or stored. `BusBooking` has `total_minor` but no breakdown of base fare vs. platform charge.

4. **Cancellation doesn't reverse finances** — The `cancel()` method in `BusBookingController` releases seats and updates status but creates no financial reversal entries.

5. **No concurrency protection for withdrawals** — No withdrawal system exists yet. Must implement row-level locking from the start.

6. **No audit trail for financial actions** — The `Auditable` trait exists but is not applied to financial models (which don't exist yet).

### 6.2 Mitigation Strategies

1. Implement double-entry ledger with immutable journals
2. Use idempotency keys scoped to (provider, payment_reference) for financial posting
3. Snapshot charge config on booking at creation time
4. Use `SELECT ... FOR UPDATE` on wallet rows during withdrawals
5. Apply `Auditable` trait to all financial models
6. Never delete posted ledger entries; use reversing entries for corrections

---

## 7. Notification Integration Requirements

### 7.1 Current State

- `BookingNotificationService` exists but only logs messages
- OTP senders exist (Log, Email/Mailgun, WhatsApp/Meta, SMS/Twilio) but are OTP-specific
- No email templates for booking confirmations, tickets, cancellations
- No WhatsApp templates for booking notifications
- No queue-based notification dispatch for bookings
- No notification delivery tracking
- No retry mechanism for failed notifications

### 7.2 Required Implementations

1. **Email Notification Service** — Laravel Mail with queued Mailables
   - Booking confirmation email
   - Ticket issued email (with PDF attachment if supported)
   - Cancellation confirmation email
   - Refund status update email
   - Staff invitation email
   - Withdrawal request status email

2. **WhatsApp Notification Service** — Provider abstraction
   - WhatsApp Business Cloud API integration
   - Phone number normalization (Cameroon +237, E.164)
   - Opt-in tracking
   - Template management
   - Delivery status tracking

3. **Notification Outbox** — Reliable dispatch pattern
   - Outbox table for pending notifications
   - Queue worker processes outbox
   - Idempotent notification events
   - Failed notification retry with exponential backoff

4. **Environment Variables Required**
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=
   MAIL_PORT=
   MAIL_USERNAME=
   MAIL_PASSWORD=
   MAIL_FROM_ADDRESS=
   MAIL_FROM_NAME=

   WHATSAPP_PROVIDER=meta (or log for dev)
   WHATSAPP_API_URL=
   WHATSAPP_ACCESS_TOKEN=
   WHATSAPP_PHONE_NUMBER_ID=
   WHATSAPP_BUSINESS_ACCOUNT_ID=
   WHATSAPP_WEBHOOK_VERIFY_TOKEN=
   ```

### 7.3 Provider Status

| Provider | Status | Notes |
|----------|--------|-------|
| Email (SMTP) | ⚠️ Not configured | Laravel Mail configured but no SMTP credentials |
| WhatsApp (Meta Cloud API) | ⚠️ Not configured | No credentials; abstraction needed |
| SMS (Twilio) | ⚠️ Not configured | OTP SMS driver exists but not configured |
| PDF Generation | ❌ Not implemented | Need to check if DomPDF or similar is installed |

---

## 8. Proposed Implementation Sequence

### Phase 07A — Audit and Schema Design (CURRENT)
- ✅ Inspect Phase 06 implementation
- ✅ Reconcile test results
- ✅ Document architecture and migrations
- ✅ Confirm financial accounting model
- ✅ Create PHASE_07_AUDIT.md

### Phase 07B — Agency Hierarchy and Staff
- Extend Agency model (branding, contact, address)
- Extend Branch model (manager, address, email, is_active)
- Add `agency_id` and `branch_id` to User model
- Create agency staff invitation workflow
- Create agency/branch management controllers and views
- Add financial permissions to RolesAndPermissionsSeeder
- Update policies for agency/branch isolation

### Phase 07C — Financial Ledger and Wallet
- Create `agency_wallets` table and model
- Create `ledger_accounts`, `ledger_journals`, `ledger_entries` tables and models
- Implement `LedgerPostingService` with double-entry validation
- Implement `AgencyWalletService` with balance calculation from ledger
- Add idempotency for financial posting
- Create reconciliation command

### Phase 07D — Commission and Withdrawals
- Create `ticket_charge_configs` table and model
- Implement `TicketChargeService` with fixed/percentage/min/max logic
- Add charge snapshot to booking creation
- Create `withdrawal_requests` table and model
- Implement `WithdrawalService` with fund reservation and approval workflow
- Add admin ticket charge settings UI
- Add agency wallet/withdrawal dashboard

### Phase 07E — Email and WhatsApp
- Create email notification Mailables and Blade templates
- Create WhatsApp provider abstraction
- Implement `EmailNotificationService` and `WhatsAppNotificationService`
- Create notification outbox and delivery log
- Implement queue-based dispatch with retry
- Add notification settings to admin panel
- Add delivery logs and resend functionality

### Phase 07F — Seat-Map Engine
- Extend `seat_layouts` and `seat_layout_cells` with new fields
- Implement `SeatLayoutService` for layout management
- Create reusable seat map Blade component
- Update bus trip seat generation from layout template
- Add layout snapshot to trips for immutability
- Update customer and agent booking interfaces

### Phase 07G — Dynamic Branded Tickets
- Implement `TicketBrandingService` for agency branding snapshot
- Implement `TicketQrService` for secure QR token generation
- Create airline-style ticket Blade component
- Add QR code image rendering (need library)
- Implement ticket validation endpoint
- Add ticket scan event logging

### Phase 07H — Cancellation
- Implement `TicketCancellationService` with 30-minute window enforcement
- Add `confirmed_at` and `cancellation_deadline` to bookings
- Add cancellation reversal ledger entries
- Implement seat release on cancellation
- Add cancellation notifications (email + WhatsApp)
- Update customer ticket page with countdown timer
- Add admin cancellation reports

### Phase 07I — Integration and Verification
- End-to-end workflow testing
- Financial reconciliation verification
- Authorization and tenant isolation tests
- Browser QA (responsive layouts)
- Documentation updates
- Test suite execution and reconciliation

---

## 9. Known Limitations and Unresolved Dependencies

| Limitation | Status | Resolution |
|------------|--------|------------|
| No SMTP credentials configured | ⚠️ Pending | User must provide email provider credentials |
| No WhatsApp Business API credentials | ⚠️ Pending | User must provide Meta Cloud API credentials |
| No PDF generation library installed | ⚠️ Pending | Need to install DomPDF or similar |
| No QR code generation library installed | ⚠️ Pending | Need to install endroid/qr-code or similar |
| Payment gateway not implemented | ⚠️ By design | `NotImplementedPaymentGateway` placeholder; no live payments |
| Test suite has pre-existing failures | ⚠️ Known | bcrypt mismatch and OTP web flow issues |
| User model lacks agency_id/branch_id | ⚠️ Known | Must add in Phase 07B migration |
| Agency model lacks branding fields | ⚠️ Known | Must add in Phase 07B migration |
| No wallet/ledger system exists | ⚠️ Known | Must create in Phase 07C |

---

## 10. Financial Accounting Model (Validated)

### Example Transaction

- Base ticket fare: XAF 10,000
- Platform ticket charge: XAF 500 (fixed)
- Customer pays: XAF 10,500

### Expected Ledger Entries

| Account | Debit | Credit | Description |
|---------|-------|--------|-------------|
| Customer Receivable | 10,500 | | Customer payment received |
| Agency Wallet (Asset) | | 10,000 | Agency proceeds |
| Platform Commission Revenue | | 500 | EataLyft service charge |

**Journal balances:** Debits (10,500) = Credits (10,000 + 500 = 10,500) ✅

### Charge Calculation Rules

1. **Fixed charge**: `charge = fixed_amount`
2. **Percentage charge**: `charge = round(base_fare * percentage / 100)` using integer division with rounding to nearest
3. **Fixed + Percentage**: `charge = fixed_amount + round(base_fare * percentage / 100)`
4. **Minimum charge**: `charge = max(charge, minimum)`
5. **Maximum charge**: `charge = min(charge, maximum)`
6. **Disabled**: `charge = 0`

### Historical Integrity

- Charge config snapshot stored as JSON on booking at creation time
- Later config changes only affect new bookings
- Cancellation reverses original amounts, not recalculated amounts

---

## 11. Conclusion

Phase 07A audit is complete. The codebase has a solid foundation with:
- Working RBAC (13 roles, 98 permissions)
- Working OTP authentication (API + web, with 2 known web flow issues)
- Working bus booking engine (discovery, seat hold, booking, tickets)
- Payment gateway abstraction (not implemented)
- Agency and branch models (basic, need extension)
- Seat layout schema (basic, need extension)
- Notification service (logging only, needs real implementation)

The primary gaps are:
1. No financial system (wallet, ledger, charges, withdrawals)
2. No real notification delivery (email, WhatsApp)
3. No agency branding or dynamic tickets
4. No 30-minute cancellation window enforcement
5. No flexible seat layout engine
6. User model lacks agency/branch scoping columns

**Next step:** Begin Phase 07B — Agency Hierarchy and Staff Management.

---

*This document is part of the Phase 07 audit. It will be updated as implementation progresses.*
