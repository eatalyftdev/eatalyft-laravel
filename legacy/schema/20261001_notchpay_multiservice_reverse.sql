-- ============================================================================
-- REVERSE of supabase/migrations/20261001_notchpay_multiservice.sql
-- ============================================================================
-- Undoes, in dependency order:
--   1. the two RLS policies added for own-read + service-role access
--   2. the three indexes (idx_payments_gateway, idx_payments_service_agency,
--      idx_payments_notch_ref)
--   3. the widened payments_service_type_check (restored to the original set)
--   4. the added tracking columns (gateway, notch_reference, agency_id,
--      agency_slug, branch_id, branch_slug) — service_type is NOT dropped
--      because it pre-existed this migration.
-- Idempotent: DROP ... IF EXISTS / guarded DO blocks; re-runs are a no-op.
-- ============================================================================

begin;

-- ── 1. Policies ─────────────────────────────────────────────────────────────
drop policy if exists "Users can read own payments"        on public.payments;
drop policy if exists "Service role full access to payments" on public.payments;

-- ── 2. Indexes ──────────────────────────────────────────────────────────────
drop index if exists public.idx_payments_notch_ref;
drop index if exists public.idx_payments_service_agency;
drop index if exists public.idx_payments_gateway;

-- ── 3. Restore the original service_type CHECK ──────────────────────────────
do $$
declare cname text;
begin
  for cname in
    select con.conname
      from pg_constraint con
      join pg_class rel     on rel.oid = con.conrelid
      join pg_namespace ns  on ns.oid = rel.relnamespace
     where ns.nspname = 'public'
       and rel.relname = 'payments'
       and con.contype = 'c'
       and pg_get_constraintdef(con.oid) ilike '%service_type%'
  loop
    execute format('alter table public.payments drop constraint %I', cname);
  end loop;

  alter table public.payments
    add constraint payments_service_type_check
    check (service_type in ('ride','food','parcel','subscription','topup'));
exception
  when check_violation then
    raise notice 'payments rows outside the original service_type set — leaving constraint widened';
  when duplicate_object then null;
end $$;

-- ── 4. Drop the added columns (service_type is pre-existing — keep it) ─────
alter table public.payments
  drop column if exists branch_slug,
  drop column if exists branch_id,
  drop column if exists agency_slug,
  drop column if exists agency_id,
  drop column if exists notch_reference,
  drop column if exists gateway;

notify pgrst, 'reload schema';

commit;

-- End of reverse migration.
