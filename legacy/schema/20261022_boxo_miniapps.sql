-- 20261022_boxo_miniapps.sql
-- Boxo Showroom miniapp registry (admin-managed). One row per Boxo miniapp
-- the host app is allowed to open. Drives the allowlist checked by
-- src/lib/boxo/registry.ts (isAllowedAppId) and the mobile app's miniapp
-- launcher. Populated from docs/boxo/SHOWROOM-SELECTION.md.
--
-- status: sandbox | live | hidden
-- kill_switch: when true, the miniapp must not be opened on any platform.

CREATE TABLE IF NOT EXISTS public.boxo_miniapps (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    app_id          TEXT NOT NULL UNIQUE,          -- Boxo Showroom app id (app_…)
    category        TEXT NOT NULL,                  -- games | esim | giftcards | billpay | hotels
    title_en        TEXT NOT NULL,
    title_fr        TEXT NOT NULL,
    description_en  TEXT,
    description_fr  TEXT,
    icon            TEXT,                           -- lucide icon name
    countries       TEXT[] NOT NULL DEFAULT '{}',   -- ISO country codes
    currencies      TEXT[] NOT NULL DEFAULT '{}',   -- ISO currency codes
    xaf_supported   BOOLEAN NOT NULL DEFAULT FALSE,
    status          TEXT NOT NULL DEFAULT 'sandbox', -- sandbox | live | hidden
    min_app_version TEXT,                           -- minimum host app version
    platforms       TEXT[] NOT NULL DEFAULT '{}',   -- ios | android | web
    required_user_fields TEXT[] NOT NULL DEFAULT '{}', -- reference | email | phone | name
    consent_text_en TEXT,
    consent_text_fr TEXT,
    kill_switch     BOOLEAN NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_by      TEXT,
    CONSTRAINT boxo_miniapps_status_chk CHECK (status IN ('sandbox','live','hidden')),
    CONSTRAINT boxo_miniapps_category_chk CHECK (category IN ('games','esim','giftcards','billpay','hotels'))
);

CREATE INDEX IF NOT EXISTS boxo_miniapps_app_id_idx ON public.boxo_miniapps (app_id);
CREATE INDEX IF NOT EXISTS boxo_miniapps_category_idx ON public.boxo_miniapps (category);
CREATE INDEX IF NOT EXISTS boxo_miniapps_status_idx ON public.boxo_miniapps (status);

ALTER TABLE public.boxo_miniapps ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS boxo_miniapps_select_public ON public.boxo_miniapps;
CREATE POLICY boxo_miniapps_select_public ON public.boxo_miniapps
    FOR SELECT
    USING (status = 'live' OR status = 'sandbox');

DROP POLICY IF EXISTS boxo_miniapps_write_admin ON public.boxo_miniapps;
CREATE POLICY boxo_miniapps_write_admin ON public.boxo_miniapps
    FOR ALL
    USING (false) -- service_role only; no client writes
    WITH CHECK (false);

REVOKE ALL ON public.boxo_miniapps FROM anon, authenticated;
GRANT SELECT ON public.boxo_miniapps TO authenticated;
GRANT SELECT, INSERT, UPDATE, DELETE ON public.boxo_miniapps TO service_role;

COMMENT ON TABLE public.boxo_miniapps IS 'Boxo Showroom miniapp registry. Admin-managed; drives the host-app allowlist and kill switch.';
COMMENT ON COLUMN public.boxo_miniapps.kill_switch IS 'When true, the miniapp must not be opened on any platform.';
