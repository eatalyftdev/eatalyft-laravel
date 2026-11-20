-- ============================================================================
-- REVERSE of supabase/migrations/002_rls.sql  (RLS + role-helper functions)
-- ============================================================================
-- Removes exactly what the forward added: all policies it created, the 5
-- SECURITY DEFINER role-helper functions, and the RLS enablement (returning
-- those 13 tables to the pre-002 state, where RLS was not enabled).
--
-- WARNING: disabling RLS re-opens these tables to the default grant model as
-- they were BEFORE this migration. Run this only as part of a deliberate,
-- scoped rollback that includes the dependent reverses. Policies created by
-- other migrations (004, 20260607_*, 20260817) have their own reverses and are
-- NOT removed here.
-- ============================================================================

begin;

-- ---- Drop the policies created by 002 (exact names from the forward) ----
drop policy if exists "profiles: own read"        on public.profiles;
drop policy if exists "profiles: own update"      on public.profiles;
drop policy if exists "profiles: admin insert"    on public.profiles;
drop policy if exists "profiles: superadmin delete" on public.profiles;

drop policy if exists "own or admin: select" on public.driver_profiles;
drop policy if exists "own or admin: insert" on public.driver_profiles;
drop policy if exists "own or admin: update" on public.driver_profiles;
drop policy if exists "admin delete"         on public.driver_profiles;

drop policy if exists "own or admin: select" on public.agent_profiles;
drop policy if exists "own or admin: insert" on public.agent_profiles;
drop policy if exists "own or admin: update" on public.agent_profiles;
drop policy if exists "admin delete"         on public.agent_profiles;

drop policy if exists "own or admin: select" on public.handyman_profiles;
drop policy if exists "own or admin: insert" on public.handyman_profiles;
drop policy if exists "own or admin: update" on public.handyman_profiles;
drop policy if exists "admin delete"         on public.handyman_profiles;

drop policy if exists "own or admin: select" on public.nanny_profiles;
drop policy if exists "own or admin: insert" on public.nanny_profiles;
drop policy if exists "own or admin: update" on public.nanny_profiles;
drop policy if exists "admin delete"         on public.nanny_profiles;

drop policy if exists "own or admin: select" on public.hotel_profiles;
drop policy if exists "own or admin: insert" on public.hotel_profiles;
drop policy if exists "own or admin: update" on public.hotel_profiles;
drop policy if exists "admin delete"         on public.hotel_profiles;

drop policy if exists "own or admin: select" on public.vendor_profiles;
drop policy if exists "own or admin: insert" on public.vendor_profiles;
drop policy if exists "own or admin: update" on public.vendor_profiles;
drop policy if exists "admin delete"         on public.vendor_profiles;

drop policy if exists "wallets: own read"     on public.wallets;
drop policy if exists "wallets: admin update" on public.wallets;
drop policy if exists "wallets: admin insert" on public.wallets;

drop policy if exists "wallet_tx: own read"          on public.wallet_transactions;
drop policy if exists "wallet_tx: service insert"    on public.wallet_transactions;
drop policy if exists "wallet_tx: no update"         on public.wallet_transactions;
drop policy if exists "wallet_tx: superadmin delete" on public.wallet_transactions;

drop policy if exists "settings: authenticated read" on public.app_settings;
drop policy if exists "settings: admin write"        on public.app_settings;

drop policy if exists "role_permissions: authenticated read" on public.role_permissions;
drop policy if exists "role_permissions: superadmin write"   on public.role_permissions;

drop policy if exists "sessions: own read"   on public.auth_sessions;
drop policy if exists "sessions: own insert" on public.auth_sessions;
drop policy if exists "sessions: own delete" on public.auth_sessions;

drop policy if exists "audit: admin read"        on public.audit_logs;
drop policy if exists "audit: service insert"    on public.audit_logs;
drop policy if exists "audit: no update or delete" on public.audit_logs;

-- ---- Drop the 5 role-helper functions created by 002 ----
drop function if exists public.get_my_role();
drop function if exists public.is_admin();
drop function if exists public.is_superadmin();
drop function if exists public.is_finance_role();
drop function if exists public.has_permission(text);

-- ---- Return RLS to the pre-002 state (RLS was OFF here before 002) ----
alter table public.profiles             disable row level security;
alter table public.driver_profiles      disable row level security;
alter table public.agent_profiles       disable row level security;
alter table public.handyman_profiles    disable row level security;
alter table public.nanny_profiles       disable row level security;
alter table public.hotel_profiles       disable row level security;
alter table public.vendor_profiles      disable row level security;
alter table public.wallets              disable row level security;
alter table public.wallet_transactions  disable row level security;
alter table public.app_settings         disable row level security;
alter table public.role_permissions     disable row level security;
alter table public.auth_sessions        disable row level security;
alter table public.audit_logs           disable row level security;

commit;