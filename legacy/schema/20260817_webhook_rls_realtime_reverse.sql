-- ============================================================================
-- REVERSE of supabase/migrations/20260817_webhook_rls_realtime.sql
-- ============================================================================
-- Removes what the forward added, in dependency-safe order:
--   * trigger payments_update_webhook_trigger on payments
--   * trigger function update_webhook_processed()
--   * views failed_webhooks, payment_status_summary, wallet_balance_audit
--   * table webhook_events (+ its 6 indexes, RLS enablement and policies)
--   * helper functions is_webhook_processed, mark_webhook_processed,
--     can_update_payment_status
--   * the 14 RLS policies on payments / wallets / wallet_transactions /
--     webhook_events
-- AMBIGUITIES (recorded in MIGRATIONS-CONFORMANCE.md):
--   * is_admin() was CREATE OR REPLACE'd here over the definition from
--     002_rls.sql. Dropping it here would ALSO remove 002's version, so this
--     reverse does NOT touch it; 002_rls_reverse owns the drop in a full
--     rollback. After a partial rollback that stops here, is_admin() still
--     points at the redefined (admins table) version.
--   * REPLICA IDENTITY FULL on payments/wallets/wallet_transactions is left
--     as-is: reverting it to DEFAULT would change behaviour of unrelated
--     consumers and is not strictly "what the forward dropped".
--   * GRANTs added for service_role/authenticated are left in place (additive
--     grants; revoking them can break service-role webhook code mid-rollback).
-- ============================================================================

begin;

-- ---- Trigger + trigger function ----
drop trigger if exists payments_update_webhook_trigger on public.payments;
drop function if exists public.update_webhook_processed();

-- ---- Views ----
drop view if exists public.failed_webhooks;
drop view if exists public.payment_status_summary;
drop view if exists public.wallet_balance_audit;

-- ---- Helper functions (byte-exact signatures) ----
drop function if exists public.is_webhook_processed(text, text);
drop function if exists public.mark_webhook_processed(text, text, jsonb);
drop function if exists public.can_update_payment_status(text, text);

-- ---- Policies the forward created, per table ----
drop policy if exists payments_user_read      on public.payments;
drop policy if exists payments_service_insert on public.payments;
drop policy if exists payments_service_update on public.payments;
drop policy if exists payments_admin_read     on public.payments;

drop policy if exists wallets_user_read      on public.wallets;
drop policy if exists wallets_user_update    on public.wallets;
drop policy if exists wallets_service_update on public.wallets;
drop policy if exists wallets_admin_read     on public.wallets;

drop policy if exists wallet_tx_user_read       on public.wallet_transactions;
drop policy if exists wallet_tx_service_insert  on public.wallet_transactions;
drop policy if exists wallet_tx_admin_read      on public.wallet_transactions;

drop policy if exists webhook_events_admin_read on public.webhook_events;
drop policy if exists webhook_events_insert     on public.webhook_events;
drop policy if exists webhook_events_update     on public.webhook_events;

-- ---- The idempotency table itself (drops its indexes + RLS policies) ----
drop table if exists public.webhook_events;

commit;