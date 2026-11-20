-- ============================================================
-- EataLyft — Supabase Migration: Notch Pay / MeSomb multi-service payments
-- File: supabase/migrations/20261001_notchpay_multiservice.sql
-- Reverse: 20261001_notchpay_multiservice_reverse.sql
-- Run: npx supabase db push
-- ============================================================
-- Adds gateway + provider-native reference + service/agency/branch tracking to
-- public.payments so the Firestore→Supabase payment mirror can be reconciled
-- per service, agency and branch (see src/lib/payments/reference.ts).
--
-- Idempotent + additive: every statement is guarded, no existing data is
-- rewritten, and pre-existing rows simply default to gateway = 'notchpay'.
-- ============================================================

begin;

-- ── 1. Tracking columns ─────────────────────────────────────────────────────
-- service_type already exists on the geo-services schema; the IF NOT EXISTS
-- form keeps this migration replay-safe on both fresh and legacy databases.
alter table public.payments
  add column if not exists gateway         varchar(32) default 'notchpay',
  add column if not exists notch_reference varchar(128),
  add column if not exists service_type    varchar(32),
  add column if not exists agency_id       varchar(64),
  add column if not exists agency_slug     varchar(64),
  add column if not exists branch_id       varchar(64),
  add column if not exists branch_slug     varchar(64);

comment on column public.payments.gateway is
  'Gateway that processed the charge: notchpay | mesomb (drain/fallback).';
comment on column public.payments.notch_reference is
  'Provider-native reference (Notch Pay `trx.…`). Mirrors transactions/{trxID}.notchReference.';
comment on column public.payments.agency_slug is
  'Agency segment of the EataLyft reference (EATALYFT-<SERVICE>-<AGENCY>-<BRANCH>-<ID>).';
comment on column public.payments.branch_slug is
  'Branch/station segment of the EataLyft reference.';

-- ── 2. Widen the service_type CHECK to the supported service set ────────────
-- Original constraint allowed ride/food/parcel/subscription/topup only.
-- Widening is safe: existing values are a strict subset of the new list.
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
    check (service_type in (
      'ride','food','parcel','bus','hotel','rental',
      'subscription','topup','wallet','giftcard','esim','ticket'
    ));
exception
  when duplicate_object then null;
end $$;

-- ── 3. Indexes ──────────────────────────────────────────────────────────────
create index if not exists idx_payments_gateway       on public.payments(gateway);
create index if not exists idx_payments_service_agency on public.payments(service_type, agency_id, branch_id);
create index if not exists idx_payments_notch_ref     on public.payments(notch_reference);

-- ── 4. RLS + policies ───────────────────────────────────────────────────────
alter table public.payments enable row level security;

-- Owners read their own rows. `::text` on both sides keeps this valid whether
-- user_id is uuid (repo migration) or text (Firebase UID mirror).
drop policy if exists "Users can read own payments" on public.payments;
create policy "Users can read own payments"
  on public.payments for select
  using (auth.uid()::text = user_id::text);

-- Service-role (server / webhook / mirror) has full access.
drop policy if exists "Service role full access to payments" on public.payments;
create policy "Service role full access to payments"
  on public.payments for all
  to service_role
  using (auth.jwt() ->> 'role' = 'service_role')
  with check (auth.jwt() ->> 'role' = 'service_role');

-- ── 5. Refresh PostgREST schema cache ──────────────────────────────────────
notify pgrst, 'reload schema';

commit;

-- End of migration.
