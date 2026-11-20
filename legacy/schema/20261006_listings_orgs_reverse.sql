-- [FEATURE] Listings/hotels canonical ownership — REVERSE
-- [CHANGES] drops v_owner_listings view, owner/admin policies, organization_id/owner_profile_id columns on listings + hotel_properties
--
-- Rollback note: safe even long after the forward. Drops ONLY what the forward
-- added (DROP ... IF EXISTS). Listing/hotel rows and the public live-read
-- policy survive. After rollback, owner scoping falls back to owner_uid /
-- manager_uid matching in the API routes until the forward is re-applied.

begin;

drop view if exists public.v_owner_listings;

drop policy if exists listings_owner_write on public.listings;
drop policy if exists listings_owner_delete on public.listings;
drop policy if exists listings_admin_write on public.listings;
drop policy if exists hotel_properties_owner_write on public.hotel_properties;
drop policy if exists hotel_properties_admin_write on public.hotel_properties;

alter table public.listings drop column if exists organization_id;
alter table public.listings drop column if exists owner_profile_id;
alter table public.hotel_properties drop column if exists organization_id;
alter table public.hotel_properties drop column if exists owner_profile_id;

notify pgrst, 'reload schema';

commit;

-- End of reverse migration.
