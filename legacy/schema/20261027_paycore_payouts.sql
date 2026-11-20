-- [FEATURE] PayCore payouts layer
-- [CHANGES] payouts table, RLS policies, indexes
-- 
-- Purpose: Track all withdrawal/payout requests from EataLyft wallets
-- to external payment providers (Fapshi Disbursements).
-- Per SPEC #8: Immutable ledger entries; no direct UPDATE wallet SET balance = ....
-- Per SPEC #16: Concurrent withdrawal protection via idempotency and atomic reservation.
-- Per SPEC #24: Audit log of all payout state transitions.
-- Per SPEC #32: Reconciliation support.
--
-- Rollback: run 20261027_paycore_payouts_reverse.sql (drops exactly this table; policies drop with it).
-- Safe: Wallet balances survive the rollback (recalculated from ledger if needed); audit log intact.
--
begin;

-- ── 1. Payouts table ─────────────────────────────────────────────────────
create table if not exists public.payouts (
  id              uuid primary key default gen_random_uuid(),
  public_id       uuid not null unique,
  user_id         text not null,          -- Firestore doc id (uid) or Supabase profile id
  wallet_id       text not null,          -- linked wallet identifier
  role            text not null,          -- customer|vendor|agency|handyman|nanny|merchant|partner|other
  amount_minor    bigint not null default 0, -- net withdrawal amount (minor units)
  withdrawal_fee_minor bigint not null default 0, -- withdrawal fee in minor units
  total_debit_minor bigint not null default 0, -- amount_minor + withdrawal_fee_minor
  currency        text not null default 'XAF',
  destination_phone text not null,         -- normalized mobile money number
  provider        text not null default 'fapshi', -- provider name
  provider_service text not null default 'disbursements', -- Fapshi disbursements service
  provider_transaction_id text, -- Fapshi provider transaction ID
  external_reference text, -- Fapshi external reference
  eatalyft_reference text not null unique, -- EataLyft withdrawal reference (EL-WDR-YYYYMMDD-XXXXX)
  status          text not null default 'CREATED', -- CREATED|PENDING|PROCESSING|SUCCESSFUL|FAILED|REVERSED|CANCELLED|REVIEW_REQUIRED
  failure_reason  text, -- reason if payout failed
   provider_response jsonb not null default '{}', -- raw provider response metadata
  created_at      timestamptz not null default now(),
  processing_at   timestamptz,
  completed_at    timestamptz,
  failed_at       timestamptz,
  CONSTRAINT payouts_status_check CHECK (status in ('CREATED','PENDING','PROCESSING','SUCCESSFUL','FAILED','REVERSED','CANCELLED','REVIEW_REQUIRED')),
  CONSTRAINT payouts_amount_check CHECK (amount_minor > 0),
  CONSTRAINT payouts_fee_check CHECK (withdrawal_fee_minor >= 0),
  CONSTRAINT payouts_total_check CHECK (total_debit_minor = amount_minor + withdrawal_fee_minor)
);

-- ── 2. Indexes ───────────────────────────────────────────────────────────
create index if not exists idx_payouts_user ON public.payouts (user_id);
create index if not exists idx_payouts_wallet ON public.payouts (wallet_id);
create index if not exists idx_payouts_role ON public.payouts (role);
create index if not exists idx_payouts_status ON public.payouts (status);
create index if not exists idx_payouts_eatalyft_ref ON public.payouts (eatalyft_reference);
create index if not exists idx_payouts_provider_tx ON public.payouts (provider_transaction_id);
create index if not exists idx_payouts_created ON public.payouts (created_at desc);
create index if not exists idx_payouts_destination ON public.payouts (destination_phone);

-- ── 3. RLS (deny-by-default; server/service-role only — clients use API) ─────
alter table public.payouts enable row level security;

drop policy if exists payouts_service_role_all on public.payouts;
create policy payouts_service_role_all
  on public.payouts for all
  to service_role
  using (true) with check (true);

-- ── 4. Refresh PostgREST schema cache ─────────────────────────────────────
notify pgrst, 'reload schema';

commit;

-- End of migration.