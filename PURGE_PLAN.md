# PURGE PLAN — Eatalyft Laravel 12 Rebuild

**Source**: Legacy Supabase schema (`legacy/schema/*.sql`)  
**Goal**: Remove Rides, Food, Deliveries, Vendors, ALL payments. Keep Agencies, Hotels, eSIMs, Boxo, Auth, Roles, KYC, Notifications, Currencies, Audit Logs.

---

## 1. DATABASE TABLES — BY SERVICE

### 1.1 KEEP (recreate as Laravel migrations)

| Table | Source Migration | Notes |
|-------|------------------|-------|
| `profiles` | 001_schema.sql | Core auth profile; keep all columns |
| `role_permissions` | 001_schema.sql | RBAC matrix |
| `auth_sessions` | 001_schema.sql | Session log |
| `audit_logs` | 001_schema.sql | Audit trail |
| `app_settings` | 001_schema.sql | **Filter**: keep only non-payment keys (see §3) |
| `currencies` | 20260920_multi_currency.sql | ISO 4217 catalog |
| `exchange_rates` | 20260920_multi_currency.sql | Live + pegged rates |
| `exchange_rate_history` | 20260920_multi_currency.sql | Append-only audit |
| `fx_margins` | 20260920_multi_currency.sql | Spread/fee config |
| `country_currency_map` | 20260920_multi_currency.sql | Country → default currency |
| `user_currency_preferences` | 20260920_multi_currency.sql | Per-user display currency |
| `notifications_mirror` | 20260921_notifications.sql | User notifications (Firestore mirror) |
| `notification_deliveries_mirror` | 20260921_notifications.sql | Delivery tracking |
| `kyc_profiles` | 20260920_kyc_didit.sql | KYC status per user |
| `kyc_sessions` | 20260920_kyc_didit.sql | Didit session records |
| `kyc_results` | 20260920_kyc_didit.sql | Encrypted PII flags |
| `kyc_webhook_events` | 20260920_kyc_didit.sql | Idempotent webhook log |
| `kyc_review_actions` | 20260920_kyc_didit.sql | Admin decisions |
| `kyc_tier_limits` | 20260920_kyc_didit.sql | Limits per tier/currency/service |
| `integration_credentials` | 20260920_kyc_didit.sql | Encrypted vault (Didit, FX, mail, etc.) |
| `hotel_properties` | 20260920_hotels.sql | Hotel catalog |
| `hotel_room_types` | 20260920_hotels.sql | Room types per property |
| `hotel_rate_plans` | 20260920_hotels.sql | Seasonal pricing |
| `hotel_availability` | 20260920_hotels.sql | Per-night inventory |
| `booking_holds` | 20260920_hotels.sql | 15-min reservation holds |
| `hotel_bookings` | 20260920_hotels.sql | Confirmed bookings (idempotent) |
| `hotel_cancellation_policies` | 20260920_hotels.sql | Free-cancel rules |
| `hotel_reviews` | 20260920_hotels.sql | Post-stay reviews |
| `agencies` | 20261002_bus_agency_schema.sql | **Only agencies table** |
| `agency_logos` | 20261018_agency_logos.sql | Logo storage |
| `service_role_grants` | 20261012_service_role_grants.sql | Role→service access |
| `boxo_consents` | 20261022_boxo_consents.sql | Miniapp OAuth consents |
| `boxo_miniapps` | 20261022_boxo_miniapps.sql | Miniapp catalog |
| `boxo_webhook_events` | 20261022_boxo_webhook_events.sql | Boxo webhook log |
| `locations` | 20260920_locations.sql | Geo locations |
| `geo_functions` | 20260607_geo_functions.sql | PostGIS helpers |
| `geo_services` | 20260607_geo_services.sql | Geo service areas |
| `live_locations_v2` | 20260607_live_locations_v2.sql | Real-time tracking |
| `listings` | 20260920_listings.sql | Generic listings (hotels, etc.) |
| `listings_phase4` | 20260922_listings_phase4.sql | Extended listings |
| `listings_orgs` | 20261006_listings_orgs.sql | Org-owned listings |
| `profiles_organizations` | 20261004_profiles_organizations.sql | Profile→org links |
| `otp_signup` | 20260920_otp_signup.sql | OTP auth flow |
| `google_apple_provider_columns` | 20261000_google_apple_provider_columns.sql | Social auth |
| `must_change_password` | 20260920_must_change_password.sql | Force password reset |
| `user_sessions` | 20261010_user_sessions.sql | Extended sessions |
| `email_deliveries` | 20261028_email_deliveries.sql | Email tracking |

