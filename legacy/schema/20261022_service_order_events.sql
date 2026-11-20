-- 20261022_service_order_events.sql
-- Append-only event log for ServiceOrder state transitions. Every CAS
-- transition writes one row here; order state is never rewritten, so the
-- full history is preserved for reconciliation and audits.
--
-- Deliberately deny-all: service_role only.

CREATE TABLE IF NOT EXISTS public.service_order_events (
    id         UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id   UUID NOT NULL REFERENCES public.service_orders(id) ON DELETE CASCADE,
    version    BIGINT NOT NULL,
    from       TEXT NOT NULL,
    to         TEXT NOT NULL,
    reason     TEXT,
    metadata   JSONB NOT NULL DEFAULT '{}',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS service_order_events_order_id_idx ON public.service_order_events (order_id);
CREATE INDEX IF NOT EXISTS service_order_events_version_idx ON public.service_order_events (order_id, version);

ALTER TABLE public.service_order_events ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS service_order_events_deny_all ON public.service_order_events;

REVOKE ALL ON public.service_order_events FROM anon, authenticated;
GRANT SELECT, INSERT ON public.service_order_events TO service_role;

COMMENT ON TABLE public.service_order_events IS 'Append-only ServiceOrder transition log for reconciliation and audits.';
COMMENT ON COLUMN public.service_order_events.from IS 'Status before the transition.';
COMMENT ON COLUMN public.service_order_events.to IS 'Status after the transition.';
