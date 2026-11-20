-- 20261022_service_orders.sql
-- Unified ServiceOrder table for every purchasable service (bus, hotel,
-- handyman, nanny, eSIM, bill payment, gift card, game purchases, native or
-- from Boxo). One row per order; state machine driven by compare-and-set
-- transitions recorded in service_order_events.
--
-- Deliberately deny-all: service_role only. Client SDKs never touch this
-- table directly; all writes go through Next.js API routes.

CREATE TABLE IF NOT EXISTS public.service_orders (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    uid             TEXT NOT NULL,                -- Firebase UID
    service_type    TEXT NOT NULL,                -- bus | hotel | handyman | nanny | esim | giftcard | billpay | game
    amount_minor    BIGINT NOT NULL DEFAULT 0,    -- XAF minor units
    currency        TEXT NOT NULL DEFAULT 'XAF',
    status          TEXT NOT NULL DEFAULT 'created', -- created | pending_payment | paid | fulfilling | fulfilled | failed | refunded | expired | cancelled
    version         BIGINT NOT NULL DEFAULT 1,    -- CAS version
    external_ref    TEXT,                         -- miniapp order id, booking id, etc.
    payment_order_id TEXT,                        -- linked Boxo payment_order id
    metadata        JSONB NOT NULL DEFAULT '{}',
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    paid_at         TIMESTAMPTZ,
    fulfilled_at    TIMESTAMPTZ,
    failed_at       TIMESTAMPTZ,
    refunded_at     TIMESTAMPTZ,
    fail_reason     TEXT
);

CREATE INDEX IF NOT EXISTS service_orders_uid_idx ON public.service_orders (uid);
CREATE INDEX IF NOT EXISTS service_orders_status_idx ON public.service_orders (status);
CREATE INDEX IF NOT EXISTS service_orders_payment_order_id_idx ON public.service_orders (payment_order_id);
CREATE INDEX IF NOT EXISTS service_orders_external_ref_idx ON public.service_orders (external_ref);

ALTER TABLE public.service_orders ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS service_orders_deny_all ON public.service_orders;

REVOKE ALL ON public.service_orders FROM anon, authenticated;
GRANT SELECT, INSERT, UPDATE ON public.service_orders TO service_role;

COMMENT ON TABLE public.service_orders IS 'Unified ServiceOrder for every purchasable service. State machine via CAS transitions.';
COMMENT ON COLUMN public.service_orders.uid IS 'Firebase UID; joins profiles.firebase_uid in application code.';
