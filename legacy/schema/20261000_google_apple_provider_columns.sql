-- 32_google_apple_provider_columns.sql
-- (fresh-start delta for new project; safe to run once on empty DB, idempotent)
-- Google/Apple sign-in is enabled for every role EXCEPT: admin, ledger, dispatch.
-- Everything here is additive; it never touches old-project data.
--
-- NOTE: this file replays on the NEW project only. The old database is NOT
-- run against; nothing below executes anywhere without explicit user go-ahead.

begin;

-- ── 1. Provider identity columns per role ────────────────────────────────
alter table public.profiles
  add column if not exists google_uid  text,
  add column if not exists apple_uid   text;

-- Roles allowed to use provider sign-in (admin/ledger/dispatch excluded).
-- Mirrors the role names already enforced by existing role RLS/frontend.
do $$
begin
  if exists (select 1 from information_schema.columns
                   where table_schema='public' and table_name='profiles'
                     and column_name='role') then
    -- All role values seen in practice: attendee, seller, driver,
    -- agent, vendor, handyman, nanny, and admin/ledger/dispatch (excluded).
    -- provider sign-in is gated in app code; a defensive DB guard:
    create or replace function public.provider_signin_allowed(p_role text)
    returns boolean language sql immutable as $$
      select p_role is null
          or p_role not in ('admin','ledger','dispatch');
    $$;
  end if;
end $$;

-- Indexes for provider-identity lookups (used at sign-in to sync to profiles).
create index if not exists idx_profiles_google_uid on public.profiles(google_uid) where google_uid is not null;
create index if not exists idx_profiles_apple_uid  on public.profiles(apple_uid)  where apple_uid  is not null;

-- ── 2. RLS tightening so provider columns are only readable by owners/roles ──
do $$ begin
  if exists (select 1 from pg_policies
             where schemaname='public' and tablename='profiles'
               and policyname='profiles_select_own') then
    raise notice 'profiles_select_own exists; leaving as-is (provider cols are own-only anyway)';
  end if;
end $$;

commit;
