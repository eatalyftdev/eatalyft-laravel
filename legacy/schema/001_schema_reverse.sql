-- ============================================================================
-- REVERSE of supabase/migrations/001_schema.sql  (core schema baseline)
-- ============================================================================
-- Drops exactly what the forward added: 4 enums, 13 tables (+inline indexes),
-- 2 helper functions, 4 triggers. WARNING: this is the baseline teardown - it
-- destroys ALL rows created since the baseline (users, wallets, settings,
-- role seeds). Only run as part of a full fresh-project nuke; you cannot
-- recover data after this.
--
-- Reverses all 8 core sections of 001. No object created by a LATER
-- migration is touched here (those each have their own reverse).
-- ============================================================================

begin;

-- Triggers first (depend on functions + tables)
drop trigger if exists on_auth_user_created on auth.users;
drop trigger if exists trg_profiles_ts  on public.profiles;
drop trigger if exists trg_wallets_ts   on public.wallets;
drop trigger if exists trg_settings_ts  on public.app_settings;

-- Functions next
drop function if exists public.handle_new_user();
drop function if exists public.update_updated_at();

-- Tables child-before-parent (FK order), indexes drop with tables
drop table if exists public.wallet_transactions;
drop table if exists public.audit_logs;
drop table if exists public.auth_sessions;
drop table if exists public.role_permissions;
drop table if exists public.app_settings;
drop table if exists public.wallets;
drop table if exists public.vendor_profiles;
drop table if exists public.hotel_profiles;
drop table if exists public.nanny_profiles;
drop table if exists public.handyman_profiles;
drop table if exists public.agent_profiles;
drop table if exists public.driver_profiles;
drop table if exists public.profiles;

-- Enum types last (nothing may reference them)
drop type if exists public.service_type;
drop type if exists public.tx_type;
drop type if exists public.account_status;
drop type if exists public.user_role;

commit;