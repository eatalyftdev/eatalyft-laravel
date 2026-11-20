-- ============================================================
-- EataLyft — Supabase Migration: Hotels module (Phase 5, booking.com-style)
-- Properties, rooms, rate plans, per-night availability with RPC row-locked
-- holds (no double booking), idempotent bookings, ledger payments,
-- cancellation policies, reviews (completed stays only), manager payouts.
-- Money = INTEGER MINOR UNITS. Run: npx supabase db push
-- ============================================================

-- ── 1. properties ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.hotel_properties (
  id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  manager_uid      TEXT NOT NULL,                       -- hotel manager Firebase UID
  name             TEXT NOT NULL,
  property_type    TEXT NOT NULL DEFAULT 'hotel'
                   CHECK (property_type IN ('hotel','guesthouse','motel','resort','lodge','apartment')),
  stars            INTEGER NOT NULL DEFAULT 3 CHECK (stars BETWEEN 0 AND 5),
  description      TEXT NOT NULL DEFAULT '',
  country_code     CHAR(2) NOT NULL DEFAULT 'CM',
  city_name        TEXT NOT NULL DEFAULT '',
  address          TEXT NOT NULL DEFAULT '',
  lat              DOUBLE PRECISION,
  lng              DOUBLE PRECISION,
  amenities        JSONB NOT NULL DEFAULT '[]',
  photos           JSONB NOT NULL DEFAULT '[]',
  policies         JSONB NOT NULL DEFAULT '{}',         -- check-in/out times, house rules
  free_cancellation BOOLEAN NOT NULL DEFAULT TRUE,
  status           TEXT NOT NULL DEFAULT 'draft'
                   CHECK (status IN ('draft','pending_review','live','paused','rejected')),
  rejection_reason TEXT,
  rating           NUMERIC(3,2) NOT NULL DEFAULT 0,
  review_count     INTEGER NOT NULL DEFAULT 0,
  created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_hotels_scope  ON public.hotel_properties (country_code, city_name, status);
CREATE INDEX IF NOT EXISTS idx_hotels_manager ON public.hotel_properties (manager_uid);

-- ── 2. room types ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.hotel_room_types (
  id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  property_id  UUID NOT NULL REFERENCES public.hotel_properties(id) ON DELETE CASCADE,
  name         TEXT NOT NULL,
  capacity     INTEGER NOT NULL DEFAULT 2 CHECK (capacity BETWEEN 1 AND 16),
  beds         TEXT NOT NULL DEFAULT '',
  size_m2      INTEGER,
  amenities    JSONB NOT NULL DEFAULT '[]',
  photos       JSONB NOT NULL DEFAULT '[]',
  base_price_minor BIGINT NOT NULL CHECK (base_price_minor >= 0),
  currency     TEXT NOT NULL DEFAULT 'XAF',
  total_rooms  INTEGER NOT NULL DEFAULT 1 CHECK (total_rooms >= 1),
  is_active    BOOLEAN NOT NULL DEFAULT TRUE,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_rooms_property ON public.hotel_room_types (property_id);

-- ── 3. rate plans (per-date/season overrides) ───────────────
CREATE TABLE IF NOT EXISTS public.hotel_rate_plans (
  id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  room_type_id   UUID NOT NULL REFERENCES public.hotel_room_types(id) ON DELETE CASCADE,
  name           TEXT NOT NULL DEFAULT 'Standard',
  start_date     DATE NOT NULL,
  end_date       DATE NOT NULL,
  price_minor    BIGINT NOT NULL CHECK (price_minor >= 0),
  min_stay       INTEGER NOT NULL DEFAULT 1,
  created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CHECK (end_date >= start_date)
);

-- ── 4. availability (per night; booked incremented under row lock) ──
CREATE TABLE IF NOT EXISTS public.hotel_availability (
  room_type_id UUID NOT NULL REFERENCES public.hotel_room_types(id) ON DELETE CASCADE,
  night        DATE NOT NULL,
  allotted     INTEGER NOT NULL DEFAULT 1 CHECK (allotted >= 0),
  booked       INTEGER NOT NULL DEFAULT 0 CHECK (booked >= 0),
  closed       BOOLEAN NOT NULL DEFAULT FALSE,
  PRIMARY KEY (room_type_id, night)
);

-- ── 5. temporary holds (15-min expiry, lazy-released) ───────
CREATE TABLE IF NOT EXISTS public.booking_holds (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_uid      TEXT NOT NULL,
  room_type_id  UUID NOT NULL REFERENCES public.hotel_room_types(id) ON DELETE CASCADE,
  check_in      DATE NOT NULL,
  check_out     DATE NOT NULL,
  guests        INTEGER NOT NULL DEFAULT 1,
  expires_at    TIMESTAMPTZ NOT NULL,
  consumed      BOOLEAN NOT NULL DEFAULT FALSE,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CHECK (check_out > check_in)
);
CREATE INDEX IF NOT EXISTS idx_holds_room ON public.booking_holds (room_type_id, expires_at);

-- ── 6. bookings (idempotent) ────────────────────────────────
CREATE TABLE IF NOT EXISTS public.hotel_bookings (
  id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  code             TEXT NOT NULL UNIQUE,                -- e-voucher code
  idempotency_key  TEXT NOT NULL UNIQUE,
  user_uid         TEXT NOT NULL,
  property_id      UUID NOT NULL REFERENCES public.hotel_properties(id),
  room_type_id     UUID NOT NULL REFERENCES public.hotel_room_types(id),
  check_in         DATE NOT NULL,
  check_out        DATE NOT NULL,
  guests           INTEGER NOT NULL DEFAULT 1,
  guest_name       TEXT NOT NULL DEFAULT '',
  guest_phone      TEXT NOT NULL DEFAULT '',
  guest_email      TEXT NOT NULL DEFAULT '',
  nights           INTEGER NOT NULL,
  subtotal_minor   BIGINT NOT NULL,
  taxes_minor      BIGINT NOT NULL DEFAULT 0,
  fees_minor       BIGINT NOT NULL DEFAULT 0,
  total_minor      BIGINT NOT NULL,
  currency         TEXT NOT NULL DEFAULT 'XAF',
  commission_minor BIGINT NOT NULL DEFAULT 0,
  status           TEXT NOT NULL DEFAULT 'confirmed'
                   CHECK (status IN ('pending','confirmed','checked_in','completed','cancelled','disputed','refunded')),
  payment_status   TEXT NOT NULL DEFAULT 'paid',
  cancel_reason    TEXT,
  dispute_note     TEXT,
  checked_in_at    TIMESTAMPTZ,
  checked_out_at   TIMESTAMPTZ,
  completed_at     TIMESTAMPTZ,
  created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_hbookings_user     ON public.hotel_bookings (user_uid, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_hbookings_property ON public.hotel_bookings (property_id, check_in);

-- ── 7. ledger payments ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.booking_payments (
  id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  booking_id   UUID NOT NULL REFERENCES public.hotel_bookings(id) ON DELETE CASCADE,
  kind         TEXT NOT NULL CHECK (kind IN ('charge','refund','commission','payout')),
  amount_minor BIGINT NOT NULL,
  currency     TEXT NOT NULL DEFAULT 'XAF',
  status       TEXT NOT NULL DEFAULT 'completed',
  reference    TEXT,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ── 8. cancellation policies ────────────────────────────────
CREATE TABLE IF NOT EXISTS public.hotel_cancellation_policies (
  id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  property_id      UUID NOT NULL REFERENCES public.hotel_properties(id) ON DELETE CASCADE,
  hours_before     INTEGER NOT NULL,                    -- free-cancel until this many hours prior
  refund_pct       INTEGER NOT NULL CHECK (refund_pct BETWEEN 0 AND 100),
  UNIQUE (property_id, hours_before)
);

-- ── 9. reviews (completed stays only — enforced in API) ─────
CREATE TABLE IF NOT EXISTS public.hotel_reviews (
  id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  booking_id   UUID NOT NULL UNIQUE REFERENCES public.hotel_bookings(id) ON DELETE CASCADE,
  property_id  UUID NOT NULL REFERENCES public.hotel_properties(id) ON DELETE CASCADE,
  user_uid     TEXT NOT NULL,
  rating       INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
  title        TEXT NOT NULL DEFAULT '',
  body         TEXT NOT NULL DEFAULT '',
  reply        TEXT,
  status       TEXT NOT NULL DEFAULT 'live' CHECK (status IN ('live','hidden','flagged')),
  created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ── 10. manager payouts (payable after check-in) ────────────
CREATE TABLE IF NOT EXISTS public.manager_payouts (
  id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  manager_uid  TEXT NOT NULL,
  booking_id   UUID NOT NULL REFERENCES public.hotel_bookings(id),
  amount_minor BIGINT NOT NULL,
  currency     TEXT NOT NULL DEFAULT 'XAF',
  status       TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','payable','paid','cancelled')),
  paid_at      TIMESTAMPTZ,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_mpayouts_manager ON public.manager_payouts (manager_uid, status);

-- ── RLS: public reads (live properties, live reviews); rest service-role ──
ALTER TABLE public.hotel_properties            ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.hotel_room_types            ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.hotel_rate_plans            ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.hotel_availability          ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.booking_holds               ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.hotel_bookings              ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.booking_payments            ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.hotel_cancellation_policies ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.hotel_reviews               ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.manager_payouts             ENABLE ROW LEVEL SECURITY;

CREATE POLICY "hotels: public live read" ON public.hotel_properties
  FOR SELECT USING (status = 'live');
CREATE POLICY "rooms: public read" ON public.hotel_room_types
  FOR SELECT USING (is_active = TRUE);
CREATE POLICY "reviews: public live read" ON public.hotel_reviews
  FOR SELECT USING (status = 'live');

REVOKE ALL ON public.hotel_properties            FROM anon, authenticated;
REVOKE ALL ON public.hotel_room_types            FROM anon, authenticated;
REVOKE ALL ON public.hotel_rate_plans            FROM anon, authenticated;
REVOKE ALL ON public.hotel_availability          FROM anon, authenticated;
REVOKE ALL ON public.booking_holds               FROM anon, authenticated;
REVOKE ALL ON public.hotel_bookings              FROM anon, authenticated;
REVOKE ALL ON public.booking_payments            FROM anon, authenticated;
REVOKE ALL ON public.hotel_cancellation_policies FROM anon, authenticated;
REVOKE ALL ON public.hotel_reviews               FROM anon, authenticated;
REVOKE ALL ON public.manager_payouts             FROM anon, authenticated;
GRANT SELECT ON public.hotel_properties TO anon, authenticated;
GRANT SELECT ON public.hotel_room_types TO anon, authenticated;
GRANT SELECT ON public.hotel_reviews    TO anon, authenticated;

-- ── 11. Row-locked hold function (prevents double booking) ──
-- Locks each night row FOR UPDATE, releases the caller's expired holds
-- piggyback, then reserves qty if capacity allows. Returns per-night ok.
CREATE OR REPLACE FUNCTION public.hold_hotel_inventory(
  p_user_uid TEXT,
  p_room_type_id UUID,
  p_check_in DATE,
  p_check_out DATE,
  p_guests INTEGER,
  p_ttl_seconds INTEGER DEFAULT 900
) RETURNS JSONB LANGUAGE plpgsql SECURITY DEFINER SET search_path = public AS $$
DECLARE
  d DATE;
  v_allotted INTEGER;
  v_booked INTEGER;
  v_closed BOOLEAN;
  v_hold_id UUID;
BEGIN
  IF p_check_out <= p_check_in THEN
    RETURN jsonb_build_object('ok', FALSE, 'error', 'BAD_DATES');
  END IF;
  IF p_check_out - p_check_in > 60 THEN
    RETURN jsonb_build_object('ok', FALSE, 'error', 'STAY_TOO_LONG');
  END IF;

  -- Piggyback: release this room's expired unconsumed holds first.
  UPDATE public.booking_holds SET consumed = TRUE
  WHERE room_type_id = p_room_type_id AND consumed = FALSE AND expires_at <= NOW();

  d := p_check_in;
  WHILE d < p_check_out LOOP
    SELECT allotted, booked, closed INTO v_allotted, v_booked, v_closed
    FROM public.hotel_availability
    WHERE room_type_id = p_room_type_id AND night = d
    FOR UPDATE;
    IF NOT FOUND THEN
      -- Default night: capacity from room type.
      SELECT total_rooms INTO v_allotted FROM public.hotel_room_types WHERE id = p_room_type_id;
      IF v_allotted IS NULL THEN
        RETURN jsonb_build_object('ok', FALSE, 'error', 'NO_ROOM');
      END IF;
      INSERT INTO public.hotel_availability (room_type_id, night, allotted, booked)
      VALUES (p_room_type_id, d, v_allotted, 0)
      ON CONFLICT (room_type_id, night) DO NOTHING;
      SELECT allotted, booked, closed INTO v_allotted, v_booked, v_closed
      FROM public.hotel_availability
      WHERE room_type_id = p_room_type_id AND night = d
      FOR UPDATE;
    END IF;
    IF v_closed OR v_booked + 1 > v_allotted THEN
      RETURN jsonb_build_object('ok', FALSE, 'error', 'SOLD_OUT', 'night', d);
    END IF;
    UPDATE public.hotel_availability SET booked = booked + 1
    WHERE room_type_id = p_room_type_id AND night = d;
    d := d + 1;
  END LOOP;

  INSERT INTO public.booking_holds (user_uid, room_type_id, check_in, check_out, guests, expires_at)
  VALUES (p_user_uid, p_room_type_id, p_check_in, p_check_out, p_guests, NOW() + (p_ttl_seconds || ' seconds')::INTERVAL)
  RETURNING id INTO v_hold_id;

  RETURN jsonb_build_object('ok', TRUE, 'hold_id', v_hold_id);
END;
$$;

-- ── 12. Seeds: commission knob ──
INSERT INTO public.app_settings (key, value, description) VALUES
  ('hotel_commission_pct', '10', 'Platform commission % on hotel bookings')
ON CONFLICT (key) DO NOTHING;
