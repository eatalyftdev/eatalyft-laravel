-- ============================================================
-- EataLyft — Supabase Migration: Listings Phase 4
-- reviews (post-interaction only, moderated), inquiry replies, saves,
-- saved searches, contact-event log, listing rating aggregates.
-- RLS: public reads live/approved rows; all writes service-role (API).
-- Reversible — see 20260922_listings_phase4_reverse.sql.
-- Run: npx supabase db push
-- ============================================================

-- ── 1. reviews (approved-only public read; eligibility enforced in API) ──
CREATE TABLE IF NOT EXISTS public.listing_reviews (
  id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  listing_id   UUID NOT NULL REFERENCES public.listings(id) ON DELETE CASCADE,
  user_uid     TEXT NOT NULL,
  stars        SMALLINT NOT NULL CHECK (stars BETWEEN 1 AND 5),
  comment      TEXT NOT NULL DEFAULT '' CHECK (char_length(comment) <= 1000),
  viewing_id   UUID REFERENCES public.listing_viewings(id) ON DELETE SET NULL,
  status       TEXT NOT NULL DEFAULT 'pending'
               CHECK (status IN ('pending','approved','rejected')),
  created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  UNIQUE (listing_id, user_uid)
);
CREATE INDEX IF NOT EXISTS idx_reviews_listing ON public.listing_reviews (listing_id, status, created_at DESC);

-- ── 2. inquiry replies (realtor ↔ inquirer thread) ───────────────────────
CREATE TABLE IF NOT EXISTS public.listing_inquiry_replies (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  listing_id  UUID NOT NULL REFERENCES public.listings(id) ON DELETE CASCADE,
  inquiry_id  UUID NOT NULL REFERENCES public.listing_inquiries(id) ON DELETE CASCADE,
  author_uid  TEXT NOT NULL,
  message     TEXT NOT NULL CHECK (char_length(message) BETWEEN 1 AND 2000),
  created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_replies_inquiry ON public.listing_inquiry_replies (inquiry_id, created_at);

-- ── 3. saves (follow a listing → price-drop + live pings) ───────────────
CREATE TABLE IF NOT EXISTS public.listing_saves (
  user_uid   TEXT NOT NULL,
  listing_id UUID NOT NULL REFERENCES public.listings(id) ON DELETE CASCADE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  PRIMARY KEY (user_uid, listing_id)
);
CREATE INDEX IF NOT EXISTS idx_saves_listing ON public.listing_saves (listing_id);

-- ── 4. saved searches (city/country/kind match on approve → listing.live) ─
CREATE TABLE IF NOT EXISTS public.saved_searches (
  id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_uid         TEXT NOT NULL,
  country_code     TEXT NOT NULL,
  city_name        TEXT NOT NULL DEFAULT '',
  kind             TEXT NOT NULL DEFAULT '',
  max_price_minor  BIGINT,
  created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_searches_scope ON public.saved_searches (country_code, city_name);

-- ── 5. contact-event log (realtor analytics; ip hashed) ──────────────────
CREATE TABLE IF NOT EXISTS public.listing_contact_events (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  listing_id  UUID NOT NULL REFERENCES public.listings(id) ON DELETE CASCADE,
  user_uid    TEXT NOT NULL,
  ip_hash     TEXT NOT NULL DEFAULT '',
  channel     TEXT NOT NULL CHECK (channel IN ('whatsapp','call','reveal')),
  created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_contact_listing ON public.listing_contact_events (listing_id, created_at DESC);

-- ── 6. rating aggregates on listings (updated on review approve) ─────────
ALTER TABLE public.listings ADD COLUMN IF NOT EXISTS rating_avg   NUMERIC(3,2) NOT NULL DEFAULT 0;
ALTER TABLE public.listings ADD COLUMN IF NOT EXISTS rating_count INTEGER NOT NULL DEFAULT 0;

-- ── 7. review reports reuse the abuse queue ──────────────────────────────
ALTER TABLE public.listing_reports ADD COLUMN IF NOT EXISTS review_id UUID REFERENCES public.listing_reviews(id) ON DELETE CASCADE;

-- ── RLS ──────────────────────────────────────────────────────────────────
ALTER TABLE public.listing_reviews         ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.listing_inquiry_replies ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.listing_saves           ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.saved_searches          ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.listing_contact_events  ENABLE ROW LEVEL SECURITY;

CREATE POLICY "reviews: public approved read" ON public.listing_reviews
  FOR SELECT USING (status = 'approved');

REVOKE ALL ON public.listing_reviews         FROM anon, authenticated;
REVOKE ALL ON public.listing_inquiry_replies FROM anon, authenticated;
REVOKE ALL ON public.listing_saves           FROM anon, authenticated;
REVOKE ALL ON public.saved_searches          FROM anon, authenticated;
REVOKE ALL ON public.listing_contact_events  FROM anon, authenticated;
GRANT SELECT ON public.listing_reviews TO anon, authenticated;

-- ── Seeds: photo cap + contact country kill-switch ───────────────────────
INSERT INTO public.app_settings (key, value, description) VALUES
  ('listing_photo_cap', '20', 'Max photos per listing (wizard + API enforced)'),
  ('contact_countries_disabled', '[]', 'Country codes where direct realtor contact is disabled')
ON CONFLICT (key) DO NOTHING;
