-- ============================================================
-- EataLyft — Supabase Migration: Notifications mirror (Phase 1)
-- Firestore is AUTHORITATIVE for notification data. These tables are a
-- best-effort analytics/delivery mirror written by the server only
-- (service role). RLS: users read only their own rows; no client writes.
-- Reversible — see 20260921_notifications_reverse.sql.
-- Run: npx supabase db push
-- ============================================================

-- ── Mirror of Firestore notifications/{uid}/items ──────────────────
CREATE TABLE IF NOT EXISTS public.notifications_mirror (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id       TEXT NOT NULL,
  event         TEXT NOT NULL,
  category      TEXT NOT NULL DEFAULT 'system',
  priority      TEXT NOT NULL DEFAULT 'normal'
                CHECK (priority IN ('low', 'normal', 'high', 'urgent')),
  title         TEXT NOT NULL DEFAULT '',
  body          TEXT NOT NULL DEFAULT '',
  deep_link     TEXT NOT NULL DEFAULT '',
  read_at       TIMESTAMPTZ,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_notifications_mirror_user
  ON public.notifications_mirror (user_id, created_at DESC);

-- ── Mirror of Firestore notification_deliveries ─────────────────────
CREATE TABLE IF NOT EXISTS public.notification_deliveries_mirror (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id         TEXT NOT NULL,
  notification_id TEXT,
  event           TEXT NOT NULL,
  channel         TEXT NOT NULL,
  status          TEXT NOT NULL DEFAULT 'queued'
                  CHECK (status IN ('queued', 'sent', 'delivered', 'failed', 'dead')),
  provider_id     TEXT,
  error           TEXT,
  attempts        INTEGER NOT NULL DEFAULT 0,
  created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_deliveries_mirror_user
  ON public.notification_deliveries_mirror (user_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_deliveries_mirror_status
  ON public.notification_deliveries_mirror (status, created_at DESC);

-- ── RLS: own-rows read; server-only writes ──────────────────────────
ALTER TABLE public.notifications_mirror ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.notification_deliveries_mirror ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "own notifications read" ON public.notifications_mirror;
CREATE POLICY "own notifications read" ON public.notifications_mirror
  FOR SELECT TO authenticated
  USING (user_id = (auth.jwt() ->> 'uid'));

DROP POLICY IF EXISTS "own deliveries read" ON public.notification_deliveries_mirror;
CREATE POLICY "own deliveries read" ON public.notification_deliveries_mirror
  FOR SELECT TO authenticated
  USING (user_id = (auth.jwt() ->> 'uid'));

-- NOTE: realtime.send() broadcast is used server-side for PWA fan-out;
-- no realtime publication is required for these mirror tables.
