# PURGE PLAN — Eatalyft Laravel 12 Rebuild (UPDATED SCOPE)

**Source**: Legacy Supabase schema (`legacy/schema/*.sql`)  
**Goal**: Remove **only** Rides, Food, Deliveries/Parcel, Vendors, ALL payments.  
**Build Now**: Auth, Roles, KYC, Currencies/FX, Notifications, Audit Log, **Hotels**, **Agencies + BUS** (agencies, branches, routes/trips, seat inventory, tickets/bookings), **Listings**, **eSIM**, **Boxo** integration (consents, miniapps catalog, OAuth, webhook events), Integrations Vault.  
**Still Deferred (schema notes only, no code)**: Handyman, Nanny, Cleaner, Geo/PostGIS/Live tracking.  
**Removed**: Rides, Food, Deliveries, Vendors, and **ALL payments** (no Notch Pay/MeSomb/Fapshi/wallet/ledger/payout code, tables, columns, env vars or placeholders). Payments get designed after the whole system is finished.

**Stack**: Blade + Sneat Pro (Bootstrap 5, Vite) for all web portals (admin, agency, hotel, creator, customer). Public pages = Blade under `/services/{slug}`. Expo apps use `/api/v1` (Sanctum tokens).

---

## 1. DATABASE TABLES — BY SERVICE

### 1.1 BUILD NOW (recreate as Laravel migrations)

| Table | Source Migration | Notes |
|-------|------------------|-------|
| `users` | Laravel default + 001_schema.sql | Core auth; extend with role, KYC fields, currency_preference |
| `roles` / `permissions` / `model_has_roles` / `role_has_permissions` | spatie/laravel-permission | RBAC (replaces `role_permissions`) |
| `audit_logs` | 001_schema.sql | Audit trail |
| `app_settings` | 001_schema.sql | Filter: keep only non-payment keys (see §3) |
| `currencies` | 20260920_multi_currency.sql | ISO 4217 catalog |
| `exchange_rates` | 20260920_multi_currency.sql | Live + pegged rates |
| `exchange_rate_history` | 20260920_multi_currency.sql | Append-only audit |
| `fx_margins` | 20260920_multi_currency.sql | Spread/fee config |
| `country_currency_map` | 20260920_multi_currency.sql | Country → default currency |
| `user_currency_preferences` | 20260920_multi_currency.sql | Per-user display currency (on `users` table) |
| `notifications` | 20260921_notifications.sql | User notifications (not mirror) |
| `notification_deliveries` | 20260921_notifications.sql | Delivery tracking |
| `kyc_profiles` | 20260920_kyc_didit.sql | KYC status per user |
| `kyc_sessions` | 20260920_kyc_didit.sql | Didit session records |
| `kyc_results` | 20260920_kyc_didit.sql | Encrypted PII flags |
| `kyc_webhook_events` | 20260920_kyc_didit.sql | Idempotent webhook log |
| `kyc_review_actions` | 20260920_kyc_didit.sql | Admin decisions |
| `kyc_tier_limits` | 20260920_kyc_didit.sql | Limits per tier/currency/service |
| `integration_credentials` | 20260920_kyc_didit.sql | Encrypted vault (Didit, FX, mail, etc., **eSIM provider, Boxo**) |
| `hotel_properties` | 20260920_hotels.sql | Hotel catalog |
| `hotel_room_types` | 20260920_hotels.sql | Room types per property |
| `hotel_rate_plans` | 20260920_hotels.sql | Seasonal pricing |
| `hotel_availability` | 20260920_hotels.sql | Per-night inventory |
| `booking_holds` | 20260920_hotels.sql | 15-min reservation holds (hotels) |
| `hotel_bookings` | 20260920_hotels.sql | Bookings: status = requested/held/confirmed/cancelled/expired (no payment cols) |
| `hotel_cancellation_policies` | 20260920_hotels.sql | Free-cancel rules |
| `hotel_reviews` | 20260920_hotels.sql | Post-stay reviews |
| `agencies` | 20261002_bus_agency_schema.sql | Agency tenant root |
| `agency_logos` | 20261018_agency_logos.sql | Logo storage |
| `bus_branches` | 20261002_bus_agency_schema.sql | Agency branches/stations |
| `bus_routes` | 20261005_bus_operations.sql | Routes (origin/destination, distance, duration) |
| `bus_trips` | 20261002_bus_agency_schema.sql | Scheduled departures (linked to route) |
| `bus_seat_inventory` | 20261011_bus_manifests.sql | Per-trip seat map (row/col, status) |
| `bus_tickets` | 20261002_bus_agency_schema.sql | Individual sold seats |
| `bus_bookings` | 20261002_bus_agency_schema.sql | Seat-group bookings (legacy client flow) |
| `bus_holds` | new | Per-trip seat holds (15-min expiry, row locks) |
| `service_role_grants` | 20261012_service_role_grants.sql | Role→service access |
| `boxo_consents` | 20261022_boxo_consents.sql | Miniapp OAuth consents |
| `boxo_miniapps` | 20261022_boxo_miniapps.sql | Miniapp catalog |
| `boxo_webhook_events` | 20261022_boxo_webhook_events.sql | Boxo webhook log (idempotent) |
| `esim_providers` | new | eSIM provider config (endpoints, auth) |
| `esim_plans` | new | eSIM data plans (provider, region, data, validity, price_minor) |
| `esim_orders` | new | Orders: status = requested/activated/failed/expired (no payment) |
| `esim_activations` | new | Activation status tracking (ICCID, QR, SM-DP+ info) |
| `listings` | 20260920_listings.sql | Generic listings |
| `listings_phase4` | 20260922_listings_phase4.sql | Extended listings |
| `listings_orgs` | 20261006_listings_orgs.sql | Org-owned listings |
| `otp_verifications` | 20260920_otp_signup.sql | OTP auth flow |
| `password_reset_tokens` | Laravel default | Force password reset |
| `personal_access_tokens` | Laravel Sanctum | API tokens |
| `sessions` | Laravel default | Web sessions |

