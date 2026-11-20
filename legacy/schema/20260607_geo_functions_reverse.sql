-- ============================================================================
-- REVERSE of supabase/migrations/20260607_geo_functions.sql
-- ============================================================================
-- Drops the 6 functions the forward created (double precision / uuid
-- signatures as declared):
--   find_nearest_driver, find_nearest_delivery_agent, find_nearest_agent,
--   update_my_location, assign_ride_to_driver, assign_food_order
-- NOTE: 20260607_live_locations_v2 later re-CREATEs update_my_location,
-- find_nearest_driver and find_nearest_delivery_agent with numeric
-- signatures (overloads, not replacements). Run in newest-first order:
-- live_locations_v2_reverse drops the numeric overloads, then this file
-- drops the original double-precision ones.
-- ============================================================================

begin;

drop function if exists public.find_nearest_driver(double precision, double precision, text, numeric);
drop function if exists public.find_nearest_delivery_agent(double precision, double precision, numeric);
drop function if exists public.find_nearest_agent(double precision, double precision, numeric);
drop function if exists public.update_my_location(double precision, double precision, numeric, numeric);
drop function if exists public.assign_ride_to_driver(uuid, uuid);
drop function if exists public.assign_food_order(uuid, uuid);

commit;