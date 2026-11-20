-- [FEATURE] In-app chat — REVERSE
-- [CHANGES] drops conversation_reports/message_receipts/messages/conversation_members/conversations tables + is_conversation_member(), removes realtime publication entries
--
-- Rollback note: DESTRUCTIVE to chat history — rows vanish. Only run before
-- go-live or after export. Publication entries are removed defensively
-- (missing table = no-op via the exception guard).

begin;

do $$
begin
  if exists (select 1 from pg_publication where pubname = 'supabase_realtime') then
    begin
      alter publication supabase_realtime drop table public.messages;
    exception when undefined_object then null;
    end;
    begin
      alter publication supabase_realtime drop table public.conversations;
    exception when undefined_object then null;
    end;
  end if;
end $$;

drop table if exists public.conversation_reports;
drop table if exists public.message_receipts;
drop table if exists public.messages;
drop table if exists public.conversation_members;
drop table if exists public.conversations;

drop function if exists public.is_conversation_member(uuid);

notify pgrst, 'reload schema';

commit;

-- End of reverse migration.
