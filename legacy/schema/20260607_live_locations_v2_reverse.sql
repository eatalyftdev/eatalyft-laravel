-- ============================================================================
-- REVERSE of supabase/migrations/20260607_live_locations_v2.sql
-- ============================================================================
-- Drops what v2 added and leaves the build in the run-order state as follows:
--   * Drops the v2 table live_locations (user_id text PK shape) + its indexes
--     and the two v2 policies (ride along with the table).
--   * Drops the four functions v2 created/redefined (numeric signatures).
--   * Removes live_locations from the supabase_realtime publication.
-- PARTIAL-ROLLBACK REMAINDER (ambiguous, per protocol):
--   The forward itself executed DROP TABLE live_locations CASCADE, destroying
--   the v1 table from 20260607_geo_services. This reverse CANNOT recreate v1
--   (that is geo_services forward's definition, not added by this file). In a
--   full rollback, re-apply 20260607_geo_services forward afterwards to
--   restore v1 + its double-precision update_my_location. If you stop here,
--   live_locations is missing entirely until geo_services forward is re-run.
-- ============================================================================

begin;

-- ---- Functions first (they reference the table) ----
drop function if exists public.find_all_online_fleet(numeric, numeric, numeric, numeric);
drop function if exists public.update_my_location(numeric, numeric, numeric, numeric);
drop function if exists public.find_nearest_driver(numeric, numeric, text, numeric);
drop function if exists public.find_nearest_delivery_agent(numeric, numeric, numeric);

-- ---- Remove from realtime publication (idempotent guard) ----
do $$
begin
  alter publication supabase_realtime drop table live_locations;
exception when undefined_object or others then
  null;
end $$;

-- ---- v2 table (drops with its indexes and RLS policies) ----
drop table if exists public.live_locations;

commit;