### 1.2 DEFERRED (schema notes only, migrations created but NO controllers/views/API)

| Table | Source Migration | Service | Reason |
|-------|------------------|---------|--------|
| `handyman_profiles` | 001_schema.sql | Handyman | Deferred |
| `nanny_profiles` | 001_schema.sql | Nanny/Cleaner | Deferred |
| `locations` | 20260920_locations.sql | Geo | Deferred |
| `geo_functions` | 20260607_geo_functions.sql | PostGIS | Deferred |
| `geo_services` | 20260607_geo_services.sql | Geo | Deferred |
| `live_locations_v2` | 20260607_live_locations_v2.sql | Real-time | Deferred |
| `profiles_organizations` | 20261004_profiles_organizations.sql | Generic | Deferred |
| `service_orders` | 20261022_service_orders.sql | Generic | Deferred |
| `service_order_events` | 20261022_service_order_events.sql | Generic | Deferred |

### 1.3 REMOVE ENTIRELY (payments & deleted services)

| Table | Source Migration | Service |
|-------|------------------|---------|
| `wallets` | 001_schema.sql | Payments |
| `wallet_transactions` | 001_schema.sql | Payments |
| `wallet_balances` | 20260920_multi_currency.sql | Payments |
| `payments` | (implied) | Payments |
| `payment_attempts` | 20261003_payment_gateway_layer.sql | Payments |
| `gateway_webhook_events` | 20261003_payment_gateway_layer.sql | Payments |
| `payment_providers` | 20261003_payment_gateway_layer.sql | Payments |
| `payment_intents` | 20261024_paycore_payment_intents.sql | Payments |
| `wallet_ledger` | 20261024_paycore_wallet_ledger.sql | Payments |
| `payouts` | 20261027_paycore_payouts.sql | Payments |
| `ledger_accounts` | 20261008_ledger_wallets.sql | Payments |
| `ledger_entries` | 20261008_ledger_wallets.sql | Payments |
| `transfer_idempotency` | 20261008_ledger_wallets.sql | Payments |
| `withdrawals` | 20261008_ledger_wallets.sql | Payments |
| `withdrawal_events` | 20261008_ledger_wallets.sql | Payments |
| `ledger_reconciliation_flags` | 20261008_ledger_wallets.sql | Payments |
| `booking_payments` | 20260920_hotels.sql | Payments (hotel) |
| `manager_payouts` | 20260920_hotels.sql | Payments (hotel) |
| `rls_policies` | 20260607_rls_policies.sql | Supabase-specific |
| `webhook_rls_realtime` | 20260817_webhook_rls_realtime.sql | Supabase-specific |
| `notifications_mirror` | 20260921_notifications.sql | Replaced by `notifications` |
| `notification_deliveries_mirror` | 20260921_notifications.sql | Replaced by `notification_deliveries` |
| `auth_sessions` | 001_schema.sql | Replaced by Laravel `sessions` |
| `profiles` | 001_schema.sql | Replaced by Laravel `users` + extensions |
| `driver_profiles` | 001_schema.sql | Rides (removed) |
| `agent_profiles` | 001_schema.sql | Deliveries (removed) |
| `vendor_profiles` | 001_schema.sql | Vendors/Food (removed) |

