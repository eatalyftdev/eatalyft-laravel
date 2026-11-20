-- [FEATURE] Bus operations: fleet, routes, ticket verification, activity log
-- [CHANGES] buses, bus_routes tables, bus_trips fleet FKs, bus_tickets verify columns, agency-scoped RLS on bus tables, activity_logs append-only table
--
-- Rollback: run 20261005_bus_operations_reverse.sql (drops ONLY what this file
-- created; pre-existing bus tables/columns survive).
-- Apply: paste into Supabase SQL editor, or
--   psql "$SUPABASE_DB_URL" -v ON_ERROR_STOP=1 -f supabase/migrations/20261005_bus_operations.sql

begin;

-- ── 1. Fleet + routes ───────────────────────────────────────────────────────
create table if not exists public.buses (
  id           uuid primary key default gen_random_uuid(),
  agency_id    text not null references public.agencies(id) on delete cascade,
  plate_number text not null,
  capacity     integer not null default 30 check (capacity > 0 and capacity <= 100),
  bus_class    text not null default 'standard',
  amenities    text[] not null default '{}',
  is_active    boolean not null default true,
  created_at   timestamptz not null default now(),
  updated_at   timestamptz not null default now(),
  constraint buses_agency_plate_unique unique (agency_id, plate_number)
);

create table if not exists public.bus_routes (
  id              uuid primary key default gen_random_uuid(),
  agency_id       text not null references public.agencies(id) on delete cascade,
  origin          text not null,
  destination     text not null,
  distance_km     numeric(8,2),
  base_fare_minor bigint not null default 0 check (base_fare_minor >= 0),
  currency        text not null default 'XAF',
  is_active       boolean not null default true,
  created_at      timestamptz not null default now(),
  updated_at      timestamptz not null default now(),
  constraint bus_routes_agency_od_unique unique (agency_id, origin, destination)
);

create index if not exists idx_buses_agency on public.buses (agency_id, is_active);
create index if not exists idx_bus_routes_agency on public.bus_routes (agency_id, is_active);

comment on table public.buses is 'Agency fleet. bus_trips.bus_id points here when the dispatch assigns a vehicle.';
comment on table public.bus_routes is 'Priced origin→destination pairs. Ticket fares must fall within the route base fare bounds (checked app-side, bounded here).';

-- Link trips to fleet + routes (nullable: legacy trips predate the fleet).
alter table public.bus_trips add column if not exists bus_id uuid references public.buses(id) on delete set null;
alter table public.bus_trips add column if not exists route_id uuid references public.bus_routes(id) on delete set null;

-- ── 2. Ticket verification columns (station boarding proof) ─────────────────
alter table public.bus_tickets add column if not exists sub_agent_id uuid references public.profiles(id) on delete set null;
alter table public.bus_tickets add column if not exists verified_by text;
alter table public.bus_tickets add column if not exists verified_at timestamptz;
alter table public.bus_tickets add column if not exists boarded_at timestamptz;
alter table public.bus_tickets add column if not exists verify_station_id text;

create index if not exists idx_bus_tickets_sub_agent on public.bus_tickets (sub_agent_id, booked_at desc);
create index if not exists idx_bus_tickets_ticket_code on public.bus_tickets (ticket_code) where ticket_code is not null;

comment on column public.bus_tickets.sub_agent_id is 'Seller (profiles.id). sub_agent_name stays as the point-of-sale snapshot for manifests.';

-- No un-boarding: checked_in / boarded_at move forward only.
create or replace function public.forbid_ticket_unverify()
returns trigger language plpgsql as $$
begin
  if coalesce(old.checked_in, false) = true and coalesce(new.checked_in, false) = false then
    raise exception 'TICKET_IMMUTABLE: checked_in cannot revert to false (ticket %)', old.id;
  end if;
  if old.boarded_at is not null and new.boarded_at is null then
    raise exception 'TICKET_IMMUTABLE: boarded_at cannot be cleared (ticket %)', old.id;
  end if;
  return new;
end;
$$;

drop trigger if exists trg_bus_tickets_no_unverify on public.bus_tickets;
create trigger trg_bus_tickets_no_unverify
  before update on public.bus_tickets
  for each row execute function public.forbid_ticket_unverify();

-- ── 3. Immutable activity log (actor, role, action, entity, before/after, IP)
create table if not exists public.activity_logs (
  id          uuid primary key default gen_random_uuid(),
  actor_id    text not null,
  actor_role  text not null,
  action      text not null,
  entity_type text not null,
  entity_id   text not null,
  agency_id   text references public.agencies(id) on delete set null,
  before      jsonb,
  after       jsonb,
  ip          text,
  created_at  timestamptz not null default now(),
  constraint activity_logs_action_check check (char_length(action) between 3 and 64)
);

create index if not exists idx_activity_logs_actor on public.activity_logs (actor_id, created_at desc);
create index if not exists idx_activity_logs_entity on public.activity_logs (entity_type, entity_id, created_at desc);
create index if not exists idx_activity_logs_agency on public.activity_logs (agency_id, created_at desc);

