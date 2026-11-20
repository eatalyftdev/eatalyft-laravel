-- ============================================================
-- EataLyft — Supabase Migration: Listings marketplace (Phase 4)
-- Location-scoped (country/city/neighborhood + PostGIS geo), moderated,
-- KYC-gated publishing (enforced in API, not here). Photos live in the
-- listing-photos bucket (public read, service-role writes).
-- Run: npx supabase db push
-- ============================================================

CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS pg_trgm;

-- ── 1. listings ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.listings (
  id                 UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  owner_uid          TEXT NOT NULL,                       -- realtor Firebase UID
  kind               TEXT NOT NULL CHECK (kind IN ('rent','sale','wanted','hotel','other')),
  category           TEXT NOT NULL DEFAULT 'general',
  status             TEXT NOT NULL DEFAULT 'draft'
                     CHECK (status IN ('draft','pending_review','live','paused','rejected','sold','rented','expired','flagged')),
  title              TEXT NOT NULL,
  description        TEXT NOT NULL DEFAULT '',
  price_minor        BIGINT NOT NULL CHECK (price_minor >= 0),
  currency           TEXT NOT NULL DEFAULT 'XAF',
  country_code       CHAR(2) NOT NULL,
  city_id            UUID REFERENCES public.cities(id) ON DELETE SET NULL,
  city_name          TEXT NOT NULL DEFAULT '',
  neighborhood       TEXT NOT NULL DEFAULT '',
  lat                DOUBLE PRECISION,
  lng                DOUBLE PRECISION,
  geog               GEOGRAPHY(Point, 4326),
  amenities          JSONB NOT NULL DEFAULT '[]',
  photos             JSONB NOT NULL DEFAULT '[]',        -- [{url, order}]
  documents          JSONB NOT NULL DEFAULT '[]',        -- [{url, label}] (title deeds…)
  availability       JSONB NOT NULL DEFAULT '{}',
  views              INTEGER NOT NULL DEFAULT 0,
  saves              INTEGER NOT NULL DEFAULT 0,
  inquiries          INTEGER NOT NULL DEFAULT 0,
  rejection_reason   TEXT,
  featured_until     TIMESTAMPTZ,
  created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_listings_geo       ON public.listings USING GIST (geog);
CREATE INDEX IF NOT EXISTS idx_listings_scope     ON public.listings (country_code, city_id, status, kind);
CREATE INDEX IF NOT EXISTS idx_listings_owner     ON public.listings (owner_uid, status);
CREATE INDEX IF NOT EXISTS idx_listings_title_trgm ON public.listings USING GIN (title gin_trgm_ops);

-- ── 2. inquiries (contact revealed to participants only) ────────
CREATE TABLE IF NOT EXISTS public.listing_inquiries (
  id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  listing_id   UUID NOT NULL REFERENCES public.listings(id) ON DELETE CASCADE,
  user_uid     TEXT NOT NULL,
  message      TEXT NOT NULL,
  attachments  JSONB NOT NULL DEFAULT '[]',
  status       TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open','answered','closed')),
  created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_inquiries_listing ON public.listing_inquiries (listing_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_inquiries_user    ON public.listing_inquiries (user_uid, created_at DESC);

-- ── 3. reports / abuse queue ────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.listing_reports (
  id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  listing_id   UUID NOT NULL REFERENCES public.listings(id) ON DELETE CASCADE,
  reporter_uid TEXT NOT NULL,
  reason       TEXT NOT NULL,
  detail       TEXT NOT NULL DEFAULT '',
  status       TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open','reviewing','resolved','dismissed')),
  created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_reports_status ON public.listing_reports (status, created_at DESC);

-- ── 4. viewings ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.listing_viewings (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  listing_id    UUID NOT NULL REFERENCES public.listings(id) ON DELETE CASCADE,
  user_uid      TEXT NOT NULL,
  scheduled_at  TIMESTAMPTZ NOT NULL,
  status        TEXT NOT NULL DEFAULT 'scheduled' CHECK (status IN ('scheduled','done','cancelled')),
  reminder_sent BOOLEAN NOT NULL DEFAULT FALSE,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_viewings_listing ON public.listing_viewings (listing_id, scheduled_at);

-- ── RLS: public reads live rows; everything else service-role (API) ──
ALTER TABLE public.listings            ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.listing_inquiries   ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.listing_reports     ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.listing_viewings    ENABLE ROW LEVEL SECURITY;

CREATE POLICY "listings: public live read" ON public.listings
  FOR SELECT USING (status = 'live');

REVOKE ALL ON public.listings            FROM anon, authenticated;
REVOKE ALL ON public.listing_inquiries   FROM anon, authenticated;
REVOKE ALL ON public.listing_reports     FROM anon, authenticated;
REVOKE ALL ON public.listing_viewings    FROM anon, authenticated;
GRANT SELECT ON public.listings TO anon, authenticated;

-- ── 5. listing-photos bucket (public read, service-role writes) ──
INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES ('listing-photos','listing-photos',true,5242880,ARRAY['image/jpeg','image/png','image/webp'])
ON CONFLICT (id) DO UPDATE
  SET public = EXCLUDED.public,
      file_size_limit = EXCLUDED.file_size_limit,
      allowed_mime_types = EXCLUDED.allowed_mime_types;

DROP POLICY IF EXISTS eal_listing_photos_select ON storage.objects;
CREATE POLICY eal_listing_photos_select ON storage.objects
  FOR SELECT USING (bucket_id = 'listing-photos');

-- ── 6. Seeds: realtor listing caps per tier (app_settings JSON) ──
INSERT INTO public.app_settings (key, value, description) VALUES
  ('realtor_live_limits', '{"1": 5, "2": 50}', 'Max LIVE listings per KYC tier')
ON CONFLICT (key) DO NOTHING;