---

## 2. ENUMS / ROLE VALUES — MODIFY

| Enum / Column | Current Values | Keep | Remove |
|---------------|----------------|------|--------|
| `users.role` (spatie) | customer, driver, agent, handyman, nanny, cleaner, hotel, vendor, agency, creator, dispatch, accountant, admin, superadmin | customer, hotel, agency, creator, admin, superadmin, **driver, agent, handyman, nanny, cleaner** (deferred roles) | vendor, dispatch, accountant |
| `service_type` (if used) | ride, food, parcel, handyman, nanny, bus, hotel | hotel, bus, handyman, nanny (deferred) | ride, food, parcel |
| `kyc_status` | NOT_STARTED, PENDING, IN_REVIEW, VERIFIED, REJECTED, EXPIRED | ALL | — |
| `booking_status` (hotel/bus) | — | requested, held, confirmed, cancelled, expired | — |
| `esim_order_status` | — | requested, activated, failed, expired | — |

> **Note**: Deferred roles (handyman, nanny, cleaner, driver, agent) stay in the `roles` table but have no controllers/views/API in this phase.

---

## 3. APP_SETTINGS KEYS — PURGE LIST

### 3.1 DELETE (payment/service-specific)

```
commission_ride, commission_food, commission_parcel, commission_handyman, commission_nanny, commission_bus, commission_hotel
wallet_min_topup, loyalty_points_name, loyalty_accrual_rate, loyalty_redemption_rate, loyalty_min_redemption, referral_bonus
finance_payout_fee_percent, finance_min_payout_amount
rides_tiers, parcels_base_fare, parcels_per_km_rate, parcels_min_fare, parcels_max_weight_kg
proximity_search_radius_km, proximity_max_candidates, proximity_job_timeout_seconds, proximity_auto_assign
roles_require_approval  (contains removed roles)
feature_food_delivery, feature_ride_hailing, feature_parcel_delivery
feature_bus_booking, feature_handyman, feature_nanny, feature_cleaner  (deferred features)
feature_loyalty_program, feature_referral_program
integrations_meta.payment_gateway
```

### 3.2 KEEP (shared/platform)

```
frontend_app_name, frontend_support_email, frontend_currency, frontend_country_code, frontend_app_version
maintenance_enabled, maintenance_message
launch_enabled, launch_title, launch_subtitle
tax_enabled, tax_rate
feature_hotel_booking, feature_whatsapp_notify, feature_email_notify, feature_push_notify
feature_esim, feature_boxo
integrations_meta (minus payment_gateway)
hotel_commission_pct  (stored for future; no payout logic yet)
fx_pivot_currency, fx_refresh_interval_seconds, fx_stale_after_seconds, fx_deviation_threshold_percent, fx_quote_ttl_seconds, fx_major_pairs
kyc_max_attempts, kyc_cooldown_hours, kyc_retention_days, kyc_reverify_on_expire, kyc_workflow_tier1, kyc_workflow_tier2
```

---

