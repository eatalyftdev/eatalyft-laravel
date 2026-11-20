-- [FEATURE] Canonical profiles + organizations (single source of truth)
-- [CHANGES] profiles canonical columns, agency_managers/sub_agents/station_staff/hotel_owners/realtors tables, organizations + memberships, data_merge_conflicts, backfill, views, RLS
--
-- Rollback: run 20261004_profiles_organizations_reverse.sql (drops ONLY the
-- tables/views/columns created here; backfilled organization rows vanish with
-- their tables; pre-existing data is never touched).
-- Apply: paste into Supabase SQL editor, or
--   psql "$SUPABASE_DB_URL" -v ON_ERROR_STOP=1 -f supabase/migrations/20261004_profiles_organizations.sql
--
-- AUDIT NOTE (why this is additive, not a rewrite):
--  Supabase role tables (driver/agent/handyman/nanny/hotel/vendor_profiles)
--  already FK to profiles(id) with NO copied name/phone/email columns — clean.
--  parcels reference auth.users UUIDs (FK-style); recipient_name/phone are
--  shipment data, not profile copies. agencies.phone/email are ORG-level
--  contact details, not person copies. bus_tickets.customer_* are intentional
--  point-of-sale snapshots for manifests/reconciliation. Firestore role
--  collections (riders/drivers/…) are app-level: rule is users/{uid} holds
--  canonical person fields, role docs hold role-only fields (see ARCHITECTURE).
--  What was genuinely missing (added here): kyc/country/currency/address on
--  profiles; manager/sub-agent/station/hotel-owner/realtor role tables;
--  organizations + memberships; conflict log; read views.

begin;

-- ── 1. Canonical person columns on profiles (shared by every panel) ─────────
alter table public.profiles add column if not exists kyc_status text not null default 'unverified';
alter table public.profiles add column if not exists kyc_tier integer not null default 0;
alter table public.profiles add column if not exists country char(2) not null default 'CM';
alter table public.profiles add column if not exists preferred_currency text not null default 'XAF';
alter table public.profiles add column if not exists address text;

do $$
begin
  if not exists (
    select 1 from pg_constraint
    where conname = 'profiles_kyc_status_check' and connamespace = 'public'::regnamespace
  ) then
    alter table public.profiles
      add constraint profiles_kyc_status_check
      check (kyc_status in ('unverified', 'pending', 'verified', 'rejected'));
  end if;
  if not exists (
    select 1 from pg_constraint
    where conname = 'profiles_kyc_tier_check' and connamespace = 'public'::regnamespace
  ) then
    alter table public.profiles
      add constraint profiles_kyc_tier_check
      check (kyc_tier between 0 and 2);
  end if;
end $$;

comment on column public.profiles.kyc_status is 'Canonical KYC status. Role tables MUST NOT copy it — join profiles.';
comment on column public.profiles.preferred_currency is 'Display currency. Money stays in XAF minor units; FX converts at render.';

-- ── 2. Role tables: role-only fields + FK to profiles. No copied columns. ────
create table if not exists public.agency_managers (
  id          uuid primary key default gen_random_uuid(),
  profile_id  uuid not null unique references public.profiles(id) on delete cascade,
  agency_id   text references public.agencies(id) on delete set null,
  permissions jsonb not null default '{}',
  is_active   boolean not null default true,
  created_at  timestamptz not null default now(),
  updated_at  timestamptz not null default now()
);

create table if not exists public.sub_agents (
  id              uuid primary key default gen_random_uuid(),
  profile_id      uuid not null references public.profiles(id) on delete cascade,
  agency_id       text not null references public.agencies(id) on delete cascade,
  station_id      text references public.bus_branches(id) on delete set null,
  commission_rate numeric(5,4) not null default 0 check (commission_rate >= 0 and commission_rate <= 1),
  is_active       boolean not null default true,
  suspended_at    timestamptz,
  created_at      timestamptz not null default now(),
  updated_at      timestamptz not null default now(),
  constraint sub_agents_agency_profile_unique unique (agency_id, profile_id)
);

create table if not exists public.station_staff (
  id         uuid primary key default gen_random_uuid(),
  profile_id uuid not null references public.profiles(id) on delete cascade,
  agency_id  text not null references public.agencies(id) on delete cascade,
  station_id text references public.bus_branches(id) on delete set null,
  staff_role text not null default 'verifier' check (staff_role in ('verifier', 'cashier', 'supervisor')),
  is_active  boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  constraint station_staff_station_profile_unique unique (station_id, profile_id)
);