comment on table public.activity_logs is 'Append-only ops audit. Rows can NEVER be updated or deleted (trigger). Corrections are new rows.';

create or replace function public.forbid_activity_log_mutation()
returns trigger language plpgsql as $$
begin
  raise exception 'ACTIVITY_LOG_IMMUTABLE: activity_logs is append-only ( attempted % on % )', TG_OP, OLD.id;
end;
$$;

drop trigger if exists trg_activity_logs_no_update on public.activity_logs;
create trigger trg_activity_logs_no_update
  before update or delete on public.activity_logs
  for each row execute function public.forbid_activity_log_mutation();

-- ── 4. Reconciliation view (sold vs boarded per seller per day) ─────────────
create or replace view public.v_ticket_reconciliation as
select t.agency_id,
       coalesce(t.sub_agent_id::text, 'unassigned') as seller_key,
       (t.booked_at::date) as sale_day,
       count(*) as tickets_sold,
       coalesce(sum(t.total_charge), 0) as gross_sales,
       count(*) filter (where t.checked_in = true) as tickets_boarded,
       (count(*) - count(*) filter (where t.checked_in = true)) as unboarded_count
  from public.bus_tickets t
 where t.payment_status = 'paid'
 group by t.agency_id, seller_key, sale_day;

-- ── 5. RLS ────────────────────────────────────────────────────────────────────
alter table public.buses enable row level security;
alter table public.bus_routes enable row level security;
alter table public.activity_logs enable row level security;

do $$
declare t text;
begin
  foreach t in array array['buses','bus_routes','activity_logs']
  loop
    execute format('drop policy if exists %I on public.%I', t || '_service_role_all', t);
    execute format(
      'create policy %I on public.%I for all to service_role using (true) with check (true)',
      t || '_service_role_all', t
    );
  end loop;
end $$;

-- Agency isolation: a member of the org that mirrors an agency reads that
-- agency's fleet/routes/trips/tickets/activity. Admins read all.
-- (Membership path: memberships → organizations(source_table='agencies').)

drop policy if exists buses_member_read on public.buses;
create policy buses_member_read on public.buses for select
  to authenticated using (
    public.is_admin() or exists (
      select 1 from public.memberships m
      join public.organizations o on o.id = m.organization_id
      where o.source_table = 'agencies' and o.source_id = buses.agency_id
        and m.profile_id = auth.uid()
    )
  );

drop policy if exists bus_routes_member_read on public.bus_routes;
create policy bus_routes_member_read on public.bus_routes for select
  to authenticated using (
    public.is_admin() or exists (
      select 1 from public.memberships m
      join public.organizations o on o.id = m.organization_id
      where o.source_table = 'agencies' and o.source_id = bus_routes.agency_id
        and m.profile_id = auth.uid()
    )
  );

drop policy if exists bus_trips_member_read on public.bus_trips;
create policy bus_trips_member_read on public.bus_trips for select
  to authenticated using (
    public.is_admin() or exists (
      select 1 from public.memberships m
      join public.organizations o on o.id = m.organization_id
      where o.source_table = 'agencies' and o.source_id = bus_trips.agency_id
        and m.profile_id = auth.uid()
    )
  );

drop policy if exists bus_tickets_member_read on public.bus_tickets;
create policy bus_tickets_member_read on public.bus_tickets for select
  to authenticated using (
    public.is_admin()
    or (
      bus_tickets.agency_id is not null and exists (
        select 1 from public.memberships m
        join public.organizations o on o.id = m.organization_id
        where o.source_table = 'agencies' and o.source_id = bus_tickets.agency_id
          and m.profile_id = auth.uid()
      )
    )
  );

-- Sub-agents read ONLY their own sales.
drop policy if exists bus_tickets_seller_read on public.bus_tickets;
create policy bus_tickets_seller_read on public.bus_tickets for select
  to authenticated using (bus_tickets.sub_agent_id = auth.uid());

drop policy if exists activity_logs_member_read on public.activity_logs;
create policy activity_logs_member_read on public.activity_logs for select
  to authenticated using (
    public.is_admin()
    or (
      activity_logs.agency_id is not null and exists (
        select 1 from public.memberships m
        join public.organizations o on o.id = m.organization_id
        where o.source_table = 'agencies' and o.source_id = activity_logs.agency_id
          and m.profile_id = auth.uid()
      )
    )
  );

-- ── 6. updated_at triggers ────────────────────────────────────────────────────
do $$
declare t text;
begin
  foreach t in array array['buses','bus_routes']
  loop
    execute format('drop trigger if exists trg_%I_ts on public.%I', t, t);
    execute format('create trigger trg_%I_ts before update on public.%I for each row execute function update_updated_at()', t, t);
  end loop;
end $$;

notify pgrst, 'reload schema';

commit;

-- End of migration.
