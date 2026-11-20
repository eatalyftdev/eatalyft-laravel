-- [FEATURE] Canonical profiles + organizations — REVERSE
-- [CHANGES] drops v_organization_members/v_sub_agents views, agency_managers/sub_agents/station_staff/hotel_owners/realtors/organizations/memberships/data_merge_conflicts tables, profiles canonical columns
--
-- Rollback note: safe even long after the forward. Drops ONLY what the forward
-- created (DROP ... IF EXISTS). Pre-existing profiles columns and all other
-- tables are untouched. Backfilled organization rows vanish with their tables
-- (they were mirrors of agencies/hotel_properties, which survive). After
-- rollback, bus/hotel panels fall back to direct agencies/hotel_properties
-- reads until the forward is re-applied.

begin;

drop view if exists public.v_sub_agents;
drop view if exists public.v_organization_members;

drop table if exists public.data_merge_conflicts;
drop table if exists public.memberships;
drop table if exists public.organizations;
drop table if exists public.realtors;
drop table if exists public.hotel_owners;
drop table if exists public.station_staff;
drop table if exists public.sub_agents;
drop table if exists public.agency_managers;

alter table public.profiles drop column if exists kyc_status;
alter table public.profiles drop column if exists kyc_tier;
alter table public.profiles drop column if exists country;
alter table public.profiles drop column if exists preferred_currency;
alter table public.profiles drop column if exists address;

alter table public.profiles drop constraint if exists profiles_kyc_status_check;
alter table public.profiles drop constraint if exists profiles_kyc_tier_check;

notify pgrst, 'reload schema';

commit;

-- End of reverse migration.
