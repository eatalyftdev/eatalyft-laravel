-- ============================================================
-- EataLyft — Supabase Migration: Bus / Agency multi-tenant schema
-- File: supabase/migrations/20261002_bus_agency_schema.sql
-- Reverse: 20261002_bus_agency_schema_reverse.sql
--
-- Apply via the Supabase SQL editor (dashboard → project
-- → SQL Editor → New query), OR:
--   psql "$SUPABASE_DB_URL" -v ON_ERROR_STOP=1 -f supabase/migrations/20261002_bus_agency_schema.sql
--
-- DO NOT run `supabase db push`. Per MIGRATIONS-PROTOCOL.md Rule 1 and
-- db-migrate.yml, the schema is applied from the byte-true master
-- (supabase/schema/000_master_schema.sql) by CI, never by replaying the
-- raw migration dir (that would execute the *_reverse.sql files forward).
--
-- WHAT THIS DOES
-- Mirrors the Firestore bus/agency domain into Supabase so reporting and
-- finance can query it with SQL:
--   • agencies / bus_branches  — tenant layer (Firestore `agencies/{id}`
--     and its `stations` subcollection). Powers the EATALYFT-<SERVICE>-
--     <AGENCY>-<BRANCH>-<ID> payment reference.
--   • bus_trips                — scheduled departures (Firestore `trips`).
--   • bus_tickets              — individual sold seats (Firestore `bus`).
--   • bus_bookings             — kept for the legacy client flow
--     (Firestore `busBookings`).
--
-- All statements are idempotent and additive: nothing is dropped and no
-- existing table is retyped. Column additions use IF NOT EXISTS so this can
-- be replayed safely against a database that already has the legacy shapes.
--
-- ORDER (single transaction): tenants → branches → operations → sales →
-- indexes → comments → RLS → realtime → notify → commit. Nothing below
-- the COMMIT line is executable (historical note: an earlier revision had
-- the bus_bookings tail and bus_branches definition AFTER the commit plus
-- a dangling index fragment — fixed by this rewrite; the file was never
-- applied, so no database carries the broken order).
-- ============================================================

begin;

-- ────────────────────────────────────────────────────────────────────────────
-- 1. Tenants: agencies
-- ────────────────────────────────────────────────────────────────────────────

create table if not exists public.agencies (
  id             text primary key,
  custom_uid     text unique,
  business_name  text,
  legal_name     text,
  slug           text unique,
  region         text,
  city           text,
  country        text default 'CM',
  phone          text,
  email          text,
  logo_url       text,
  is_active      boolean not null default true,
  onboarded_at   timestamptz,
  created_at     timestamptz not null default now(),
  updated_at     timestamptz not null default now()
);

comment on table  public.agencies is 'Bus/travel agencies (Firestore agencies/{id}). Tenant root.';
comment on column public.agencies.slug is 'URL + reference slug segment (EATALYFT-BUS-<AGENCY>-...).';
comment on column public.agencies.business_name is 'Firestore businessName || name.';

-- ────────────────────────────────────────────────────────────────────────────
-- 2. Tenants: branches (stations)
-- ────────────────────────────────────────────────────────────────────────────
-- Branches = stations. Mirrors agencies/{agencyId}/stations/{stationId}.

create table if not exists public.bus_branches (
  id             text primary key,
  agency_id      text not null references public.agencies(id) on delete cascade,
  name           text not null,
  slug           text,
  location       text,
  phone_number   text,
  manager_name   text,
  manager_ids    text[] not null default '{}',
  is_active      boolean not null default true,
  created_at     timestamptz not null default now(),
  updated_at     timestamptz not null default now()
);

create index if not exists idx_bus_branches_agency on public.bus_branches(agency_id);
create unique index if not exists idx_bus_branches_agency_slug
  on public.bus_branches(agency_id, slug) where slug is not null;

comment on table  public.bus_branches is 'Agency branches/stations (Firestore agencies/{id}/stations/{id}).';
comment on column public.bus_branches.slug is 'Reference slug segment for EATALYFT-...-<BRANCH>-<ID>.';

