-- ============================================================
-- EataLyft — Supabase Migration: Email Deliveries
-- Authoritative audit and idempotency record for transactional emails.
-- Written by server only (service role).
-- ============================================================

CREATE TABLE IF NOT EXISTS public.email_deliveries (
  id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  event_type          TEXT NOT NULL,
  recipient           TEXT NOT NULL,
  template            TEXT NOT NULL,
  provider            TEXT NOT NULL,
  provider_message_id TEXT,
  idempotency_key     TEXT UNIQUE,
  status              TEXT NOT NULL DEFAULT 'PENDING'
                      CHECK (status IN ('PENDING', 'SENT', 'FAILED', 'DEAD_LETTER')),
  attempts            INTEGER NOT NULL DEFAULT 0,
  last_error          TEXT,
  metadata            JSONB DEFAULT '{}'::jsonb,
  created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  sent_at             TIMESTAMPTZ,
  updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Indexes for performance and idempotency
CREATE INDEX IF NOT EXISTS idx_email_deliveries_idempotency
  ON public.email_deliveries (idempotency_key);

CREATE INDEX IF NOT EXISTS idx_email_deliveries_recipient
  ON public.email_deliveries (recipient, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_email_deliveries_status
  ON public.email_deliveries (status, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_email_deliveries_event
  ON public.email_deliveries (event_type, created_at DESC);

-- RLS: Server (service role) writes; authenticated users can read their own emails
ALTER TABLE public.email_deliveries ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "service_role_all" ON public.email_deliveries;
CREATE POLICY "service_role_all" ON public.email_deliveries
  FOR ALL TO service_role
  USING (true)
  WITH CHECK (true);
