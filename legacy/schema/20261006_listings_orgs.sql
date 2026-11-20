-- [FEATURE] Listings/hotels canonical ownership (organizations model)
-- [CHANGES] listings + hotel_properties organization_id/owner_profile_id columns, backfill, owner/admin RLS, conflict log
--
-- Rollback: run 20261006_listings_orgs_reverse.sql (drops ONLY the columns and
-- policies added here; listing/hotel rows survive).
-- Apply: paste into Supabase SQL editor, or
--   psql "$SUPABASE_DB_URL" -v ON_ERROR_STOP=1 -f supabase/migrations/20261006_listings_orgs.sql
--
-- MODEL: listings and hotel_properties hold ONLY listing data (photos, price,
-- location, rooms, availability, contact actions, ratings). Owner person data
-- lives in profiles (joined via owner_profile_id); org membership lives in
-- organizations/memberships (joined via organization_id). owner_uid /
-- manager_uid (Firebase UID text) stay as the write-path keys; the new UUID
-- columns are the canonical joins. No owner name/phone/email columns exist on
-- these tables and none are added.

begin;

-- ── 1. Canonical joins (nullable: legacy rows link on backfill) ──────────────
alter table public.listings add column if not exists organization_id uuid references public.organizations(id) on delete set null;
alter table public.listings add column if not exists owner_profile_id uuid references public.profiles(id) on delete set null;

alter table public.hotel_properties add column if not exists organization_id uuid references public.organizations(id) on delete set null;
alter table public.hotel_properties add column if not exists owner_profile_id uuid references public.profiles(id) on delete set null;

create index if not exists idx_listings_org on public.listings (organization_id) where organization_id is not null;
create index if not exists idx_listings_owner_profile on public.listings (owner_profile_id) where owner_profile_id is not null;
create index if not exists idx_hotels_org on public.hotel_properties (organization_id) where organization_id is not null;

comment on column public.listings.organization_id is 'Owning realtor agency org (organizations.type=realtor_agency). Null = independent realtor.';
comment on column public.listings.owner_profile_id is 'Canonical owner join (profiles.id). owner_uid stays the write-path key.';
comment on column public.hotel_properties.organization_id is 'Owning hotel org (source hotel_properties row from the Phase 3 backfill).';

-- ── 2. Backfill (idempotent: only fills NULL links) ──────────────────────────
-- 2a. Owner profiles via Firebase UID.
update public.listings l
   set owner_profile_id = p.id
  from public.profiles p
 where l.owner_profile_id is null and p.firebase_uid = l.owner_uid;

update public.hotel_properties h
   set owner_profile_id = p.id
  from public.profiles p
 where h.owner_profile_id is null and p.firebase_uid = h.manager_uid;

-- 2b. Hotel org links via the Phase 3 backfill (same source row).
update public.hotel_properties h
   set organization_id = o.id
  from public.organizations o
 where h.organization_id is null
   and o.source_table = 'hotel_properties' and o.source_id = h.id::text;

-- 2c. Realtor org links: owner is a member of exactly one realtor org.
update public.listings l
   set organization_id = m.organization_id
  from public.memberships m
  join public.organizations o on o.id = m.organization_id
 where l.organization_id is null
   and m.profile_id = l.owner_profile_id
   and o.type = 'realtor_agency'
   and not exists (
     select 1 from public.memberships m2
     join public.organizations o2 on o2.id = m2.organization_id
    where m2.profile_id = l.owner_profile_id and o2.type = 'realtor_agency'
      and m2.organization_id <> m.organization_id
   );

-- 2d. Unmatched owners → conflict log for review (never blocks reads).
insert into public.data_merge_conflicts (source_table, source_id, reason, payload)
select 'listings', l.id::text, 'owner_profile_missing',
       jsonb_build_object('owner_uid', l.owner_uid, 'title', l.title)
  from public.listings l
 where l.owner_profile_id is null
on conflict (source_table, source_id, reason) do nothing;

insert into public.data_merge_conflicts (source_table, source_id, reason, payload)
select 'hotel_properties', h.id::text, 'owner_profile_missing',
       jsonb_build_object('manager_uid', h.manager_uid, 'hotel_name', h.name)
  from public.hotel_properties h
 where h.owner_profile_id is null
on conflict (source_table, source_id, reason) do nothing;

-- ── 3. RLS: owners edit own, admins edit all, public reads live ──────────────
-- Existing posture (public live-read; writes via service-role API) is kept.
-- These policies add defense-in-depth for any Supabase-authenticated path:
-- owners (owner_profile_id = auth.uid()) may update/delete their own rows;
-- admins (is_admin) may write all. No authenticated INSERT (creation stays in
-- POST /api/listings + hotel manage APIs with KYC + cap gates).

drop policy if exists listings_owner_write on public.listings;
create policy listings_owner_write on public.listings
  for update to authenticated
  using (owner_profile_id = auth.uid())
  with check (owner_profile_id = auth.uid());

drop policy if exists listings_owner_delete on public.listings;
create policy listings_owner_delete on public.listings
  for delete to authenticated
  using (owner_profile_id = auth.uid());

drop policy if exists listings_admin_write on public.listings;
create policy listings_admin_write on public.listings
  for all to authenticated
  using (public.is_admin())
  with check (public.is_admin());

drop policy if exists hotel_properties_owner_write on public.hotel_properties;
create policy hotel_properties_owner_write on public.hotel_properties
  for update to authenticated
  using (owner_profile_id = auth.uid())
  with check (owner_profile_id = auth.uid());

drop policy if exists hotel_properties_admin_write on public.hotel_properties;
create policy hotel_properties_admin_write on public.hotel_properties
  for all to authenticated
  using (public.is_admin())
  with check (public.is_admin());

-- ── 4. Owner read view (own listings incl. drafts, without leaking others) ───
create or replace view public.v_owner_listings as
select l.*, p.display_name as owner_display_name, p.kyc_status as owner_kyc_status
  from public.listings l
  join public.profiles p on p.id = l.owner_profile_id;

notify pgrst, 'reload schema';

commit;

-- End of migration.