-- ────────────────────────────────────────────────────────────────────────────
-- 3. Operations: trips
-- ────────────────────────────────────────────────────────────────────────────
-- NOTE: bus_trips already exists on legacy databases (scripts/full-schema-v2.sql)
-- with agency_id NOT NULL REFERENCES agency_profiles(id). We therefore CREATE
-- only when absent, and otherwise WIDEN via ADD COLUMN IF NOT EXISTS so an
-- existing table is never retyped (Rule 1: no renames/retypes).

create table if not exists public.bus_trips (
  id              text primary key,
  custom_uid      text unique,
  agency_id       text not null references public.agencies(id),
  branch_id       text,
  bus_number      text,
  bus_class       text,
  bus_type        text,
  origin          text,
  destination     text,
  departure_at    timestamptz,
  arrival_at      timestamptz,
  total_seats     integer,
  booked_seats    integer not null default 0,
  pending_seats   integer not null default 0,
  available_seats integer,
  price_xaf       numeric(12,2) not null default 0,
  base_fare       numeric(12,2),
  currency        text not null default 'XAF',
  status          text not null default 'scheduled',
  amenities       text[] not null default '{}',
  image_url       text,
  created_by      text,
  status_history  jsonb not null default '{}',
  in_progress_at  timestamptz,
  completed_at    timestamptz,
  cancelled_at    timestamptz,
  created_at      timestamptz not null default now(),
  updated_at      timestamptz not null default now()
);

-- Additive widening for a pre-existing bus_trips.
alter table public.bus_trips add column if not exists custom_uid      text;
alter table public.bus_trips add column if not exists branch_id       text;
alter table public.bus_trips add column if not exists bus_number     text;
alter table public.bus_trips add column if not exists bus_class      text;
alter table public.bus_trips add column if not exists bus_type       text;
alter table public.bus_trips add column if not exists available_seats integer;
alter table public.bus_trips add column if not exists base_fare      numeric(12,2);
alter table public.bus_trips add column if not exists currency       text not null default 'XAF';
alter table public.bus_trips add column if not exists amenities      text[] not null default '{}';
alter table public.bus_trips add column if not exists image_url      text;
alter table public.bus_trips add column if not exists created_by     text;
alter table public.bus_trips add column if not exists status_history jsonb not null default '{}';
alter table public.bus_trips add column if not exists in_progress_at timestamptz;
alter table public.bus_trips add column if not exists completed_at   timestamptz;
alter table public.bus_trips add column if not exists cancelled_at   timestamptz;

create unique index if not exists idx_bus_trips_custom_uid
  on public.bus_trips(custom_uid) where custom_uid is not null;
create index if not exists idx_bus_trips_agency_status
  on public.bus_trips(agency_id, status, departure_at desc);
create index if not exists idx_bus_trips_departure
  on public.bus_trips(departure_at desc);
create index if not exists idx_bus_trips_branch
  on public.bus_trips(branch_id) where branch_id is not null;

comment on table  public.bus_trips is 'Scheduled bus departures (Firestore trips/{id}).';
comment on column public.bus_trips.status is
  'scheduled | boarding | in_progress | departed | completed | delayed | cancelled';

-- ────────────────────────────────────────────────────────────────────────────
-- 4. Sales: tickets (individual seats) + legacy bookings
-- ────────────────────────────────────────────────────────────────────────────
-- bus_tickets mirrors the Firestore `bus` collection — one row per sold seat,
-- which is what /api/bus/manifest reads (customerName, customerPhone,
-- seatNumber, totalCharge, paymentStatus, subAgentName).

create table if not exists public.bus_tickets (
  id               text primary key,
  custom_uid       text unique,
  trip_id          text not null references public.bus_trips(id) on delete cascade,
  agency_id        text references public.agencies(id),
  branch_id        text,
  seat_number      text,
  customer_uid     text,
  customer_name    text,
  customer_phone   text,
  customer_email   text,
  destination      text,
  sub_agent_name   text,
  price            numeric(12,2) not null default 0,
  total_charge     numeric(12,2),
  currency         text not null default 'XAF',
  payment_status   text not null default 'pending',
  payment_method   text,
  payment_reference text,
  ticket_code      text,
  seat_status      text not null default 'booked',
  checked_in       boolean not null default false,
  metadata         jsonb not null default '{}',
  booked_at        timestamptz,
  created_at       timestamptz not null default now(),
  updated_at       timestamptz not null default now()
);

