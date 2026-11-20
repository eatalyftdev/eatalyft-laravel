-- ============================================================================
-- REVERSE of supabase/migrations/20260820_payments_firebase_ref_unique.sql
-- ============================================================================
-- Drops the UNIQUE constraint payments_firebase_ref_key on public.payments
-- (and its comment) that the forward added in one ALTER TABLE.
-- Ordering note: must run before 20260607_geo_services_reverse drops the
-- payments table. The constraint is also auto-removed if the table is ever
-- dropped; this reverse keeps the dependency explicit and reversible alone.
-- ============================================================================

begin;

alter table public.payments
  drop constraint if exists payments_firebase_ref_key;

notify pgrst, 'reload schema';

commit;