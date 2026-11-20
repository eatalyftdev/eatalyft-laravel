-- RUN C — Bus trip manifests (staging/local ONLY until approved).
-- Never applied to production without explicit approval.
--
-- Manifest snapshots live in Supabase only. All writes go through the
-- Next.js API with the service role; clients get SELECT only where the
-- C2 policy allows, via SECURITY DEFINER helpers. No client
-- INSERT/UPDATE/DELETE on any of these tables.
--
-- Apply (staging):  supabase db push --db-url "$STAGING_DB_URL"
--   (or: psql "$STAGING_DB_URL" -f supabase/migrations/20261011_bus_manifests.sql)
-- Pre-flight (read-only): run the duplicate/consistency checks in
--   supabase/tests/bus_manifests_rls.sql section 0 first.
-- Rollback: psql "$STAGING_DB_URL" -f supabase/migrations/20261011_bus_manifests_reverse.sql

-- ── Tables ────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS public.bus_manifests (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  agency_id     TEXT NOT NULL,                       -- Firestore agencies/{id}
  trip_id       TEXT NOT NULL,                       -- Firestore trips/{id}
  version       INTEGER NOT NULL CHECK (version > 0),
  status        TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'final')),
  generated_by  TEXT NOT NULL,                       -- Firebase UID
  generated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  data_hash     TEXT NOT NULL,                       -- sha256 of canonical rows
  trip_snapshot JSONB NOT NULL DEFAULT '{}'::jsonb,  -- route/departure/bus/capacity
  totals        JSONB NOT NULL DEFAULT '{}'::jsonb,   -- per-agent, per-method, grand
  pdf_path      TEXT,
  pdf_sha256    TEXT,
  UNIQUE (trip_id, version)
);

CREATE TABLE IF NOT EXISTS public.bus_manifest_items (
  id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  manifest_id      UUID NOT NULL REFERENCES public.bus_manifests(id) ON DELETE CASCADE,
  ticket_id        TEXT NOT NULL,                     -- Firestore bus/{id}
  seat_label       TEXT NOT NULL,
  traveller_name   TEXT NOT NULL,
  phone            TEXT NOT NULL DEFAULT '',
  sold_by_user_id  TEXT,
  sold_by_name     TEXT,
  source           TEXT NOT NULL DEFAULT 'online' CHECK (source IN ('online', 'back_office', 'counter')),
  payment_method   TEXT NOT NULL DEFAULT 'cash',
  amount_xaf       INTEGER NOT NULL CHECK (amount_xaf >= 0),
  UNIQUE (manifest_id, ticket_id)
);

CREATE TABLE IF NOT EXISTS public.bus_manifest_downloads (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  manifest_id UUID NOT NULL REFERENCES public.bus_manifests(id) ON DELETE CASCADE,
  user_id     TEXT NOT NULL,
  downloaded_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  ip_hash     TEXT NOT NULL DEFAULT ''
);

CREATE INDEX IF NOT EXISTS idx_manifests_agency_trip ON public.bus_manifests (agency_id, trip_id);
CREATE INDEX IF NOT EXISTS idx_manifests_trip_version ON public.bus_manifests (trip_id, version DESC);
CREATE INDEX IF NOT EXISTS idx_manifest_items_manifest ON public.bus_manifest_items (manifest_id);

-- ── Private storage bucket (no client policies → clients cannot read/write) ──

INSERT INTO storage.buckets (id, name, public)
VALUES ('trip-manifests', 'trip-manifests', false)
ON CONFLICT (id) DO NOTHING;

-- ── RLS: enabled everywhere, deny by default ───────────────────────────────

ALTER TABLE public.bus_manifests ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.bus_manifest_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.bus_manifest_downloads ENABLE ROW LEVEL SECURITY;

REVOKE ALL ON public.bus_manifests FROM anon, authenticated;
REVOKE ALL ON public.bus_manifest_items FROM anon, authenticated;
REVOKE ALL ON public.bus_manifest_downloads FROM anon, authenticated;

-- Explicit service-role grants (production showed permission-denied errors
-- caused by missing privileges — do not rely on defaults).
GRANT SELECT, INSERT, UPDATE, DELETE ON public.bus_manifests TO service_role;
GRANT SELECT, INSERT, UPDATE, DELETE ON public.bus_manifest_items TO service_role;
GRANT SELECT, INSERT, UPDATE, DELETE ON public.bus_manifest_downloads TO service_role;

-- ── SECURITY DEFINER helpers (fixed search_path) for client SELECT ──────────
-- C2 policy: platform admin reads all; agency staff (membership mirror)
-- read their agency's manifests. The server API (service role) enforces the
-- full C2 matrix from Firestore membership; these policies are
-- defense-in-depth for any direct client read path.

CREATE OR REPLACE FUNCTION public.manifest_can_read(p_uid TEXT, p_manifest_id UUID)
RETURNS BOOLEAN LANGUAGE sql SECURITY DEFINER STABLE
SET search_path = public AS $$
  SELECT EXISTS (
    SELECT 1 FROM public.bus_manifests m
    WHERE m.id = p_manifest_id
      AND (
        public.is_admin()
        OR EXISTS (
          SELECT 1 FROM public.memberships mem
          JOIN public.organizations o ON o.id = mem.organization_id
          WHERE o.source_table = 'agencies'
            AND o.source_id = m.agency_id
            -- text compare: membership profile ids are uuids, callers pass
            -- Firebase UIDs; a ::uuid cast would error instead of denying.
            AND mem.profile_id::text = p_uid
        )
      )
  );
$$;

DROP POLICY IF EXISTS bus_manifests_client_read ON public.bus_manifests;
CREATE POLICY bus_manifests_client_read ON public.bus_manifests
  FOR SELECT TO authenticated
  USING (public.manifest_can_read(auth.uid()::text, id));

DROP POLICY IF EXISTS bus_manifest_items_client_read ON public.bus_manifest_items;
CREATE POLICY bus_manifest_items_client_read ON public.bus_manifest_items
  FOR SELECT TO authenticated
  USING (
    public.is_admin()
    OR EXISTS (
      SELECT 1 FROM public.bus_manifests m
      WHERE m.id = bus_manifest_items.manifest_id
        AND public.manifest_can_read(auth.uid()::text, m.id)
    )
  );

-- Downloads are audit rows: no client SELECT at all (server reads via
-- service role for the saved-manifests UI).
-- (Intentionally no policy on bus_manifest_downloads.)