create index if not exists idx_bus_tickets_trip  on public.bus_tickets(trip_id, seat_number);
create index if not exists idx_bus_tickets_agency on public.bus_tickets(agency_id, payment_status);
create index if not exists idx_bus_tickets_customer on public.bus_tickets(customer_uid, booked_at desc);
create index if not exists idx_bus_tickets_payment_ref
  on public.bus_tickets(payment_reference) where payment_reference is not null;

comment on table  public.bus_tickets is 'Individual sold seats (Firestore bus/{id}). Passenger manifest source.';
comment on column public.bus_tickets.payment_status is 'pending | paid | unpaid | failed | refunded';
comment on column public.bus_tickets.seat_status is 'booked | reserved | blocked | available';

-- Legacy client booking flow (src/lib/services/busService.ts → busBookings).
create table if not exists public.bus_bookings (
  id               text primary key,
  custom_uid       text unique,
  user_id          text not null,
  trip_id          text not null references public.bus_trips(id) on delete cascade,
  agency_id        text references public.agencies(id),
  branch_id        text,
  booked_by        text,
  booked_by_role   text,
  booked_by_name   text,
  passenger_name   text,
  passenger_phone  text,
  passenger_email  text,
  seat_numbers     text[] not null default '{}',
  number_of_seats  integer not null default 1,
  fare_per_seat    numeric(12,2),
  total_fare       numeric(12,2) not null default 0,
  currency         text not null default 'XAF',
  payment_method   text,
  payment_status   text not null default 'pending',
  payment_reference text,
  booking_status   text not null default 'pending',
  ticket_code      text,
  metadata         jsonb not null default '{}',
  booked_at        timestamptz,
  created_at       timestamptz not null default now(),
  updated_at       timestamptz not null default now()
);

create index if not exists idx_bus_bookings_trip   on public.bus_bookings(trip_id, booking_status);
create index if not exists idx_bus_bookings_user   on public.bus_bookings(user_id, booked_at desc);
create index if not exists idx_bus_bookings_agency on public.bus_bookings(agency_id, booking_status);

comment on table  public.bus_bookings is 'Seat-group bookings (Firestore busBookings/{id}). Legacy client flow.';
comment on column public.bus_bookings.booking_status is 'pending | confirmed | cancelled | refunded';
comment on column public.bus_bookings.payment_status is 'pending | paid | failed | refunded';

-- ────────────────────────────────────────────────────────────────────────────
-- 5. RLS — service-role writes, owner/admin/finance reads
-- ────────────────────────────────────────────────────────────────────────────
-- Mirrors the Firestore posture in firestore.rules: the Admin SDK / CI writes,
-- clients read their own rows. Deny-all RLS plus explicit service-role policy;
-- a permissive authenticated policy is intentionally NOT added because the
-- web client never reads Supabase directly (see ARCHITECTURE.md §4).

do $$
declare t text;
begin
  foreach t in array array['agencies','bus_branches','bus_trips','bus_tickets','bus_bookings']
  loop
    execute format('alter table public.%I enable row level security', t);
    execute format('drop policy if exists %I on public.%I', t || '_service_role_all', t);
    execute format(
      'create policy %I on public.%I for all to service_role using (true) with check (true)',
      t || '_service_role_all', t
    );
  end loop;
end $$;

-- ────────────────────────────────────────────────────────────────────────────
-- 6. Realtime + schema cache
-- ────────────────────────────────────────────────────────────────────────────
do $$
begin
  if exists (select 1 from pg_publication where pubname = 'supabase_realtime') then
    begin
      alter publication supabase_realtime add table public.bus_trips;
      alter publication supabase_realtime add table public.bus_tickets;
    exception when duplicate_object then null;
             when undefined_object then null;
    end;
  end if;
end $$;

notify pgrst, 'reload schema';

commit;

-- End of migration.
