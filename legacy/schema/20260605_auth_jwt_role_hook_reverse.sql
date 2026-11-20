-- ============================================================================
-- REVERSE of supabase/migrations/20260605_auth_jwt_role_hook.sql
-- ============================================================================
-- Removes what the forward added:
--   * Trigger trg_sync_role_to_auth on public.profiles
--   * Functions custom_access_token_hook(jsonb), sync_user_role_to_auth()
--   * Restores the EXECUTE grants the forward revoked/added
-- NOT reversed (documented remainder, not schema):
--   * The one-time backfill of auth.users.raw_app_meta_data performed by the
--     forward (a data write; raw_app_meta_data of existing users is left as-is).
-- ORDERING: in a full rollback this runs before 009_reverse and 002_reverse
-- (newest-first); after this reverse runs, jwt hook is gone and role/status
-- stop flowing into tokens.
-- ============================================================================

begin;

-- ---- Trigger created by the forward ----
drop trigger if exists trg_sync_role_to_auth on public.profiles;

-- ---- Functions created by the forward (byte-exact signatures) ----
drop function if exists public.custom_access_token_hook(jsonb);
drop function if exists public.sync_user_role_to_auth();

-- ---- Restore the grant state as it was before this migration ----
revoke execute on function public.custom_access_token_hook(jsonb) from supabase_auth_admin;
grant execute on function public.custom_access_token_hook(jsonb) to public, anon, authenticated;

commit;