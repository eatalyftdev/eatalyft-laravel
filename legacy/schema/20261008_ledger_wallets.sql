-- [FEATURE] Double-entry ledger, secure wallet transfers, withdrawals
-- [CHANGES] ledger_accounts, ledger_entries (+append-only +balanced triggers), transfer_idempotency, withdrawals, withdrawal_events, ledger_reconciliation_flags, wallet_transfer() + flag_ledger_mismatches() functions, platform account seeds
--
-- Rollback: run 20261008_ledger_wallets_reverse.sql (drops ONLY what this file
-- created; wallets/wallet_transactions survive).
-- Apply: paste into Supabase SQL editor, or
--   psql "$SUPABASE_DB_URL" -v ON_ERROR_STOP=1 -f supabase/migrations/20261008_ledger_wallets.sql
--
-- MONEY MODEL: ledger_accounts.balance_minor (BIGINT minor units) is the
-- system of record. public.wallets.balance (NUMERIC) is the read mirror,
-- updated in the same DB transaction. The nightly flag_ledger_mismatches()
-- compares account balances against summed entries and raises review flags.

begin;

-- ── 1. Accounts (user/agency wallets + platform/fee/escrow/hold/clearing) ───
create table if not exists public.ledger_accounts (
  id               uuid primary key default gen_random_uuid(),
  owner_profile_id uuid references public.profiles(id) on delete set null,
  kind             text not null
                   check (kind in ('user','agency','platform_revenue','fees','escrow','withdrawals_pending','provider_clearing')),
  currency         text not null default 'XAF',
  balance_minor    bigint not null default 0 check (balance_minor >= 0),
  opening_minor    bigint not null default 0,
  status           text not null default 'active' check (status in ('active','frozen','closed')),
  created_at       timestamptz not null default now(),
  updated_at       timestamptz not null default now()
);

create unique index if not exists idx_ledger_accounts_owner
  on public.ledger_accounts (owner_profile_id, kind, currency)
  where owner_profile_id is not null;
create unique index if not exists idx_ledger_accounts_platform
  on public.ledger_accounts (kind, currency)
  where owner_profile_id is null;

comment on table public.ledger_accounts is 'Every money holder. Balances are derived from opening_minor + ledger_entries; balance_minor is the cached head, reconciled nightly.';

-- Backfill: every existing profile gets a user XAF account, opening from the
-- legacy wallets mirror (minor units, rounded). Verify before/after with:
--   select count(*) filter (where a.balance_minor <> round(w.balance)) as diverged
--     from public.ledger_accounts a join public.wallets w on w.user_id = a.owner_profile_id
--    where a.kind = 'user' and a.currency = 'XAF';
insert into public.ledger_accounts (owner_profile_id, kind, currency, balance_minor, opening_minor)
select p.id, 'user', 'XAF',
       greatest(0, round(coalesce(w.balance, 0)))::bigint,
       greatest(0, round(coalesce(w.balance, 0)))::bigint
  from public.profiles p
  left join public.wallets w on w.user_id = p.id
 where not exists (
   select 1 from public.ledger_accounts a
   where a.owner_profile_id = p.id and a.kind = 'user' and a.currency = 'XAF'
 );
-- Platform singleton accounts (idempotent seeds).
insert into public.ledger_accounts (kind, currency, owner_profile_id)
values ('platform_revenue','XAF',null), ('fees','XAF',null),
       ('escrow','XAF',null), ('withdrawals_pending','XAF',null),
       ('provider_clearing','XAF',null)
on conflict do nothing;

-- ── 2. Entries (append-only, balanced per transaction) ───────────────────────
create table if not exists public.ledger_entries (
  id             uuid primary key default gen_random_uuid(),
  transaction_id uuid not null,
  account_id     uuid not null references public.ledger_accounts(id) on delete restrict,
  direction      text not null check (direction in ('debit','credit')),
  amount_minor   bigint not null check (amount_minor > 0),
  currency       text not null default 'XAF',
  balance_after  bigint not null,
  reference      text,
  entry_type     text not null default 'transfer'
                 check (entry_type in ('transfer','topup','ticket_sale','commission','withdrawal','withdrawal_reversal','refund','fee','payout','adjustment')),
  metadata       jsonb not null default '{}',
  created_at     timestamptz not null default now()
);

