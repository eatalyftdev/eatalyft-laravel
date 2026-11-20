-- [FEATURE] Parcel lifecycle: events, assignments, delivery proof, earnings
-- [CHANGES] parcels status set widened + proof/earnings columns, parcel_events, parcel_assignments, RLS
--
-- Rollback: run 20261007_parcels_flow_reverse.sql (drops ONLY what this file
-- created; legacy 'pending…cancelled' states keep working).
-- Apply: paste into Supabase SQL editor, or
--   psql "$SUPABASE_DB_URL" -v ON_ERROR_STOP=1 -f supabase/migrations/20261007_parcels_flow.sql
--
-- FLOW: created > payment_confirmed > awaiting_assignment > assigned to agent
-- > picked_up > in_transit > delivered (OTP/code + optional photo) > settled.
-- Side states: cancelled / failed / returned. Transition rules are enforced
-- app-side (src/lib/parcels/machine.ts, tested) AND by the trigger below,
-- which rejects jumps that skip mandatory states.

begin;

-- ── 1. Widen parcels.status (drop legacy CHECK by discovered name, add new) ──
-- Discovery keys on 'in_transit', which appears ONLY in the legacy status
-- CHECK (no other parcels constraint mentions it). Verify after apply:
--   select conname from pg_constraint where conrelid = 'public.parcels'::regclass and contype = 'c';
-- Exactly one status CHECK (parcels_status_check) must remain.
do $$
declare cname text;
begin
  for cname in
    select con.conname
      from pg_constraint con
      join pg_class rel     on rel.oid = con.conrelid
      join pg_namespace ns  on ns.oid = rel.relnamespace
     where ns.nspname = 'public'
       and rel.relname = 'parcels'
       and con.contype = 'c'
       and pg_get_constraintdef(con.oid) ilike '%in_transit%'
  loop
    execute format('alter table public.parcels drop constraint %I', cname);
  end loop;

  alter table public.parcels
    add constraint parcels_status_check
    check (status in (
      'created', 'payment_confirmed', 'awaiting_assignment',
      'assigned', 'picked_up', 'in_transit', 'delivered', 'settled',
      'cancelled', 'failed', 'returned',
      -- legacy states (pre-flow rows keep working):
      'pending', 'requested'
    ));
exception
  when duplicate_object then null;
end $$;

-- ── 2. Proof + earnings columns (OTP stored HASHED, never plaintext) ─────────
alter table public.parcels add column if not exists assigned_agent_uid text;
alter table public.parcels add column if not exists delivery_otp_hash text;
alter table public.parcels add column if not exists delivery_photo_url text;
alter table public.parcels add column if not exists earnings_minor bigint not null default 0
  constraint parcels_earnings_check check (earnings_minor >= 0);
alter table public.parcels add column if not exists settled_at timestamptz;

create index if not exists idx_parcels_agent_status on public.parcels (assigned_agent_uid, status);
create index if not exists idx_parcels_status on public.parcels (status, created_at desc);

comment on column public.parcels.delivery_otp_hash is 'SHA-256 hex of the 6-digit delivery code. The code itself is never stored.';

-- ── 3. Status timeline (every transition is a row) ───────────────────────────
create table if not exists public.parcel_events (
  id          uuid primary key default gen_random_uuid(),
  parcel_id   uuid not null references public.parcels(id) on delete cascade,
  from_status text,
  to_status   text not null,
  actor_uid   text,
  actor_role  text,
  note        text,
  proof       jsonb not null default '{}',
  created_at  timestamptz not null default now()
);

create index if not exists idx_parcel_events_parcel on public.parcel_events (parcel_id, created_at);

comment on table public.parcel_events is 'Append-only status timeline. The (from_status → to_status) chain is the audit trail.';

-- ── 4. Assignment offers (manual + auto-suggest, accept/decline/timeout) ─────
create table if not exists public.parcel_assignments (
  id          uuid primary key default gen_random_uuid(),
  parcel_id   uuid not null references public.parcels(id) on delete cascade,
  agent_uid   text not null,
  status      text not null default 'offered'
              check (status in ('offered', 'accepted', 'declined', 'expired', 'reassigned')),
  offered_at  timestamptz not null default now(),
  expires_at  timestamptz not null default now() + interval '5 minutes',
  responded_at timestamptz,
  created_at  timestamptz not null default now()
);

create index if not exists idx_parcel_assignments_parcel on public.parcel_assignments (parcel_id, created_at desc);
create index if not exists idx_parcel_assignments_expiry on public.parcel_assignments (status, expires_at)
  where status = 'offered';

comment on table public.parcel_assignments is 'One row per offer. accept/decline by the agent; expiry handled by the cron reassign job.';

-- ── 5. Transition guard trigger (no skipped states, no resurrection) ─────────
create or replace function public.guard_parcel_transition()
returns trigger language plpgsql as $$
declare
  allowed boolean := false;
begin
  if old.status = new.status then return new; end if;
  case old.status
    when 'created' then allowed := new.status in ('payment_confirmed', 'cancelled');
    when 'pending' then allowed := new.status in ('payment_confirmed', 'awaiting_assignment', 'cancelled');
    when 'requested' then allowed := new.status in ('payment_confirmed', 'awaiting_assignment', 'cancelled');
    when 'payment_confirmed' then allowed := new.status in ('awaiting_assignment', 'cancelled');
    when 'awaiting_assignment' then allowed := new.status in ('assigned', 'cancelled');
    when 'assigned' then allowed := new.status in ('picked_up', 'awaiting_assignment', 'cancelled');
    when 'picked_up' then allowed := new.status in ('in_transit', 'failed');
    when 'in_transit' then allowed := new.status in ('delivered', 'failed');
    when 'delivered' then allowed := new.status in ('settled', 'returned');
    when 'failed' then allowed := new.status in ('awaiting_assignment', 'cancelled');
    when 'returned' then allowed := new.status in ('awaiting_assignment', 'cancelled');
    else allowed := false;
  end case;
  if not allowed then
    raise exception 'PARCEL_TRANSITION: % → % is not allowed', old.status, new.status;
  end if;
  return new;
end;
$$;

drop trigger if exists trg_parcels_guard_transition on public.parcels;
create trigger trg_parcels_guard_transition
  before update of status on public.parcels
  for each row execute function public.guard_parcel_transition();

-- ── 6. RLS (service-role API writes; admins read all) ────────────────────────
alter table public.parcel_events enable row level security;
alter table public.parcel_assignments enable row level security;

drop policy if exists parcel_events_service_role_all on public.parcel_events;
create policy parcel_events_service_role_all
  on public.parcel_events for all
  to service_role
  using (true) with check (true);

drop policy if exists parcel_assignments_service_role_all on public.parcel_assignments;
create policy parcel_assignments_service_role_all
  on public.parcel_assignments for all
  to service_role
  using (true) with check (true);

drop policy if exists parcel_events_admin_read on public.parcel_events;
create policy parcel_events_admin_read on public.parcel_events for select
  to authenticated using (public.is_admin());

drop policy if exists parcel_assignments_admin_read on public.parcel_assignments;
create policy parcel_assignments_admin_read on public.parcel_assignments for select
  to authenticated using (public.is_admin());

notify pgrst, 'reload schema';

commit;

-- End of migration.
