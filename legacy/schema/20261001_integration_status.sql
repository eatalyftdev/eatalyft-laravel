-- 33_integration_status.sql
-- (fresh-start delta for new project; idempotent, additive, never runs on old DB)
-- Phase 5: env-driven, runtime-visible integration/feature status.
-- Nothing below executes anywhere without explicit user go-ahead.

begin;

create table if not exists public.integration_status (
  id             bigint generated always as identity primary key,
  integration    text not null unique,
  status         text not null default 'disabled',   -- enabled | disabled | degraded
  requires       text[] not null default '{}',       -- env keys that gate it
  last_probed_at timestamptz,
  updated_at     timestamptz not null default now()
);

comment on table public.integration_status is
  'Runtime status of each feature/env-gated integration (Phase 5).';

alter table public.integration_status enable row level security;

-- Owners + superadmin read; service-role writes; admins (non-cash) update.
drop policy if exists integration_status_select on public.integration_status;
create policy integration_status_select on public.integration_status
  for select to authenticated
  using ( public.is_admin(auth.uid())
       or public.is_superadmin(auth.uid())
       or auth.uid() = (select id from public.profiles p
                          where p.id = auth.uid()) );

drop policy if exists integration_status_update on public.integration_status;
create policy integration_status_update on public.integration_status
  for update to authenticated
  using ( public.is_admin(auth.uid()) or public.is_superadmin(auth.uid()) )
  with check ( public.is_admin(auth.uid()) or public.is_superadmin(auth.uid()) );

-- Seed a fixed, known baseline (env-to-toggle wiring lives in app code, not here).
insert into public.integration_status (integration, status, requires) values
  ('google_signin', 'disabled', array['NEXT_PUBLIC_FIREBASE_API_KEY']),
  ('apple_signin',  'disabled', array['NEXT_PUBLIC_FIREBASE_API_KEY','NEXT_PUBLIC_FIREBASE_AUTH_DOMAIN'])
on conflict (integration) do nothing;

commit;
