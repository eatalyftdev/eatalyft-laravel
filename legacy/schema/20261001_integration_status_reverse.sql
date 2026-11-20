-- ============================================================================
-- REVERSE of supabase/migrations/20261001_integration_status.sql
-- ============================================================================
-- What it undoes (byte-reverse of the forward):
--   * Drops the public.integration_status table
--   * All RLS policies attached to it drop with the table
-- Idempotent: re-runs are a no-op (DROP IF EXISTS).
-- ============================================================================

begin;

drop table if exists public.integration_status;

commit;
