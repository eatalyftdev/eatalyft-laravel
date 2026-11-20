-- [FEATURE] Bus operations — REVERSE
-- [CHANGES] drops v_ticket_reconciliation view, activity_logs/buses/bus_routes tables (+ triggers/functions), bus_trips/bus_tickets added columns, member-read RLS policies
--
-- Rollback note: safe even long after the forward. Drops ONLY what the forward
-- created (DROP ... IF EXISTS). Pre-existing bus tables and the 20261002 RLS
-- posture survive. After rollback, fleet/route reads and the immutable log are
-- unavailable until the forward is re-applied; Firestore remains the live
-- operational store.

begin;

drop view if exists public.v_ticket_reconciliation;

drop table if exists public.activity_logs;
drop table if exists public.buses;
drop table if exists public.bus_routes;

drop function if exists public.forbid_activity_log_mutation();
drop function if exists public.forbid_ticket_unverify();

-- Member-read policies created by the forward on SURVIVING tables must be
-- removed explicitly (table drops only take their own policies with them).
drop policy if exists bus_trips_member_read on public.bus_trips;
drop policy if exists bus_tickets_member_read on public.bus_tickets;
drop policy if exists bus_tickets_seller_read on public.bus_tickets;

alter table public.bus_trips drop column if exists bus_id;
alter table public.bus_trips drop column if exists route_id;

alter table public.bus_tickets drop column if exists sub_agent_id;
alter table public.bus_tickets drop column if exists verified_by;
alter table public.bus_tickets drop column if exists verified_at;
alter table public.bus_tickets drop column if exists boarded_at;
alter table public.bus_tickets drop column if exists verify_station_id;

notify pgrst, 'reload schema';

commit;

-- End of reverse migration.