create table if not exists public.hotel_owners (
  id         uuid primary key default gen_random_uuid(),
  profile_id uuid not null unique references public.profiles(id) on delete cascade,
  is_active  boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.realtors (
  id             uuid primary key default gen_random_uuid(),
  profile_id     uuid not null unique references public.profiles(id) on delete cascade,
  license_number text,
  is_verified    boolean not null default false,
  is_active      boolean not null default true,
  created_at     timestamptz not null default now(),
  updated_at     timestamptz not null default now()
);

-- ── 3. Organizations (one table for every org type) + memberships ────────────
create table if not exists public.organizations (
  id               uuid primary key default gen_random_uuid(),
  type             text not null check (type in ('bus_agency', 'hotel', 'realtor_agency', 'vendor')),
  name             text not null,
  owner_profile_id uuid references public.profiles(id) on delete set null,
  source_table     text,
  source_id        text,
  is_active        boolean not null default true,
  created_at       timestamptz not null default now(),
  updated_at       timestamptz not null default now(),
  constraint organizations_source_unique unique (source_table, source_id)
);

create table if not exists public.memberships (
  id              uuid primary key default gen_random_uuid(),
  organization_id uuid not null references public.organizations(id) on delete cascade,
  profile_id      uuid not null references public.profiles(id) on delete cascade,
  role            text not null default 'member'
                  check (role in ('owner', 'manager', 'sub_agent', 'staff', 'member')),
  created_at      timestamptz not null default now(),
  constraint memberships_org_profile_unique unique (organization_id, profile_id)
);

create index if not exists idx_memberships_profile on public.memberships (profile_id);
create index if not exists idx_memberships_org on public.memberships (organization_id);
create index if not exists idx_sub_agents_agency on public.sub_agents (agency_id, is_active);
create index if not exists idx_station_staff_station on public.station_staff (station_id, is_active);

comment on table public.organizations is 'Single org table (bus agencies, hotels, realtor agencies, vendors). Listings reference orgs; they never copy owner details.';
comment on table public.memberships is 'Owner + members of an organization. Panels scope reads through this table.';

-- ── 4. Merge-conflict log (dedupe by phone/email/user id lands here) ─────────
create table if not exists public.data_merge_conflicts (
  id           uuid primary key default gen_random_uuid(),
  source_table text not null,
  source_id    text not null,
  reason       text not null,
  payload      jsonb not null default '{}',
  resolved     boolean not null default false,
  created_at   timestamptz not null default now(),
  constraint data_merge_conflicts_unique unique (source_table, source_id, reason)
);

comment on table public.data_merge_conflicts is 'Backfill/dedupe conflicts for human review. Resolved in admin; never auto-deleted.';

-- ── 5. Backfill: existing agencies + hotels become organizations ─────────────
-- Idempotent: ON CONFLICT DO NOTHING on the source-unique key.
insert into public.organizations (type, name, is_active, source_table, source_id)
select 'bus_agency', coalesce(business_name, legal_name, slug, id), coalesce(is_active, true), 'agencies', id
  from public.agencies
on conflict (source_table, source_id) do nothing;

insert into public.organizations (type, name, is_active, source_table, source_id)
select 'hotel', name, (status <> 'rejected'), 'hotel_properties', id::text
  from public.hotel_properties
on conflict (source_table, source_id) do nothing;

-- Owner linking where a matching profile exists (Firebase UID → profiles).
-- Hotels whose manager_uid matches NO profile are logged for review.
-- NOTE: three separate statements (not one chained CTE): all parts of a
-- single WITH share one snapshot, so the membership insert re-joins the
-- source instead of reading the just-updated owner column.
update public.organizations o
   set owner_profile_id = p.id, updated_at = now()
  from public.hotel_properties h
  join public.profiles p on p.firebase_uid = h.manager_uid
 where o.source_table = 'hotel_properties'
   and o.source_id = h.id::text
   and o.owner_profile_id is null;

insert into public.memberships (organization_id, profile_id, role)
select o.id, p.id, 'owner'
  from public.organizations o
  join public.hotel_properties h
    on o.source_table = 'hotel_properties' and o.source_id = h.id::text
  join public.profiles p on p.firebase_uid = h.manager_uid
on conflict (organization_id, profile_id) do nothing;

insert into public.data_merge_conflicts (source_table, source_id, reason, payload)
select 'hotel_properties', h.id::text, 'owner_profile_missing',
       jsonb_build_object('manager_uid', h.manager_uid, 'hotel_name', h.name)
  from public.hotel_properties h
 where not exists (select 1 from public.profiles p where p.firebase_uid = h.manager_uid)
on conflict (source_table, source_id, reason) do nothing;

-- ── 6. Read views (panels join through these; no hand-syncing) ──────────────
create or replace view public.v_organization_members as
select o.id as organization_id, o.type as organization_type, o.name as organization_name,
       m.role, p.id as profile_id, p.display_name, p.first_name, p.last_name,
       p.phone, p.photo_url, p.kyc_status, p.kyc_tier
  from public.memberships m
  join public.organizations o on o.id = m.organization_id
  join public.profiles p on p.id = m.profile_id;

create or replace view public.v_sub_agents as
select s.id as sub_agent_id, s.agency_id, a.business_name as agency_name,
       s.station_id, b.name as station_name, s.commission_rate, s.is_active,
       p.id as profile_id, p.display_name, p.first_name, p.last_name,
       p.phone, p.photo_url, p.kyc_status, p.kyc_tier
  from public.sub_agents s
  join public.profiles p on p.id = s.profile_id
  left join public.agencies a on a.id = s.agency_id
  left join public.bus_branches b on b.id = s.station_id;

-- ── 7. updated_at triggers (reuse the canonical function) ────────────────────
do $$
declare t text;
begin
  foreach t in array array['agency_managers','sub_agents','station_staff','hotel_owners','realtors','organizations']
  loop
    execute format('drop trigger if exists trg_%I_ts on public.%I', t, t);
    execute format('create trigger trg_%I_ts before update on public.%I for each row execute function update_updated_at()', t, t);
  end loop;
end $$;

-- ── 8. RLS ────────────────────────────────────────────────────────────────────
-- Posture: service_role (API routes + webhooks) has full access. Authenticated
-- users read their OWN role row / memberships / orgs they belong to; admins
-- (is_admin) read all. No client writes: authenticated INSERT/UPDATE/DELETE
-- policies are intentionally absent (deny-by-default).

alter table public.agency_managers enable row level security;
alter table public.sub_agents      enable row level security;
alter table public.station_staff   enable row level security;
alter table public.hotel_owners    enable row level security;
alter table public.realtors        enable row level security;
alter table public.organizations   enable row level security;
alter table public.memberships     enable row level security;
alter table public.data_merge_conflicts enable row level security;

do $$
declare t text;
begin
  foreach t in array array['agency_managers','sub_agents','station_staff','hotel_owners','realtors','organizations','memberships','data_merge_conflicts']
  loop
    execute format('drop policy if exists %I on public.%I', t || '_service_role_all', t);
    execute format(
      'create policy %I on public.%I for all to service_role using (true) with check (true)',
      t || '_service_role_all', t
    );
  end loop;
end $$;

-- Own-row reads for authenticated users.
drop policy if exists agency_managers_own_read on public.agency_managers;
create policy agency_managers_own_read on public.agency_managers for select
  to authenticated using (profile_id = auth.uid());

drop policy if exists sub_agents_own_read on public.sub_agents;
create policy sub_agents_own_read on public.sub_agents for select
  to authenticated using (profile_id = auth.uid());

drop policy if exists station_staff_own_read on public.station_staff;
create policy station_staff_own_read on public.station_staff for select
  to authenticated using (profile_id = auth.uid());

drop policy if exists hotel_owners_own_read on public.hotel_owners;
create policy hotel_owners_own_read on public.hotel_owners for select
  to authenticated using (profile_id = auth.uid());

drop policy if exists realtors_own_read on public.realtors;
create policy realtors_own_read on public.realtors for select
  to authenticated using (profile_id = auth.uid());

drop policy if exists memberships_own_read on public.memberships;
create policy memberships_own_read on public.memberships for select
  to authenticated using (profile_id = auth.uid());

-- Org reads: members + owner see their orgs.
drop policy if exists organizations_member_read on public.organizations;
create policy organizations_member_read on public.organizations for select
  to authenticated using (
    owner_profile_id = auth.uid()
    or exists (
      select 1 from public.memberships m
      where m.organization_id = organizations.id and m.profile_id = auth.uid()
    )
  );

-- Admin reads everything (review queue + panels).
drop policy if exists admin_read_all_org_model on public.organizations;
create policy admin_read_all_org_model on public.organizations for select
  to authenticated using (public.is_admin());

drop policy if exists admin_read_all_memberships on public.memberships;
create policy admin_read_all_memberships on public.memberships for select
  to authenticated using (public.is_admin());

drop policy if exists admin_read_all_conflicts on public.data_merge_conflicts;
create policy admin_read_all_conflicts on public.data_merge_conflicts for select
  to authenticated using (public.is_admin());

drop policy if exists admin_read_all_sub_agents on public.sub_agents;
create policy admin_read_all_sub_agents on public.sub_agents for select
  to authenticated using (public.is_admin());

drop policy if exists admin_read_all_managers on public.agency_managers;
create policy admin_read_all_managers on public.agency_managers for select
  to authenticated using (public.is_admin());

drop policy if exists admin_read_all_station_staff on public.station_staff;
create policy admin_read_all_station_staff on public.station_staff for select
  to authenticated using (public.is_admin());

drop policy if exists admin_read_all_hotel_owners on public.hotel_owners;
create policy admin_read_all_hotel_owners on public.hotel_owners for select
  to authenticated using (public.is_admin());

drop policy if exists admin_read_all_realtors on public.realtors;
create policy admin_read_all_realtors on public.realtors for select
  to authenticated using (public.is_admin());

-- ── 9. Refresh PostgREST schema cache ─────────────────────────────────────────
notify pgrst, 'reload schema';

commit;

-- End of migration.