### 1.2 MODIFY (keep table, drop columns/rows)

| Table | Action |
|-------|--------|
| `profiles` | Drop `driver_profiles`, `agent_profiles`, `handyman_profiles`, `nanny_profiles`, `vendor_profiles` FKs; keep `hotel_profiles`; drop `role` enum values: `driver`, `agent`, `handyman`, `nanny`, `cleaner`, `vendor`, `dispatch`, `accountant` |
| `app_settings` | **DELETE** payment keys (see §3) |
| `role_permissions` | **DELETE** rows for removed roles (`driver`, `agent`, `handyman`, `nanny`, `cleaner`, `vendor`, `dispatch`, `accountant`) and payment permissions (`view_wallets`, `manage_commissions`, `currencies:view`, `currencies:manage`, `rates:override`, `fx_margins:manage`, `kyc:view`, `kyc:review`, `kyc:configure`) |
| `hotel_bookings` | **DROP** `commission_minor`, `payment_status` columns (payment Phase 2) |
| `hotel_properties` | **DROP** `manager_payouts` FK (removed) |

### 1.3 REMOVE (drop entirely — payments & deleted services)

| Table | Source Migration | Service |
|-------|------------------|---------|
| `wallets` | 001_schema.sql | Payments |
| `wallet_transactions` | 001_schema.sql | Payments |
| `wallet_balances` | 20260920_multi_currency.sql | Payments |
| `driver_profiles` | 001_schema.sql | Rides |
| `agent_profiles` | 001_schema.sql | Deliveries |
| `handyman_profiles` | 001_schema.sql | Handyman (removed) |
| `nanny_profiles` | 001_schema.sql | Nanny/Cleaner (removed) |
| `vendor_profiles` | 001_schema.sql | Vendors/Food |
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
| `notchpay_multiservice` cols on `payments` | 20261001_notchpay_multiservice.sql | Payments |
| `bus_branches` | 20261002_bus_agency_schema.sql | Bus (removed) |
| `bus_trips` | 20261002_bus_agency_schema.sql | Bus (removed) |
| `bus_tickets` | 20261002_bus_agency_schema.sql | Bus (removed) |
| `bus_bookings` | 20261002_bus_agency_schema.sql | Bus (removed) |
| `booking_payments` | 20260920_hotels.sql | Payments (hotel) |
| `manager_payouts` | 20260920_hotels.sql | Payments (hotel) |
| `rls_policies` | 20260607_rls_policies.sql | Supabase-specific (not needed) |
| `webhook_rls_realtime` | 20260817_webhook_rls_realtime.sql | Supabase-specific |

---

## 2. ENUMS — MODIFY