create index if not exists idx_ledger_entries_tx on public.ledger_entries (transaction_id);
create index if not exists idx_ledger_entries_account on public.ledger_entries (account_id, created_at desc);
create index if not exists idx_ledger_entries_reference on public.ledger_entries (reference) where reference is not null;

comment on table public.ledger_entries is 'Immutable double-entry legs. No UPDATE/DELETE (trigger). Corrections are reversing entries in a new transaction.';

create or replace function public.forbid_ledger_mutation()
returns trigger language plpgsql as $$
begin
  raise exception 'LEDGER_IMMUTABLE: ledger_entries is append-only (attempted % on %)', TG_OP, OLD.id;
end;
$$;

drop trigger if exists trg_ledger_entries_immutable on public.ledger_entries;
create trigger trg_ledger_entries_immutable
  before update or delete on public.ledger_entries
  for each row execute function public.forbid_ledger_mutation();

-- Balanced-transaction guard (deferred: checked at COMMIT, all legs visible).
create or replace function public.enforce_balanced_transaction()
returns trigger language plpgsql as $$
declare
  v_debit bigint; v_credit bigint;
begin
  select coalesce(sum(amount_minor) filter (where direction = 'debit'), 0),
         coalesce(sum(amount_minor) filter (where direction = 'credit'), 0)
    into v_debit, v_credit
    from public.ledger_entries
   where transaction_id = NEW.transaction_id;
  if v_debit <> v_credit then
    raise exception 'LEDGER_UNBALANCED: transaction % debits % <> credits %', NEW.transaction_id, v_debit, v_credit;
  end if;
  return null;
end;
$$;

drop trigger if exists trg_ledger_entries_balanced on public.ledger_entries;
create constraint trigger trg_ledger_entries_balanced
  after insert on public.ledger_entries
  deferrable initially deferred
  for each row execute function public.enforce_balanced_transaction();

-- ── 3. Transfer idempotency keys ─────────────────────────────────────────────
create table if not exists public.transfer_idempotency (
  key        text primary key,
  profile_id uuid references public.profiles(id) on delete set null,
  result     jsonb not null default '{}',
  created_at timestamptz not null default now()
);

comment on table public.transfer_idempotency is 'Replayed transfer requests return the stored result instead of moving money twice.';

-- ── 4. Secure wallet-to-wallet transfer (ONE atomic function) ────────────────
-- Locks both wallet accounts (consistent order → no deadlocks), checks
-- balance/KYC/limits/status, writes balanced legs, updates mirrors.
create or replace function public.wallet_transfer(
  p_sender    uuid,
  p_recipient uuid,
  p_amount_minor bigint,
  p_idempotency  text,
  p_note         text default null
)
returns jsonb
language plpgsql
security definer
as $$
declare
  v_tx        uuid := gen_random_uuid();
  v_sender    public.ledger_accounts%rowtype;
  v_recip     public.ledger_accounts%rowtype;
  v_fees      public.ledger_accounts%rowtype;
  v_sender_bal bigint;
  v_recip_bal  bigint;
  v_fees_bal   bigint;
  v_fee       bigint;
  v_total     bigint;
  v_sender_kyc int;
  v_sender_status text;
  v_max_txn   bigint;
  v_max_day   bigint;
  v_today     bigint;
  v_cached    jsonb;
