-- ============================================================================
-- REVERSE of supabase/migrations/004_maid_profiles_bookings.sql
-- ============================================================================
-- Drops exactly what the forward added: tables public.maid_profiles and
-- public.bookings (+ their inline indexes and the RLS policies the forward
-- created on them; indexes/policies ride along with the table drops).
-- ============================================================================

begin;

drop table if exists public.bookings;
drop table if exists public.maid_profiles;

commit;