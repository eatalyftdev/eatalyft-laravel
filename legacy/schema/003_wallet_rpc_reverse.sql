-- ============================================================================
-- REVERSE of supabase/migrations/003_wallet_rpc.sql  (wallet RPC functions)
-- ============================================================================
-- Drops exactly what the forward added: the 4 SECURITY DEFINER wallet RPC
-- functions. Signatures byte-match the forward declarations (arg types and
-- order); DROP FUNCTION requires the exact signature, so nothing extra is
-- ever removed.
-- ============================================================================

begin;

drop function if exists public.update_wallet_balance(uuid, numeric, text, text, text, text);
drop function if exists public.credit_points(uuid, numeric, text);
drop function if exists public.redeem_points(uuid, numeric, numeric);
drop function if exists public.admin_adjust_wallet(uuid, uuid, numeric, numeric);

commit;