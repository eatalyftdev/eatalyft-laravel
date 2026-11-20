-- [FEATURE] PayCore wallet ledger layer
-- [CHANGES] wallet_ledger table, RLS policies
--
-- Purpose: Double-entry ledger for all wallet balance mutations.
-- Per SPEC #8: Every balance mutation requires an immutable payment_intents/payment_transactions entry;
--   no direct UPDATE wallet SET balance = ....
-- Per SPEC #9: Two endpoints—collections and disbursements webhooks.
-- Every credit/debit is an immutable row with before/after balances.
--
-- Rollback: run 20261024_paycore_wallet_ledger_reverse.sql (drops exactly this table; policies drop with it).
-- Safe: Wallet balances survive the rollback (recalculated from ledger if needed); audit log intact.

begin;

-- ── 1. Wallet ledger table ─────────────────────────────────────────────
create table if not exists public.wallet_ledger (
  id              uuid primary key default gen_random_uuid(),
  wallet_id       text not null,          -- Firestore doc id (uid) or Supabase profile id
  transaction_type text not null check (transaction_type in ('credit', 'debit', 'fee', 'reversal')),
  amount_minor    bigint not null default 0,  -- amount in this transaction (minor units); negative for debit
  before_balance_minor bigint not null default 0, -- balance before this transaction
  after_balance_minor bigint not null default 0,   -- balance after this transaction
  currency        text not null default 'XAF',
  reference       text not null,          -- EATALYFT-* reference or provider transaction ID
  payment_intent_id uuid,             -- link to payment_intents table
  created_at      timestamptz not null default now(),
  metadata        jsonb not null default '{}',
  constraint wallet_ledger_transaction_type_check
    check (transaction_type in ('credit', 'debit', 'fee', 'reversal')),
  constraint wallet_ledger_amount_check
    check (amount_minor >= 0 or transaction_type in ('debit', 'reversal'))
);

comment on table public.wallet_ledger is
  'PayCore double-entry ledger: every wallet balance mutation is an immutable row with before/after balances.';

-- ── 2. Indexes ──────────────────────────────────────────────────────────
create index if not exists idx_wallet_ledger_wallet
  on public.wallet_ledger (wallet_id);
create index if not exists idx_wallet_ledger_reference
  on public.wallet_ledger (reference);
create index if not exists idx_wallet_ledger_payment_intent
  on public.wallet_ledger (payment_intent_id);
create index if not exists idx_wallet_ledger_created
  on public.wallet_ledger (created_at desc);

-- ── 3. RLS (deny-by-default; server/service-role only — clients use API) ─────
alter table public.wallet_ledger enable row level security;

drop policy if exists wallet_ledger_service_role_all on public.wallet_ledger;
create policy wallet_ledger_service_role_all
  on public.wallet_ledger for all
  to service_role
  using (true) with check (true);

-- ── 4. Refresh PostgREST schema cache ─────────────────────────────────────
notify pgrst, 'reload schema';

commit;

-- End of migration.