## 4. PERMISSIONS (spatie) — KEEP / REMOVE

### 4.1 KEEP (create in seeder)

```
# KYC
kyc:view, kyc:review, kyc:configure

# Currencies & FX
currencies:view, currencies:manage, rates:override, fx_margins:manage

# Hotels
hotels:view, hotels:manage, hotels:bookings, hotels:reviews

# Agencies + Bus
agencies:view, agencies:manage, agencies:branches
bus:routes:view, bus:routes:manage
bus:trips:view, bus:trips:manage
bus:tickets:view, bus:tickets:manage
bus:bookings:view, bus:bookings:manage
bus:holds:manage
bus:manifests:view

# eSIM
esim:plans:view, esim:plans:manage
esim:orders:view, esim:orders:manage
esim:activations:view

# Boxo
boxo:view, boxo:manage
boxo:miniapps:view, boxo:miniapps:manage
boxo:consents:view
boxo:webhooks:view

# Listings
listings:view, listings:manage

# Admin
users:view, users:manage, roles:manage, settings:manage, audit:view, cache:clear
```

### 4.2 REMOVE (do not create)

```
view_wallets, manage_commissions, manage_payouts, manage_withdrawals
wallet:topup, wallet:transfer, wallet:view_ledger
payment:process, payment:refund, payment:webhook
```

---

## 5. ENV VARIABLES — PURGE / KEEP

| Variable | Action |
|----------|--------|
| `NOTCHPAY_*` | DELETE |
| `MESOMB_*` | DELETE |
| `FAPSHI_*` | DELETE |
| `WALLET_*` | DELETE |
| `LEDGER_*` | DELETE |
| `PAYMENT_*` | DELETE |
| `REDIS_*` | Keep (staging/prod only; env-driven) |
| `SUPABASE_*` | DELETE |
| `FIREBASE_*` | DELETE |
| `KYC_DIDIT_*` | KEEP |
| `MAILGUN_*`, `WHATSAPP_*`, `FCM_*` | KEEP |
| `GOOGLE_MAPS_*`, `AWS_*` | KEEP |
| `EXCHANGE_RATE_*` | KEEP |
| `ESIM_PROVIDER_*` | KEEP (add to vault) |
| `BOXO_*` | KEEP (add to vault) |

---

## 6. RULES FOR BUS, ESIM, BOXO WITHOUT PAYMENTS

### 6.1 Bus & Hotel Bookings
- **Statuses**: `requested` → `held` (15-min seat/room hold) → `confirmed` OR `expired` → `cancelled`
- **Inventory protection**: DB transactions + row locks (`SELECT ... FOR UPDATE`)
- **Hold expiry**: Scheduled command (cron) releases expired holds
- **Concurrency tests**: Two users, same seat/room → only one succeeds

### 6.2 eSIM
- **Catalog**: providers, plans (region, data, validity, price_minor)
- **Orders**: status = `requested` / `activated` / `failed` / `expired` (stop at `requested` until payments exist)
- **Activation tracking**: ICCID, QR code, SM-DP+ address, activation status
- **Provider**: Read legacy docs/code to identify provider + endpoints; list UNKNOWN in ARCHITECTURE.md
- **Credentials**: Add to integrations vault with "Test connection" button

### 6.3 Boxo
- **Consents**: Port `boxo_consents` (uid, app_id, granted_at, revoked_at, scopes)
- **Miniapps catalog**: Port `boxo_miniapps` (id, name, description, icon, categories, payment_type flag)
- **OAuth consent flow**: Redirect → user grants scopes → store consent
- **Webhook events**: Port `boxo_webhook_events` with idempotency (provider + event_id unique)
- **Exclude**: All settlement/refund/payment logic
- **Audit seed data**: List any payment-type miniapps for decision

---

## 7. STACK & ARCHITECTURE

