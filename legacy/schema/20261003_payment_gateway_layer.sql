-- [FEATURE] Payment gateway layer (Notch Pay primary, MeSomb fallback)
-- [CHANGES] payment_providers, payment_attempts, gateway_webhook_events tables, indexes, RLS policies, provider seeds
--
-- Rollback: run 20261003_payment_gateway_layer_reverse.sql (drops exactly these
-- three tables; policies drop with them). Safe: Firestore transactions/{ref}
-- remain the canonical payment ledger; these tables are operational mirrors.
-- Apply: paste into Supabase SQL editor, or
--   psql "$SUPABASE_DB_URL" -v ON_ERROR_STOP=1 -f supabase/migrations/20261003_payment_gateway_layer.sql

begin;

-- ── 1. Provider routing config (DB source of truth, edited from admin) ──────
create table if not exists public.payment_providers (
  name              text primary key,
  enabled           boolean not null default true,
  priority          integer not null default 100,
  health_status     text not null default 'unknown',
  last_checked_at   timestamptz,
  failure_count     integer not null default 0,
  circuit_state     text not null default 'closed',
  circuit_opened_at timestamptz,
  created_at        timestamptz not null default now(),
  updated_at        timestamptz not null default now(),
  constraint payment_providers_health_check
    check (health_status in ('operational', 'degraded', 'outage', 'unknown')),
  constraint payment_providers_circuit_check
    check (circuit_state in ('closed', 'open', 'half_open')),
  constraint payment_providers_priority_check
    check (priority >= 0),
  constraint payment_providers_failures_check
    check (failure_count >= 0)
);

comment on table public.payment_providers is
  'Gateway routing config. Order: lowest priority first (1 = Notch Pay primary, 2 = MeSomb fallback). Edited from admin Third-Party Configuration.';
comment on column public.payment_providers.circuit_state is
  'closed = normal; open = failing (3 failures in 2 min, 5 min cooldown); half_open = single probe allowed.';

-- Seeds: Notch Pay primary (1), MeSomb fallback (2). Never duplicates.
insert into public.payment_providers (name, enabled, priority, health_status)
values ('notchpay', true, 1, 'unknown'),
       ('mesomb',   true, 2, 'unknown')
on conflict (name) do nothing;

-- ── 2. Payment attempts (ONE row per provider try, same internal reference) ──
create table if not exists public.payment_attempts (
  id                      uuid primary key default gen_random_uuid(),
  internal_reference      text not null,
  provider                text not null,
  provider_reference      text,
  provider_transaction_id text,
  status                  text not null default 'pending',
  amount_minor            bigint not null default 0,
  currency                text not null default 'XAF',
  fees_minor              bigint not null default 0,
  fallback_from           text,
  fallback_reason         text,
  raw_webhook             jsonb not null default '{}',
  created_at              timestamptz not null default now(),
  updated_at              timestamptz not null default now(),
  constraint payment_attempts_status_check
    check (status in ('pending', 'processing', 'success', 'failed', 'review')),
  constraint payment_attempts_amount_check
    check (amount_minor >= 0 and fees_minor >= 0)
);

comment on table public.payment_attempts is
  'Every gateway try under ONE internal_reference. No-double-charge audit: fallback retries reuse the same internal_reference with fallback_from set.';
comment on column public.payment_attempts.fallback_reason is
  'Why this provider was tried: disabled | missing_keys | health_failing | timeout | http_5xx | network_error | circuit_open | unknown_outcome_verified.';

create unique index if not exists idx_payment_attempts_ref_provider
  on public.payment_attempts (internal_reference, provider);
create index if not exists idx_payment_attempts_created
  on public.payment_attempts (created_at desc);
create index if not exists idx_payment_attempts_status
  on public.payment_attempts (status, created_at desc);

-- ── 3. Webhook dedup (idempotency: provider + event id is unique) ────────────
-- NAME NOTE: the legacy MeSomb-era `webhook_events` table (master schema §1:
-- global-UNIQUE event_id + NOT NULL transaction_ref) is left untouched for
-- drain compatibility. This feature uses `gateway_webhook_events`, whose
-- (provider, event_id) key supports per-provider dedup for BOTH gateways.
create table if not exists public.gateway_webhook_events (
  id                 uuid primary key default gen_random_uuid(),
  provider           text not null,
  event_id           text not null,
  internal_reference text,
  payload            jsonb not null default '{}',
  status             text not null default 'received',
  received_at        timestamptz not null default now(),
  processed_at       timestamptz,
  constraint gateway_webhook_events_status_check
    check (status in ('received', 'processed', 'duplicate', 'rejected'))
);

comment on table public.gateway_webhook_events is
  'Raw gateway webhook log + replay protection. Unique (provider, event_id): replays become duplicates, never double credits.';

create unique index if not exists idx_gateway_webhook_events_provider_event
  on public.gateway_webhook_events (provider, event_id);
create index if not exists idx_gateway_webhook_events_reference
  on public.gateway_webhook_events (internal_reference) where internal_reference is not null;

-- ── 4. RLS (deny-by-default; server/service-role only — clients use API) ─────
alter table public.payment_providers enable row level security;
alter table public.payment_attempts enable row level security;
alter table public.gateway_webhook_events   enable row level security;

drop policy if exists payment_providers_service_role_all on public.payment_providers;
create policy payment_providers_service_role_all
  on public.payment_providers for all
  to service_role
  using (true) with check (true);

drop policy if exists payment_attempts_service_role_all on public.payment_attempts;
create policy payment_attempts_service_role_all
  on public.payment_attempts for all
  to service_role
  using (true) with check (true);

drop policy if exists gateway_webhook_events_service_role_all on public.gateway_webhook_events;
create policy gateway_webhook_events_service_role_all
  on public.gateway_webhook_events for all
  to service_role
  using (true) with check (true);

-- ── 5. Refresh PostgREST schema cache ─────────────────────────────────────────
notify pgrst, 'reload schema';

commit;

-- End of migration.
