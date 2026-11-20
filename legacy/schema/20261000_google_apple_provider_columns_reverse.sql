-- ============================================================================
-- REVERSE of supabase/migrations/20261000_google_apple_provider_columns.sql
-- ============================================================================
-- What it undoes (exact objects created by the forward):
--   * google_uid / apple_uid columns added to public.profiles
--   * the partial indexes idx_profiles_google_uid / idx_profiles_apple_uid
--     (dropping the columns auto-drops the indexes; the explicit drops below
--     keep the reverse self-contained and idempotent)
--   * provider_signin_allowed(p_role text) guard function
-- Idempotent: re-runs are a no-op (IF EXISTS / DROP IF EXISTS).
-- NOTE (fixed): forward declares the function with signature (p_role text),
-- so DROP must use (text); earlier draft wrongly used (bigint).
-- ============================================================================

begin;

drop function if exists public.provider_signin_allowed(text);

drop index if exists public.idx_profiles_apple_uid;
drop index if exists public.idx_profiles_google_uid;

alter table public.profiles
  drop column if exists apple_uid,
  drop column if exists google_uid;

commit;