| Layer | Technology |
|-------|------------|
| Web Portals (admin, agency, hotel, creator, customer) | **Blade + Sneat Pro** (Bootstrap 5, Vite) |
| Public Pages | **Blade** (own design) under `/services/{slug}` |
| PWA | Manifest + Service Worker on Blade pages |
| Mobile (Expo) | `/api/v1` with Sanctum tokens |
| Auth | Laravel `users` + Sanctum + native sessions |
| Permissions | spatie/laravel-permission |
| Design System | Sneat Pro licensed kit at `<PATH>` — copy layouts, menu, auth pages, tables, forms, charts |
| Layout System | **ONE** layout with role-based menu config; no duplicated pages |
| i18n | EN / FR (design tokens, currency switcher) |

---

## 8. STAGES (stop for review after each)

### Stage 1 — Base
- Sneat Pro layout, role-based menu, design tokens, i18n EN/FR, currency switcher, health/status page
- Users table extensions (role, KYC fields, currency_preference)
- Spatie permissions seeder
- Audit log model + observer

### Stage 2 — Admin Portal
- Login, 2FA-ready, dashboard
- User management (CRUD, roles, permissions, audit log)
- Deleted-accounts archive (soft delete)
- Settings: app, maintenance, tax, cache-clear

### Stage 3 — Integrations Vault + Test Buttons
- Encrypted credentials vault + "Test connection" button each:
  - WhatsApp Meta Cloud API
  - Didit KYC
  - Mailgun
  - FCM
  - Google Maps
  - AWS S3
  - Exchange-rate feed (ECB / custom)
  - **eSIM provider** (TBD from legacy)
  - **Boxo** (TBD from legacy)

### Stage 4 — Public Pages & Auth
- Public pages (Blade, SEO, bilingual)
- Auth flows: register, login, password reset, OTP, IP block, device management

### Stage 5 — Role Portals + /api/v1 (in order)
1. **Agency (Bus)** — branches, routes, trips, seat inventory, tickets, bookings, holds, manifests
2. **Hotel** — property/room/rate/availability management, bookings (requested/held/confirmed/cancelled/expired), reviews
3. **Creator** — content, miniapps, earnings (placeholder)
4. **Customer** — bookings, notifications, KYC, currency, profile

> **Before building Agency portal**: Propose agency staff/branch-manager model in ARCHITECTURE.md for approval.

### Stage 6 — eSIM Module
- Provider config, plans catalog, orders (requested only), activation tracking

### Stage 7 — Boxo Module
- Miniapps catalog, OAuth consent flow, webhook event log

---

## 9. MIGRATION ORDER (Laravel)

### Phase 1 — Core (run first)
1. `create_users_table` (Laravel default + role, KYC, currency columns)
2. `create_roles_permissions_tables` (spatie)
3. `create_audit_logs_table`
4. `create_app_settings_table` (filtered keys only)
5. `create_sessions_table` (Laravel default)
6. `create_personal_access_tokens_table` (Sanctum)

### Phase 2 — Currencies
7. `create_currencies_table`
8. `create_exchange_rates_table`
9. `create_exchange_rate_history_table`
10. `create_fx_margins_table`
11. `create_country_currency_map_table`
12. `add_currency_preference_to_users_table`

### Phase 3 — Notifications
13. `create_notifications_table`
14. `create_notification_deliveries_table`

### Phase 4 — KYC
15. `create_kyc_profiles_table`
16. `create_kyc_sessions_table`
17. `create_kyc_results_table`
18. `create_kyc_webhook_events_table`
19. `create_kyc_review_actions_table`
20. `create_kyc_tier_limits_table`
21. `create_integration_credentials_table`

### Phase 5 — Hotels
22. `create_hotel_properties_table`
23. `create_hotel_room_types_table`
24. `create_hotel_rate_plans_table`
25. `create_hotel_availability_table`
26. `create_booking_holds_table`
27. `create_hotel_bookings_table` (status: requested/held/confirmed/cancelled/expired)
28. `create_hotel_cancellation_policies_table`
29. `create_hotel_reviews_table`

### Phase 6 — Agencies & Bus
30. `create_agencies_table`
31. `create_agency_logos_table`
32. `create_bus_branches_table`
33. `create_bus_routes_table`
34. `create_bus_trips_table`
35. `create_bus_seat_inventory_table`
36. `create_bus_tickets_table`
37. `create_bus_bookings_table`
38. `create_bus_holds_table`
39. `create_service_role_grants_table`

