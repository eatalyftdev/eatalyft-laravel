-- 20261022_boxo_consents.sql
-- Consent records for Boxo Connect: which Boxo miniapps each EataLyft user has
-- authorized, and when consent was revoked. One row per (uid, app_id).
--
-- uid is the Firebase UID (profiles.firebase_uid). No FK to profiles: Boxo
-- identities live outside Supabase Auth, so the join is done by application
-- code on firebase_uid when needed.
--
-- Deliberately deny-all: service_role only. Client SDKs never touch this table.

CREATE TABLE IF NOT EXISTS public.boxo_consents (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    uid             TEXT NOT NULL,
    app_id          TEXT NOT NULL,
    granted_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    revoked_at      TIMESTAMPTZ,
    scopes          TEXT[] NOT NULL DEFAULT '{}',
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT boxo_consents_uid_app_id_key UNIQUE (uid, app_id)
);

CREATE INDEX IF NOT EXISTS boxo_consents_uid_idx ON public.boxo_consents (uid);
CREATE INDEX IF NOT EXISTS boxo_consents_app_id_idx ON public.boxo_consents (app_id);

ALTER TABLE public.boxo_consents ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS boxo_consents_deny_all ON public.boxo_consents;
-- No client policy: rows are readable/writable only via service_role (server
-- routes). Default-deny after enabling RLS with no policies attached.

REVOKE ALL ON public.boxo_consents FROM anon, authenticated;
GRANT SELECT, INSERT, UPDATE ON public.boxo_consents TO service_role;

COMMENT ON TABLE public.boxo_consents IS 'Boxo Connect consent records (uid, app_id). Revoked rows keep history.';
COMMENT ON COLUMN public.boxo_consents.uid IS 'Firebase UID; joins profiles.firebase_uid in application code.';
