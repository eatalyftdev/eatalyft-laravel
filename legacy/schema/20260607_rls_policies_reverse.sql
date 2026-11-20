-- ============================================================================
-- REVERSE of supabase/migrations/20260607_rls_policies.sql
-- ============================================================================
-- Drops the RLS policies this forward created and removes the 5 tables from
-- the supabase_realtime publication it added them to. Does NOT disable RLS on
-- live_locations: RLS on that table was enabled by 20260607_live_locations_v2
-- (an earlier file), not by this one.
-- HARD AMBIGUITY (recorded in MIGRATIONS-CONFORMANCE.md):
--   The forward ENABLED RLS on rides, food_orders, parcels, payments and
--   agent_zones. Any policy on a table needs RLS on to be effective, so a
--   truly effective rollback also needs ALTER TABLE ... DISABLE RLS on those
--   5 tables. Doing so is the byte-reverse of the forward (those tables had
--   RLS disabled before this file). It is intentionally commented OUT here
--   because a mis-ordered standalone run would silently open the tables; use
--   it only in a scoped rollback that also drops the later 20260817 policies.
-- ============================================================================

begin;

-- ---- live_locations: only policies THIS file added ----
drop policy if exists "actors can update own location" on public.live_locations;
drop policy if exists "customers can see nearby drivers" on public.live_locations;

-- ---- rides ----
drop policy if exists "customers see own rides"        on public.rides;
drop policy if exists "drivers see assigned rides"     on public.rides;
drop policy if exists "customers create rides"         on public.rides;
drop policy if exists "driver updates own ride"        on public.rides;
drop policy if exists "admins see all rides"           on public.rides;

-- ---- food_orders ----
drop policy if exists "customers see own food orders"  on public.food_orders;
drop policy if exists "vendors see their orders"       on public.food_orders;
drop policy if exists "agents see assigned food orders" on public.food_orders;
drop policy if exists "customers create food orders"   on public.food_orders;
drop policy if exists "admins manage food orders"      on public.food_orders;

-- ---- parcels ----
drop policy if exists "senders see own parcels"        on public.parcels;
drop policy if exists "agents see assigned parcels"    on public.parcels;
drop policy if exists "senders create parcels"         on public.parcels;
drop policy if exists "admins manage parcels"          on public.parcels;

-- ---- payments ----
drop policy if exists "users see own payments"         on public.payments;
drop policy if exists "admins see all payments"        on public.payments;

-- ---- agent_zones ----
drop policy if exists "agents see own zone"            on public.agent_zones;
drop policy if exists "admins manage zones"            on public.agent_zones;

do $$
begin
  alter publication supabase_realtime drop table public.live_locations;
  alter publication supabase_realtime drop table public.rides;
  alter publication supabase_realtime drop table public.food_orders;
  alter publication supabase_realtime drop table public.parcels;
  alter publication supabase_realtime drop table public.payments;
exception when undefined_object or others then
  null;
end $$;

-- -- Byte-reverse of the forward's ENABLE RLS, gated for scoped rollbacks:
-- alter table public.rides         disable row level security;
-- alter table public.food_orders   disable row level security;
-- alter table public.parcels       disable row level security;
-- alter table public.payments      disable row level security;
-- alter table public.agent_zones   disable row level security;

commit;