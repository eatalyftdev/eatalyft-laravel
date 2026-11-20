-- [FEATURE] In-app chat: conversations, members, messages, receipts, reports
-- [CHANGES] conversations, conversation_members, messages, message_receipts, conversation_reports tables, realtime publication, RLS
--
-- Rollback: run 20261009_chat_reverse.sql (drops ONLY what this file created).
-- Apply: paste into Supabase SQL editor, or
--   psql "$SUPABASE_DB_URL" -v ON_ERROR_STOP=1 -f supabase/migrations/20261009_chat.sql
--
-- MODEL: members-only RLS (only conversation members read/write). Realtime:
-- messages is added to the supabase_realtime publication so PWA/Expo clients
-- get live inserts (RLS-filtered per member). Push fan-out happens server-side
-- in POST /api/chat (notifyEvent 'message.received' → inapp/webpush/expo).
-- Device tokens + per-user prefs already live in Firestore (notify system).

begin;

-- ── 1. Conversations ─────────────────────────────────────────────────────────
create table if not exists public.conversations (
  id             uuid primary key default gen_random_uuid(),
  kind           text not null default 'direct'
                  check (kind in ('direct','support','parcel','order','group')),
  subject_entity text,
  created_by     uuid references public.profiles(id) on delete set null,
  created_at     timestamptz not null default now(),
  updated_at     timestamptz not null default now()
);

-- ── 2. Members (one row per participant; block/mute live here) ───────────────
create table if not exists public.conversation_members (
  id              uuid primary key default gen_random_uuid(),
  conversation_id uuid not null references public.conversations(id) on delete cascade,
  profile_id      uuid not null references public.profiles(id) on delete cascade,
  role            text not null default 'member' check (role in ('owner','member','support')),
  muted           boolean not null default false,
  blocked_profile_id uuid references public.profiles(id) on delete set null,
  last_read_at    timestamptz,
  created_at      timestamptz not null default now(),
  constraint conversation_members_unique unique (conversation_id, profile_id)
);

create index if not exists idx_conversation_members_profile on public.conversation_members (profile_id, conversation_id);

comment on column public.conversation_members.blocked_profile_id is 'Set by the blocker: they no longer receive messages from this profile in this conversation.';

-- ── 3. Messages (soft-delete only; history is never rewritten) ──────────────
create table if not exists public.messages (
  id              uuid primary key default gen_random_uuid(),
  conversation_id uuid not null references public.conversations(id) on delete cascade,
  sender_id       uuid references public.profiles(id) on delete set null,
  body            text not null check (char_length(body) between 1 and 4000),
  attachments     jsonb not null default '[]',
  edited_at       timestamptz,
  deleted_at      timestamptz,
  created_at      timestamptz not null default now()
);

create index if not exists idx_messages_conversation on public.messages (conversation_id, created_at);

comment on column public.messages.deleted_at is 'Soft delete: clients hide the body but the row stays for moderation.';

-- ── 4. Read receipts ─────────────────────────────────────────────────────────
create table if not exists public.message_receipts (
  id         uuid primary key default gen_random_uuid(),
  message_id uuid not null references public.messages(id) on delete cascade,
  profile_id uuid not null references public.profiles(id) on delete cascade,
  read_at    timestamptz not null default now(),
  constraint message_receipts_unique unique (message_id, profile_id)
);

-- ── 5. Block/report queue ────────────────────────────────────────────────────
create table if not exists public.conversation_reports (
  id              uuid primary key default gen_random_uuid(),
  conversation_id uuid not null references public.conversations(id) on delete cascade,
  reporter_id     uuid not null references public.profiles(id) on delete cascade,
  reported_id     uuid references public.profiles(id) on delete set null,
  reason          text not null,
  status          text not null default 'open' check (status in ('open','reviewing','resolved','dismissed')),
  created_at      timestamptz not null default now()
);

create index if not exists idx_conversation_reports_status on public.conversation_reports (status, created_at desc);

-- ── 6. Realtime publication (member-filtered by RLS) ──────────────────────────
do $$
begin
  if exists (select 1 from pg_publication where pubname = 'supabase_realtime') then
    begin
      alter publication supabase_realtime add table public.messages;
      alter publication supabase_realtime add table public.conversations;
    exception when duplicate_object then null;
             when undefined_object then null;
    end;
  end if;
end $$;

-- ── 7. RLS: members-only reads/writes + service_role + admin ─────────────────
alter table public.conversations enable row level security;
alter table public.conversation_members enable row level security;
alter table public.messages enable row level security;
alter table public.message_receipts enable row level security;
alter table public.conversation_reports enable row level security;

do $$
declare t text;
begin
  foreach t in array array['conversations','conversation_members','messages','message_receipts','conversation_reports']
  loop
    execute format('drop policy if exists %I on public.%I', t || '_service_role_all', t);
    execute format(
      'create policy %I on public.%I for all to service_role using (true) with check (true)',
      t || '_service_role_all', t
    );
  end loop;
end $$;

-- Helper: is the caller a member of the conversation?
create or replace function public.is_conversation_member(c uuid)
returns boolean language sql security definer stable as $$
  select exists (
    select 1 from public.conversation_members m
    where m.conversation_id = c and m.profile_id = auth.uid()
  );
$$;

-- Conversations: members read; creation via API (service role).
drop policy if exists conversations_member_read on public.conversations;
create policy conversations_member_read on public.conversations for select
  to authenticated using (public.is_conversation_member(conversations.id) or public.is_admin());

-- Members: members read the roster; writes via API.
drop policy if exists conversation_members_roster_read on public.conversation_members;
create policy conversation_members_roster_read on public.conversation_members for select
  to authenticated using (
    public.is_conversation_member(conversation_members.conversation_id) or public.is_admin()
  );

-- Messages: members read; send via API (service role) so rate limits, blocks
-- and push fan-out stay server-side.
drop policy if exists messages_member_read on public.messages;
create policy messages_member_read on public.messages for select
  to authenticated using (
    public.is_conversation_member(messages.conversation_id) or public.is_admin()
  );

-- Receipts: members read receipts in their conversations.
drop policy if exists receipts_member_read on public.message_receipts;
create policy receipts_member_read on public.message_receipts for select
  to authenticated using (
    exists (
      select 1 from public.messages m
      where m.id = message_receipts.message_id
        and public.is_conversation_member(m.conversation_id)
    ) or public.is_admin()
  );

-- Reports: anyone authenticated can file; admins read.
drop policy if exists reports_insert_own on public.conversation_reports;
create policy reports_insert_own on public.conversation_reports for insert
  to authenticated with check (reporter_id = auth.uid());

drop policy if exists reports_admin_read on public.conversation_reports;
create policy reports_admin_read on public.conversation_reports for select
  to authenticated using (public.is_admin());

-- ── 8. updated_at trigger ─────────────────────────────────────────────────────
do $$
begin
  execute 'drop trigger if exists trg_conversations_ts on public.conversations';
  execute 'create trigger trg_conversations_ts before update on public.conversations for each row execute function update_updated_at()';
end $$;

notify pgrst, 'reload schema';

commit;

-- End of migration.