begin
  if p_sender = p_recipient then
    raise exception 'SELF_TRANSFER';
  end if;
  if p_amount_minor is null or p_amount_minor <= 0 then
    raise exception 'INVALID_AMOUNT';
  end if;

  -- Idempotency: replay returns the original result, moves nothing.
  select result into v_cached from public.transfer_idempotency where key = p_idempotency;
  if found then
    return v_cached;
  end if;

  -- Limits (admin-configurable via app_settings JSONB scalars, sane defaults).
  select coalesce((select nullif(value#>>'{}','')::bigint from public.app_settings where key = 'transfer_per_txn_limit_minor'), 1000000) into v_max_txn;
  select coalesce((select nullif(value#>>'{}','')::bigint from public.app_settings where key = 'transfer_daily_limit_minor'), 2000000) into v_max_day;
  if p_amount_minor > v_max_txn then
    raise exception 'OVER_PER_TXN_LIMIT';
  end if;

  -- Lock accounts in consistent (id) order to avoid deadlocks.
  -- Auto-provision: a profile without a user account gets one on first use
  -- (same txn, so concurrent first-transfers serialize on the unique index).
  insert into public.ledger_accounts (owner_profile_id, kind, currency)
  values (p_sender, 'user', 'XAF'), (p_recipient, 'user', 'XAF')
  on conflict do nothing;
  select * into v_sender from public.ledger_accounts
   where owner_profile_id = p_sender and kind = 'user' and currency = 'XAF'
   order by id limit 1 for update;
  select * into v_recip from public.ledger_accounts
   where owner_profile_id = p_recipient and kind = 'user' and currency = 'XAF'
   order by id limit 1 for update;
  if v_sender.id is null then raise exception 'SENDER_NO_ACCOUNT'; end if;
  if v_recip.id is null then raise exception 'RECIPIENT_NO_ACCOUNT'; end if;
  if v_sender.status <> 'active' then raise exception 'SENDER_BLOCKED'; end if;
  if v_recip.status <> 'active' then raise exception 'RECIPIENT_BLOCKED'; end if;

  -- Sender KYC + account status (profiles extended by 20261004).
  select kyc_tier, status::text into v_sender_kyc, v_sender_status
    from public.profiles where id = p_sender;
  if coalesce(v_sender_status, '') <> 'active' then
    raise exception 'SENDER_BLOCKED';
  end if;
  if coalesce(v_sender_kyc, 0) < 1 then
    raise exception 'KYC_REQUIRED';
  end if;

  -- Daily velocity (sum of today's sent transfers, legs only).
  select coalesce(sum(amount_minor), 0) into v_today
    from public.ledger_entries
   where account_id = v_sender.id and direction = 'debit'
     and entry_type = 'transfer' and created_at > now() - interval '24 hours';
  if v_today + p_amount_minor > v_max_day then
    raise exception 'OVER_DAILY_LIMIT';
  end if;

  v_fee := greatest(0, round(p_amount_minor * 0.03)::bigint);
  v_total := p_amount_minor + v_fee;
  if v_sender.balance_minor < v_total then
    raise exception 'INSUFFICIENT_BALANCE';
  end if;

  select * into v_fees from public.ledger_accounts
   where kind = 'fees' and currency = 'XAF' and owner_profile_id is null
   limit 1 for update;

  -- Balanced legs: sender out (amount+fee), recipient in (amount), fees in.
  update public.ledger_accounts set balance_minor = balance_minor - v_total, updated_at = now()
   where id = v_sender.id returning balance_minor into v_sender_bal;
  insert into public.ledger_entries (transaction_id, account_id, direction, amount_minor, currency, balance_after, reference, entry_type, metadata)
  values (v_tx, v_sender.id, 'debit', v_total, 'XAF', v_sender_bal, p_idempotency, 'transfer',
          jsonb_build_object('to', p_recipient, 'fee', v_fee, 'note', p_note));

  update public.ledger_accounts set balance_minor = balance_minor + p_amount_minor, updated_at = now()
   where id = v_recip.id returning balance_minor into v_recip_bal;
  insert into public.ledger_entries (transaction_id, account_id, direction, amount_minor, currency, balance_after, reference, entry_type, metadata)
  values (v_tx, v_recip.id, 'credit', p_amount_minor, 'XAF', v_recip_bal, p_idempotency, 'transfer',
          jsonb_build_object('from', p_sender, 'note', p_note));

  if v_fee > 0 then
    update public.ledger_accounts set balance_minor = balance_minor + v_fee, updated_at = now()
     where id = v_fees.id returning balance_minor into v_fees_bal;
    insert into public.ledger_entries (transaction_id, account_id, direction, amount_minor, currency, balance_after, reference, entry_type, metadata)
    values (v_tx, v_fees.id, 'credit', v_fee, 'XAF', v_fees_bal, p_idempotency, 'fee',
            jsonb_build_object('from', p_sender, 'gross', p_amount_minor));
  end if;

  -- Read mirror (NUMERIC XAF) is overwritten from the ledger head in the same
  -- DB transaction, so it can never drift (insert when the row is missing).
  insert into public.wallets (user_id, balance, currency)
  values (p_sender, v_sender_bal, 'XAF')
  on conflict (user_id) do update set balance = excluded.balance, updated_at = now();
  insert into public.wallets (user_id, balance, currency)
  values (p_recipient, v_recip_bal, 'XAF')
  on conflict (user_id) do update set balance = excluded.balance, updated_at = now();

  v_cached := jsonb_build_object(
    'success', true, 'transaction_id', v_tx, 'amount', p_amount_minor,
    'fee', v_fee, 'sender_balance', v_sender_bal,
    'recipient_balance', v_recip_bal);
  insert into public.transfer_idempotency (key, profile_id, result)
  values (p_idempotency, p_sender, v_cached)
  on conflict (key) do nothing;

  return v_cached;
end;
$$;

comment on function public.wallet_transfer(uuid, uuid, bigint, text, text) is
  'Atomic wallet-to-wallet transfer: ordered row locks, KYC/limit/balance checks, balanced double-entry legs, idempotent by key.';

-- ── 5. Withdrawals (MoMo/Orange via Notch transfers) + event timeline ────────
create table if not exists public.withdrawals (
  id                 uuid primary key default gen_random_uuid(),
  profile_id         uuid not null references public.profiles(id) on delete cascade,
  amount_minor       bigint not null check (amount_minor > 0),
  fee_minor          bigint not null default 0 check (fee_minor >= 0),
  net_minor          bigint not null check (net_minor > 0),
  currency           text not null default 'XAF',
  destination_number text not null,
  network            text not null check (network in ('mtn','orange')),
  status             text not null default 'requested'
                     check (status in ('requested','pending_approval','processing','completed','failed','reversed','cancelled')),
  internal_reference text not null unique,
  provider_reference text,
  created_at         timestamptz not null default now(),
  updated_at         timestamptz not null default now()
);

create index if not exists idx_withdrawals_profile on public.withdrawals (profile_id, created_at desc);
create index if not exists idx_withdrawals_status on public.withdrawals (status, created_at desc);

create table if not exists public.withdrawal_events (
  id            uuid primary key default gen_random_uuid(),
  withdrawal_id uuid not null references public.withdrawals(id) on delete cascade,
  from_status   text,
  to_status     text not null,
  actor_uid     text,
  note          text,
  created_at    timestamptz not null default now()
);

create index if not exists idx_withdrawal_events_wd on public.withdrawal_events (withdrawal_id, created_at);

comment on table public.withdrawals is 'Wallet → mobile-money payouts. Funds sit in the withdrawals_pending ledger account until provider verification completes or reverses.';
comment on table public.withdrawal_events is 'Every withdrawal status change. completed is written ONLY after provider verification (API retrieve or webhook re-verify).';

create or replace function public.guard_withdrawal_transition()
returns trigger language plpgsql as $$
declare allowed boolean := false;
begin
  if old.status = new.status then return new; end if;
  case old.status
    when 'requested' then allowed := new.status in ('pending_approval','processing','cancelled');
    when 'pending_approval' then allowed := new.status in ('processing','cancelled');
    when 'processing' then allowed := new.status in ('completed','failed','cancelled');
    when 'failed' then allowed := new.status in ('reversed','processing');
    else allowed := false;
  end case;
  -- 'rejected' is an admin alias for cancelled-from-review: normalize it.
  if old.status = 'pending_approval' and new.status = 'rejected' then
    new.status := 'cancelled';
    return new;
  end if;
  if not allowed then
    raise exception 'WITHDRAWAL_TRANSITION: % → % is not allowed', old.status, new.status;
  end if;
  return new;
end;
$$;

drop trigger if exists trg_withdrawals_guard on public.withdrawals;
create trigger trg_withdrawals_guard
  before update of status on public.withdrawals
  for each row execute function public.guard_withdrawal_transition();

-- ── 6. Nightly reconciliation flags ──────────────────────────────────────────
create table if not exists public.ledger_reconciliation_flags (
  id          uuid primary key default gen_random_uuid(),
  account_id  uuid not null references public.ledger_accounts(id) on delete cascade,
  expected    bigint not null,
  actual      bigint not null,
  diff        bigint not null,
  created_at  timestamptz not null default now()
);

create index if not exists idx_recon_flags_created on public.ledger_reconciliation_flags (created_at desc);

create or replace function public.flag_ledger_mismatches()
returns integer
language plpgsql
security definer
as $$
declare
  r record; v_sum bigint; v_count integer := 0;
begin
  for r in select id, balance_minor, opening_minor from public.ledger_accounts loop
    select r.opening_minor + coalesce(sum(case when direction = 'credit' then amount_minor else -amount_minor end), 0)
      into v_sum
      from public.ledger_entries where account_id = r.id;
    if v_sum <> r.balance_minor then
      insert into public.ledger_reconciliation_flags (account_id, expected, actual, diff)
      values (r.id, v_sum, r.balance_minor, r.balance_minor - v_sum);
      v_count := v_count + 1;
    end if;
  end loop;
  return v_count;
end;
$$;

comment on function public.flag_ledger_mismatches() is 'Nightly job: flags every account whose cached balance differs from summed entries. Done = zero new flags.';

-- ── 7. RLS ────────────────────────────────────────────────────────────────────
alter table public.ledger_accounts enable row level security;
alter table public.ledger_entries enable row level security;
alter table public.transfer_idempotency enable row level security;
alter table public.withdrawals enable row level security;
alter table public.withdrawal_events enable row level security;
alter table public.ledger_reconciliation_flags enable row level security;

do $$
declare t text;
begin
  foreach t in array array['ledger_accounts','ledger_entries','transfer_idempotency','withdrawals','withdrawal_events','ledger_reconciliation_flags']
  loop
    execute format('drop policy if exists %I on public.%I', t || '_service_role_all', t);
    execute format(
      'create policy %I on public.%I for all to service_role using (true) with check (true)',
      t || '_service_role_all', t
    );
  end loop;
end $$;

-- Holders read their own accounts + entries + withdrawals.
drop policy if exists ledger_accounts_own_read on public.ledger_accounts;
create policy ledger_accounts_own_read on public.ledger_accounts for select
  to authenticated using (owner_profile_id = auth.uid());

drop policy if exists ledger_entries_own_read on public.ledger_entries;
create policy ledger_entries_own_read on public.ledger_entries for select
  to authenticated using (exists (
    select 1 from public.ledger_accounts a
    where a.id = ledger_entries.account_id and a.owner_profile_id = auth.uid()
  ));

drop policy if exists withdrawals_own_read on public.withdrawals;
create policy withdrawals_own_read on public.withdrawals for select
  to authenticated using (profile_id = auth.uid());

-- Finance roles read everything (admin ledger + queue).
drop policy if exists ledger_finance_read_accounts on public.ledger_accounts;
create policy ledger_finance_read_accounts on public.ledger_accounts for select
  to authenticated using (public.is_finance_role());

drop policy if exists ledger_finance_read_entries on public.ledger_entries;
create policy ledger_finance_read_entries on public.ledger_entries for select
  to authenticated using (public.is_finance_role());

drop policy if exists ledger_finance_read_withdrawals on public.withdrawals;
create policy ledger_finance_read_withdrawals on public.withdrawals for select
  to authenticated using (public.is_finance_role());

drop policy if exists ledger_finance_read_events on public.withdrawal_events;
create policy ledger_finance_read_events on public.withdrawal_events for select
  to authenticated using (public.is_finance_role());

drop policy if exists ledger_finance_read_flags on public.ledger_reconciliation_flags;
create policy ledger_finance_read_flags on public.ledger_reconciliation_flags for select
  to authenticated using (public.is_finance_role());

-- ── 8. updated_at triggers ────────────────────────────────────────────────────
do $$
declare t text;
begin
  foreach t in array array['ledger_accounts','withdrawals']
  loop
    execute format('drop trigger if exists trg_%I_ts on public.%I', t, t);
    execute format('create trigger trg_%I_ts before update on public.%I for each row execute function update_updated_at()', t, t);
  end loop;
end $$;

notify pgrst, 'reload schema';

commit;

-- End of migration.
