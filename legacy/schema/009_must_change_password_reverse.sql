-- ============================================================================
-- REVERSE of supabase/migrations/009_must_change_password.sql
-- ============================================================================
-- Drops exactly what the forward added: the two columns on public.profiles
-- (must_change_password, last_seen_at) plus their comments (drop with the
-- columns). This is the inverse of the ADD COLUMN the forward performed.
--
-- DEPENDENCY: migration 20260605 reads/writes must_change_password. In a full
-- rollback run the reverses newest-first (20260605_reverse BEFORE this file).
-- ============================================================================

begin;

alter table public.profiles
  drop column if exists must_change_password;

alter table public.profiles
  drop column if exists last_seen_at;

commit;