### Phase 7 — Listings
40. `create_listings_table`
41. `create_listings_phase4_table`
42. `create_listings_orgs_table`

### Phase 8 — eSIM
43. `create_esim_providers_table`
44. `create_esim_plans_table`
45. `create_esim_orders_table`
46. `create_esim_activations_table`

### Phase 9 — Boxo
47. `create_boxo_consents_table`
48. `create_boxo_miniapps_table`
49. `create_boxo_webhook_events_table`

### Phase 10 — Auth Extras
50. `create_otp_verifications_table`
51. `add_google_apple_provider_columns_to_users`
52. `create_password_reset_tokens_table` (Laravel default)

### Phase 11 — Deferred (migrations only, no code)
53. `create_handyman_profiles_table`
54. `create_nanny_profiles_table`
55. `create_locations_table`
56. `create_geo_functions` (SQL)
57. `create_geo_services_table`
58. `create_live_locations_table`
59. `create_profiles_organizations_table`
60. `create_service_orders_table`
61. `create_service_order_events_table`

---

## 10. VERIFICATION CHECKLIST

- [ ] No `wallets`, `wallet_transactions`, `wallet_balances`, `wallet_ledger`, `payouts`, `withdrawals`, `ledger_accounts`, `ledger_entries`, `transfer_idempotency`, `withdrawal_events`, `ledger_reconciliation_flags` tables exist
- [ ] No `payment_providers`, `payment_attempts`, `gateway_webhook_events`, `payment_intents` tables exist
- [ ] No `booking_payments`, `manager_payouts` tables exist
- [ ] No `profiles`, `auth_sessions`, `notifications_mirror`, `notification_deliveries_mirror` tables exist
- [ ] No `driver_profiles`, `agent_profiles`, `vendor_profiles` tables exist
- [ ] `users.role` (via spatie) contains: customer, hotel, agency, creator, admin, superadmin, driver, agent, handyman, nanny, cleaner (deferred)
- [ ] No `vendor`, `dispatch`, `accountant` roles
- [ ] `role_permissions` (spatie) has KYC, currencies, FX, hotel, agency, bus, eSIM, Boxo, listings, admin permissions
- [ ] No wallet/commission/payout permissions exist
- [ ] `app_settings` has no payment keys (see §3.1)
- [ ] All payment-related env vars removed from `.env.example`
- [ ] Hotel bookings use only statuses: requested, held, confirmed, cancelled, expired
- [ ] Bus bookings use only statuses: requested, held, confirmed, cancelled, expired
- [ ] Bus seat holds + hotel room holds use row locks + cron expiry
- [ ] eSIM orders stop at `requested` (no payment)
- [ ] Boxo has no payment logic; miniapps with `payment_type` flagged for review
- [ ] Laravel migrations run without error on fresh MySQL `eatalyft_local`
- [ ] Tests pass (php artisan test)
- [ ] Feature tests for every admin action + role access (403 for other roles)

---

## 11. RISK AREAS — NEED HUMAN REVIEW

1. **`service_role_grants`** — may grant access to deleted services; audit grants
2. **`kyc_tier_limits.service='transfer'`** — references removed wallet transfer; change to `'*'` or remove
3. **`app_settings.integrations_meta.payment_gateway`** — used by frontend config; ensure frontend handles missing
4. **Hotel commission** — `hotel_commission_pct` kept but no payout logic; Stage 6 will implement
5. **Boxo miniapps seed data** — audit for payment-type miniapps; list for decision
6. **eSIM provider** — UNKNOWN until legacy code read; list endpoints/requirements in ARCHITECTURE.md
7. **Deferred tables** — migrations created but no code; ensure FKs don't block kept modules
8. **Sneat Pro license** — copy only needed parts; keep repo private; no license files committed outside project
9. **Agency staff/branch-manager model** — propose in ARCHITECTURE.md before Stage 5

---

**NEXT STEP**: Stage 1 — Base (Sneat layout, role-based menu, design tokens, i18n, currency switcher, health page, users table extensions, spatie permissions, audit log).