| Enum | Current Values | Keep | Remove |
|------|----------------|------|--------|
| `user_role` | customer, driver, agent, handyman, nanny, cleaner, hotel, vendor, agency, creator, dispatch, accountant, admin, superadmin | customer, hotel, agency, creator, admin, superadmin | driver, agent, handyman, nanny, cleaner, vendor, dispatch, accountant |
| `account_status` | active, pending, suspended, inactive | ALL | — |
| `tx_type` | credit, debit | **REMOVE ENUM** (payments) | credit, debit |
| `service_type` | ride, food, parcel, handyman, nanny, bus, hotel | hotel | ride, food, parcel, handyman, nanny, bus |
| `kyc_status` | NOT_STARTED, PENDING, IN_REVIEW, VERIFIED, REJECTED, EXPIRED | ALL | — |

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
feature_food_delivery, feature_ride_hailing, feature_parcel_delivery, feature_bus_booking, feature_handyman, feature_nanny, feature_cleaner, feature_loyalty_program, feature_referral_program
integrations_meta.payment_gateway
```

### 3.2 KEEP (shared/platform)

```
frontend_app_name, frontend_support_email, frontend_currency, frontend_country_code, frontend_app_version
maintenance_enabled, maintenance_message
launch_enabled, launch_title, launch_subtitle
tax_enabled, tax_rate
feature_hotel_booking, feature_whatsapp_notify, feature_email_notify, feature_push_notify
integrations_meta (minus payment_gateway)
hotel_commission_pct
fx_pivot_currency, fx_refresh_interval_seconds, fx_stale_after_seconds, fx_deviation_threshold_percent, fx_quote_ttl_seconds, fx_major_pairs
kyc_max_attempts, kyc_cooldown_hours, kyc_retention_days, kyc_reverify_on_expire, kyc_workflow_tier1, kyc_workflow_tier2
```

---

## 4. CODE DEPENDENCIES — KEPT MODULES → DELETED PAYMENTS

| Kept Module | Dependency on Payments | Fix Strategy |
|-------------|------------------------|--------------|
| `hotel_bookings` | `booking_payments`, `manager_payouts`, `commission_minor`, `payment_status` | **Stub**: replace with `PaymentPlaceholder` throwing "Payments not enabled — Phase 2" |
| `hotel_properties` | `manager_payouts` FK | **Drop FK**; payouts recreated in Phase 2 |
| `kyc_tier_limits` | `service` column references `wallet`, `transfer` | **Keep** `service='*'` default; remove `transfer` row |
| `role_permissions` | `view_wallets`, `manage_commissions`, `currencies:*`, `fx_margins:manage` | **Delete** those permission rows |
| `app_settings` | Payment keys (see §3.1) | **Delete** keys |
| `profiles` | `wallets` FK, `wallet_transactions` | **Drop** wallet FKs; wallet recreated in Phase 2 |
| `wallets` table | Referenced by `ledger_accounts`, `wallet_transactions`, `wallet_balances`, `wallet_ledger`, `payouts`, `withdrawals` | **Drop entire table** (Phase 2) |
| `currencies` | Referenced by `wallets.currency_code`, `wallet_balances.currency_code`, `wallet_transactions.currency_code`, `payments.currency_code`, `exchange_rates`, `fx_margins`, `country_currency_map`, `user_currency_preferences` | **Keep** currencies; drop FKs to deleted tables |
| `exchange_rates` | Referenced by payment conversion logic | **Keep** (used for display/pricing) |
| `user_currency_preferences` | Referenced by wallet display | **Keep** (used for hotel pricing display) |

---

## 5. ENV VARIABLES — PURGE

| Variable | Action |
|----------|--------|
| `NOTCHPAY_*` | DELETE |
| `MESOMB_*` | DELETE |
| `FAPSHI_*` | DELETE |
| `WALLET_*` | DELETE |
| `LEDGER_*` | DELETE |
| `PAYMENT_*` | DELETE |
| `REDIS_*` | Keep (staging/prod only; env-driven) |
| `SUPABASE_*` | DELETE (migrated to Laravel) |
| `FIREBASE_*` | DELETE |
| `KYC_DIDIT_*` | KEEP |
| `MAILGUN_*`, `WHATSAPP_*`, `FCM_*` | KEEP |
| `GOOGLE_MAPS_*`, `AWS_*` | KEEP |

---

## 6. ROUTES / CONTROLLERS / COMPONENTS — PURGE GROUPS

| Group | Files to Delete |
|-------|-----------------|
| **Rides** | `app/Http/Controllers/Ride*`, `app/Models/Ride*`, `routes/ride.php`, `resources/js/pages/ride*`, `database/migrations/*ride*`, `database/seeders/*Ride*` |
| **Food** | `app/Http/Controllers/Food*`, `app/Models/Food*`, `app/Models/Restaurant*`, `app/Models/Menu*`, `routes/food.php`, `resources/js/pages/food*` |
| **Deliveries/Parcel** | `app/Http/Controllers/Parcel*`, `app/Http/Controllers/Delivery*`, `app/Models/Parcel*`, `app/Models/Courier*`, `routes/parcel.php` |
| **Vendors** | `app/Http/Controllers/Vendor*`, `app/Models/Vendor*`, `app/Models/Shop*`, `routes/vendor.php` |
| **Payments** | `app/Http/Controllers/Payment*`, `app/Http/Controllers/Wallet*`, `app/Http/Controllers/Ledger*`, `app/Http/Controllers/Payout*`, `app/Http/Controllers/Withdrawal*`, `app/Models/Wallet*`, `app/Models/Ledger*`, `app/Models/Payout*`, `app/Services/Payment*`, `app/Services/Wallet*`, `app/Services/NotchPay*`, `app/Services/Mesomb*`, `routes/payment.php`, `routes/wallet.php`, `database/migrations/*wallet*`, `database/migrations/*payment*`, `database/migrations/*ledger*`, `database/migrations/*payout*`, `database/migrations/*withdrawal*`, `database/seeders/*Payment*`, `database/seeders/*Wallet*`, `resources/js/pages/wallet*`, `resources/js/pages/payment*` |
| **Bus** | `app/Http/Controllers/Bus*`, `app/Models/Bus*`, `app/Models/Trip*`, `app/Models/Ticket*`, `app/Models/Branch*`, `routes/bus.php` (but keep `agencies` model) |

---

## 7. MIGRATION ORDER (Laravel)

### Phase 1 — Core (run first)
1. `create_profiles_table` (auth, roles, KYC fields)
2. `create_role_permissions_table`
3. `create_auth_sessions_table`
4. `create_audit_logs_table`
5. `create_app_settings_table` (filtered keys only)

### Phase 2 — Currencies
6. `create_currencies_table`
7. `create_exchange_rates_table`
8. `create_exchange_rate_history_table`
9. `create_fx_margins_table`
10. `create_country_currency_map_table`
11. `create_user_currency_preferences_table`

### Phase 3 — Notifications
12. `create_notifications_mirror_table`
13. `create_notification_deliveries_mirror_table`

### Phase 4 — KYC
14. `create_kyc_profiles_table`
15. `create_kyc_sessions_table`
16. `create_kyc_results_table`
17. `create_kyc_webhook_events_table`
18. `create_kyc_review_actions_table`
19. `create_kyc_tier_limits_table`
20. `create_integration_credentials_table`

### Phase 5 — Hotels
21. `create_hotel_properties_table`
22. `create_hotel_room_types_table`
23. `create_hotel_rate_plans_table`
24. `create_hotel_availability_table`
25. `create_booking_holds_table`
26. `create_hotel_bookings_table` (no payment cols)
27. `create_hotel_cancellation_policies_table`
28. `create_hotel_reviews_table`

### Phase 6 — Agencies & Boxo
29. `create_agencies_table`
30. `create_agency_logos_table`
31. `create_service_role_grants_table`
32. `create_boxo_consents_table`
33. `create_boxo_miniapps_table`
34. `create_boxo_webhook_events_table`

### Phase 7 — Geo & Listings
35. `create_locations_table`
36. `create_geo_functions` (SQL)
37. `create_geo_services_table`
38. `create_live_locations_table`
39. `create_listings_table`
40. `create_listings_phase4_table`
41. `create_listings_orgs_table`
42. `create_profiles_organizations_table`

### Phase 8 — Auth Extras
43. `create_otp_signup_table`
44. `add_google_apple_provider_columns`
45. `create_must_change_password_table`
46. `create_user_sessions_table`
47. `create_email_deliveries_table`

---

## 8. VERIFICATION CHECKLIST

- [ ] No `wallets`, `wallet_transactions`, `wallet_balances`, `wallet_ledger`, `payouts`, `withdrawals`, `ledger_accounts`, `ledger_entries`, `transfer_idempotency`, `withdrawal_events`, `ledger_reconciliation_flags` tables exist
- [ ] No `driver_profiles`, `agent_profiles`, `handyman_profiles`, `nanny_profiles`, `vendor_profiles` tables exist
- [ ] No `bus_branches`, `bus_trips`, `bus_tickets`, `bus_bookings` tables exist
- [ ] No `booking_payments`, `manager_payouts` tables exist
- [ ] No `payment_providers`, `payment_attempts`, `gateway_webhook_events`, `payment_intents` tables exist
- [ ] `user_role` enum only contains: `customer`, `hotel`, `agency`, `creator`, `admin`, `superadmin`
- [ ] `service_type` enum only contains: `hotel`
- [ ] `role_permissions` has no rows for deleted roles or payment permissions
- [ ] `app_settings` has no payment keys (see §3.1)
- [ ] All payment-related env vars removed from `.env.example`
- [ ] `PaymentPlaceholder` interface exists and throws on all methods
- [ ] Kept modules (hotels, agencies, KYC, currencies, notifications) have no import/use of deleted payment classes
- [ ] Laravel migrations run without error on fresh MySQL `eatalyft_local`
- [ ] Tests pass (php artisan test)

---

## 9. RISK AREAS — NEED HUMAN REVIEW

1. **`profiles_organizations` / `listings_orgs`** — may reference vendor/bus agencies; verify FK targets
2. **`service_role_grants`** — may grant access to deleted services; audit grants
3. **`kyc_tier_limits.service='transfer'`** — references removed wallet transfer; change to `'*'` or remove
4. **`app_settings.integrations_meta.payment_gateway`** — used by frontend config; ensure frontend handles missing
5. **Hotel commission** — `hotel_commission_pct` kept but no payout logic; Phase 2 will implement
6. **Boxo miniapps** — may include payment miniapps; audit `boxo_miniapps` seed data
7. **`locations` / `geo_*`** — used by rides/food/deliveries; verify hotel/agency still needs them
8. **RLS policies** — all Supabase-specific; not needed in Laravel (replaced by Policies/Gates)

---

**NEXT STEP**: Await approval. Then execute deletions in commits per service group (Rides → Food → Deliveries → Vendors → Payments).