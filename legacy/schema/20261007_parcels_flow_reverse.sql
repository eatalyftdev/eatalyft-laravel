-- [FEATURE] Parcel lifecycle — REVERSE
-- [CHANGES] drops parcel_events/parcel_assignments tables + guard trigger/function, parcels added columns; restores legacy status CHECK
--
-- Rollback note: restores the pre-flow status set
-- (pending/assigned/picked_up/in_transit/delivered/failed/cancelled). Rows
-- already in NEW states (created/payment_confirmed/awaiting_assignment/
-- settled/returned) would violate the restored CHECK on next UPDATE — resolve
-- them first (settle or cancel open parcels). Columns drop only if unreferenced.

begin;

drop trigger if exists trg_parcels_guard_transition on public.parcels;
drop function if exists public.guard_parcel_transition();

drop table if exists public.parcel_assignments;
drop table if exists public.parcel_events;

alter table public.parcels drop column if exists assigned_agent_uid;
alter table public.parcels drop column if exists delivery_otp_hash;
alter table public.parcels drop column if exists delivery_photo_url;
alter table public.parcels drop column if exists earnings_minor;
alter table public.parcels drop column if exists settled_at;

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
       and con.conname = 'parcels_status_check'
  loop
    execute format('alter table public.parcels drop constraint %I', cname);
  end loop;
  alter table public.parcels
    add constraint parcels_status_check
    check (status in ('pending','assigned','picked_up','in_transit','delivered','failed','cancelled'));
exception
  when duplicate_object then null;
end $$;

notify pgrst, 'reload schema';

commit;

-- End of reverse migration.
