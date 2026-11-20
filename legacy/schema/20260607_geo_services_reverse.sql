-- ============================================================================
-- REVERSE of supabase/migrations/20260607_geo_services.sql
-- ============================================================================
-- Drops the 6 tables this forward created (live_locations v1 shape, rides,
-- food_orders, parcels, payments, agent_zones) and the indexes it added.
-- DO NOT drop the postgis / postgis_topology extensions: the forward only
-- ensured they exist with CREATE EXTENSION IF NOT EXISTS; they are shared
-- infrastructure and may be owned by other migrations / schemas.
-- ORDERING: run after 20260607_live_locations_v2_reverse (which drops the v2
-- live_locations table and its functions) and after 20260607_rls_policies /
-- 20260817 reverses; payments in particular holds the 20260820 UNIQUE
-- constraint which must be dropped first by that file's reverse.
-- ============================================================================

begin;

drop table if exists public.agent_zones;
drop table if exists public.payments;
drop table if exists public.parcels;
drop table if exists public.food_orders;
drop table if exists public.rides;
drop table if exists public.live_locations;

commit;