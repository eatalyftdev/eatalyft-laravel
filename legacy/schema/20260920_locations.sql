-- ============================================================
-- EataLyft — Supabase Migration: Location Registry (countries/cities/
-- neighborhoods) for the location-scoped marketplace + Realtor app.
-- Admin-managed (service role writes); public read of enabled rows.
-- Run: npx supabase db push
-- ============================================================

CREATE TABLE IF NOT EXISTS public.countries (
  code          CHAR(2) PRIMARY KEY,
  name          TEXT NOT NULL,
  currency_code TEXT NOT NULL DEFAULT 'XAF',
  is_enabled    BOOLEAN NOT NULL DEFAULT TRUE,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.cities (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  country_code  CHAR(2) NOT NULL REFERENCES public.countries(code) ON DELETE CASCADE,
  name          TEXT NOT NULL,
  is_enabled    BOOLEAN NOT NULL DEFAULT TRUE,
  lat           DOUBLE PRECISION,
  lng           DOUBLE PRECISION,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  UNIQUE (country_code, name)
);
CREATE INDEX IF NOT EXISTS idx_cities_country ON public.cities(country_code);

CREATE TABLE IF NOT EXISTS public.neighborhoods (
  id         UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  city_id    UUID NOT NULL REFERENCES public.cities(id) ON DELETE CASCADE,
  name       TEXT NOT NULL,
  is_enabled BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  UNIQUE (city_id, name)
);
CREATE INDEX IF NOT EXISTS idx_neighborhoods_city ON public.neighborhoods(city_id);

-- RLS: public read (enabled only), writes via service role.
ALTER TABLE public.countries     ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.cities        ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.neighborhoods ENABLE ROW LEVEL SECURITY;

CREATE POLICY "locations: public read countries" ON public.countries
  FOR SELECT USING (is_enabled = TRUE);
CREATE POLICY "locations: public read cities" ON public.cities
  FOR SELECT USING (is_enabled = TRUE);
CREATE POLICY "locations: public read neighborhoods" ON public.neighborhoods
  FOR SELECT USING (is_enabled = TRUE);

REVOKE ALL ON public.countries     FROM anon, authenticated;
REVOKE ALL ON public.cities        FROM anon, authenticated;
REVOKE ALL ON public.neighborhoods FROM anon, authenticated;
GRANT SELECT ON public.countries     TO anon, authenticated;
GRANT SELECT ON public.cities        TO anon, authenticated;
GRANT SELECT ON public.neighborhoods TO anon, authenticated;

-- Seeds (CEMAC home market + neighbors; admin extends in console).
INSERT INTO public.countries (code, name, currency_code, is_enabled) VALUES
  ('CM','Cameroon','XAF',TRUE),('GA','Gabon','XAF',TRUE),('GQ','Equatorial Guinea','XAF',TRUE),
  ('TD','Chad','XAF',TRUE),('CF','Central African Republic','XAF',TRUE),('CG','Congo','XAF',TRUE),
  ('NG','Nigeria','NGN',TRUE),('SN','Senegal','XOF',TRUE),('CI','Côte d''Ivoire','XOF',TRUE),
  ('GH','Ghana','GHS',TRUE)
ON CONFLICT (code) DO NOTHING;

-- Cities (ids stable per (country, name); coordinates approximate centers).
INSERT INTO public.cities (country_code, name, is_enabled, lat, lng) VALUES
  ('CM','Douala',TRUE,4.0511,9.7679),('CM','Yaoundé',TRUE,3.8480,11.5021),
  ('CM','Bafoussam',TRUE,5.4778,10.4176),('CM','Bamenda',TRUE,5.9631,10.1591),
  ('CM','Garoua',TRUE,9.3014,13.3973),('CM','Kribi',TRUE,2.9407,9.9109),
  ('CM','Limbe',TRUE,4.0072,9.2125),
  ('GA','Libreville',TRUE,0.4162,9.4673),
  ('NG','Lagos',TRUE,6.5244,3.3792),('NG','Abuja',TRUE,9.0579,7.4951),
  ('SN','Dakar',TRUE,14.7167,-17.4677),
  ('CI','Abidjan',TRUE,5.3599,-4.0083),
  ('GH','Accra',TRUE,5.6037,-0.1870)
ON CONFLICT (country_code, name) DO NOTHING;

-- A few Douala/Yaoundé neighborhoods (admin extends).
INSERT INTO public.neighborhoods (city_id, name, is_enabled)
SELECT c.id, n.name, TRUE
FROM public.cities c
JOIN (VALUES
  ('CM','Douala','Akwa'),('CM','Douala','Bonanjo'),('CM','Douala','Deïdo'),
  ('CM','Douala','Bonapriso'),('CM','Douala','Logbessou'),
  ('CM','Yaoundé','Bastos'),('CM','Yaoundé','Mvan'),('CM','Yaoundé','Nkolbisson'),
  ('CM','Yaoundé','Mendong'),('CM','Yaoundé','Odza')
) AS n(country_code, city, name)
  ON c.country_code = n.country_code AND c.name = n.city
ON CONFLICT (city_id, name) DO NOTHING;
