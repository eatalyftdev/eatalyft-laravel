-- D1 — service_role privilege repair (staging FIRST, production only on approval).
-- Symptom: `permission denied for table X` (NOT an RLS message) on profiles,
-- rides, food_orders, parcels, listings, kyc_profiles, app_settings,
-- role_permissions, *_profiles. Cause: missing GRANTs (possibly after a key
-- rotation or a restore that dropped them). RLS cannot fix this, and the
-- service role bypass does not substitute for missing privileges.
--
-- Scope discipline: service_role ONLY. Nothing for anon/authenticated, no RLS
-- changes, no USING(true) policies. Idempotent: safe to re-run.
--
-- Apply (staging):  psql "$STAGING_DB_URL" -f supabase/migrations/20261012_service_role_grants.sql
-- Verify:           psql "$STAGING_DB_URL" -f supabase/tests/grants_audit.sql
--                    (section 4 must show zero denied tables)
-- Rollback:         psql "$STAGING_DB_URL" -f supabase/migrations/20261012_service_role_grants_reverse.sql

-- Schema usage (required before any table privilege is usable).
GRANT USAGE ON SCHEMA public TO service_role;

-- Existing objects: tables (+views), sequences, functions.
GRANT ALL ON ALL TABLES IN SCHEMA public TO service_role;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO service_role;
GRANT ALL ON ALL FUNCTIONS IN SCHEMA public TO service_role;

-- Future objects created by any role get the same service_role grants.
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO service_role;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO service_role;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON FUNCTIONS TO service_role;
