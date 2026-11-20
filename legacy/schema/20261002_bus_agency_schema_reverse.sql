-- ============================================================================
-- REVERSE of supabase/migrations/20261002_bus_agency_schema.sql
-- ============================================================================
-- Drops, in dependency order: bus_tickets → bus_bookings → bus_trips →
-- bus_branches → agencies. Removes the realtime publication entries first.
--
-- ⚠️  DESTRUCTIVE: this deletes every bus/agency row. Take a backup first.
--     Per MIGRATIONS-PROTOCOL.md Rule 1 this file is a revert path only — it
--     is NEVER applied forward and is excluded from the byte-true master by
--     scripts/build-master-schema.ps1 (which skips any name containing
--     `_reverse`).
--
-- Idempotent: DROP ... IF EXISTS / guarded DO blocks.
-- ============================================================================

begin;

-- ── 0. Realtime publication entries ─────────────────────────────────────────
do $$
begin
  if exists (select 1 from pg_publication where pubname = 'supabase_realtime') then
    begin
      alter publication supabase_realtime drop table public.bus_tickets;
    exception when undefined_object then null; end;
    begin
      alter publication supabase_realtime drop table public.bus_trips;
    exception when undefined_object then null; end;
  end if;
end $$;

-- ── 1. Sales tables (depend on bus_trips) ──────────────────────────────────
drop table if exists public.bus_tickets;
drop table if exists public.bus_bookings;

-- ── 2. Operations ──────────────────────────────────────────────────────────
drop table if exists public.bus_trips;

-- ── 3. Tenants ─────────────────────────────────────────────────────────────
drop table if exists public.bus_branches;
drop table if exists public.agencies;

notify pgrst, 'reload schema';

commit;

-- End of reverse migration.
