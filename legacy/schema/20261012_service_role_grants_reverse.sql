-- D1 rollback — reverses 20261012_service_role_grants.sql (staging only).
-- Rollback: psql "$STAGING_DB_URL" -f supabase/migrations/20261012_service_role_grants_reverse.sql
-- NOTE: this returns privileges to their pre-repair state, which REINTRODUCES
-- the permission-denied symptom. Only roll back to diagnose, then re-apply.

ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE ALL ON FUNCTIONS FROM service_role;
ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE ALL ON SEQUENCES FROM service_role;
ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE ALL ON TABLES FROM service_role;

REVOKE ALL ON ALL FUNCTIONS IN SCHEMA public FROM service_role;
REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM service_role;
REVOKE ALL ON ALL TABLES IN SCHEMA public FROM service_role;
REVOKE USAGE ON SCHEMA public FROM service_role;
