-- 20261022_boxo_webhook_events.sql
-- Idempotency + replay-protection ledger for Boxo webhook events (miniapp
-- order events, Connect events, custom events). One row per Boxo event_id;
-- duplicates are ignored before processing.
--
-- Deliberately deny-all: service_role only.

CREATE TABLE IF NOT EXISTS public.boxo_webhook_events (
    id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id       TEXT NOT NULL UNIQUE,
    type           TEXT NOT NULL,
    payload        JSONB NOT NULL DEFAULT '{}',
    received_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    processed_at   TIMESTAMPTZ
);

CREATE INDEX IF NOT EXISTS boxo_webhook_events_event_id_idx ON public.boxo_webhook_events (event_id);
CREATE INDEX IF NOT EXISTS boxo_webhook_events_received_at_idx ON public.boxo_webhook_events (received_at);

ALTER TABLE public.boxo_webhook_events ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS boxo_webhook_events_deny_all ON public.boxo_webhook_events;

REVOKE ALL ON public.boxo_webhook_events FROM anon, authenticated;
GRANT SELECT, INSERT, UPDATE ON public.boxo_webhook_events TO service_role;

COMMENT ON TABLE public.boxo_webhook_events IS 'Boxo webhook event ledger for idempotency and replay protection.';
COMMENT ON COLUMN public.boxo_webhook_events.event_id IS 'Boxo event id; duplicates are ignored before processing.';
