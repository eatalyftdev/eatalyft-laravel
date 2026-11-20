-- ============================================================
-- live_locations v2 — Firebase-UID-compatible + profile snapshot
-- ============================================================
-- Changes vs v1:
--   • user_id: uuid FK → text PRIMARY KEY  (supports Firebase UIDs)
--   • adds latitude / longitude numeric columns (Realtime-friendly, no PostGIS client needed)
--   • adds display_name, photo_url, vehicle_model (profile snapshot)
--   • role CHECK extended to include 'agent' alias
--   • clean RLS: anon can read, only service role can write
-- ============================================================

-- Drop v1 table (no production data yet)
DROP TABLE IF EXISTS live_locations CASCADE;

CREATE TABLE live_locations (
  user_id        text           PRIMARY KEY,
  role           text           NOT NULL
                   CHECK (role IN ('driver','delivery_agent','agent','handyman','nanny')),
  -- PostGIS geometry for spatial RPC functions (find_nearest_*)
  location       geometry(Point, 4326),
  -- Plain numeric lat/lng so Realtime subscribers can read without PostGIS
  latitude       numeric(10, 7) NOT NULL DEFAULT 0,
  longitude      numeric(10, 7) NOT NULL DEFAULT 0,
  heading        numeric(5,  2),
  speed_kmh      numeric(6,  2),
  is_online      boolean        NOT NULL DEFAULT true,
  is_available   boolean        NOT NULL DEFAULT true,
  -- Profile snapshot — cached from Firestore so the dispatcher needs 0 extra reads
  display_name   text,
  photo_url      text,
  vehicle_type   text,
  vehicle_model  text,
  last_seen_at   timestamptz    NOT NULL DEFAULT now(),
  updated_at     timestamptz    NOT NULL DEFAULT now()
);

-- Spatial index for find_nearest_* RPCs
CREATE INDEX live_locations_location_idx
  ON live_locations USING GIST (location);

-- Partial index for online fleet queries (dispatcher / heat map)
CREATE INDEX live_locations_online_role_idx
  ON live_locations (role, updated_at DESC)
  WHERE is_online = true;

-- ─── Row Level Security ───────────────────────────────────────────────────────

ALTER TABLE live_locations ENABLE ROW LEVEL SECURITY;

-- Anon / authenticated users can SELECT (dispatcher + customer map views)
CREATE POLICY "live_locations_read_public"
  ON live_locations FOR SELECT
  USING (true);

-- Only the service-role key (server-side Next.js) may write
-- The WITH CHECK (true) lets service role bypass even this policy if needed
CREATE POLICY "live_locations_write_service"
  ON live_locations FOR ALL
  USING (current_setting('role') = 'service_role')
  WITH CHECK (current_setting('role') = 'service_role');

-- ─── Realtime publication ─────────────────────────────────────────────────────

-- Remove from publication first (safe if it wasn't there)
DO $$
BEGIN
  ALTER PUBLICATION supabase_realtime DROP TABLE live_locations;
EXCEPTION WHEN undefined_object OR others THEN NULL;
END $$;

ALTER PUBLICATION supabase_realtime ADD TABLE live_locations;

-- ─── Update the update_my_location RPC for v2 schema ────────────────────────
-- (used by Supabase-authed web sessions; Next.js API uses service role directly)

CREATE OR REPLACE FUNCTION update_my_location(
  lat       numeric,
  lng       numeric,
  heading   numeric DEFAULT NULL,
  speed_kmh numeric DEFAULT NULL
)
RETURNS void
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  v_role text;
BEGIN
  -- Derive role from the calling user's metadata (set at Supabase signup)
  SELECT COALESCE(raw_user_meta_data->>'role', 'driver')
  INTO v_role
  FROM auth.users
  WHERE id::text = auth.uid()::text;

  INSERT INTO live_locations (
    user_id, role, location, latitude, longitude,
    heading, speed_kmh, is_online, is_available, last_seen_at, updated_at
  )
  VALUES (
    auth.uid()::text,
    COALESCE(v_role, 'driver'),
    ST_SetSRID(ST_MakePoint(lng, lat), 4326),
    lat, lng,
    update_my_location.heading,
    update_my_location.speed_kmh,
    true, true,
    now(), now()
  )
  ON CONFLICT (user_id) DO UPDATE SET
    location     = EXCLUDED.location,
    latitude     = EXCLUDED.latitude,
    longitude    = EXCLUDED.longitude,
    heading      = COALESCE(EXCLUDED.heading,    live_locations.heading),
    speed_kmh    = COALESCE(EXCLUDED.speed_kmh,  live_locations.speed_kmh),
    is_online    = true,
    last_seen_at = now(),
    updated_at   = now();
END;
$$;

-- ─── Update find_nearest_driver for v2 schema (uses latitude/longitude columns now) ─

CREATE OR REPLACE FUNCTION find_nearest_driver(
  customer_lat     numeric,
  customer_lng     numeric,
  vehicle_type     text    DEFAULT 'car',
  search_radius_km numeric DEFAULT 10
)
RETURNS TABLE (
  user_id          text,
  distance_meters  numeric,
  latitude         numeric,
  longitude        numeric,
  heading          numeric,
  speed_kmh        numeric,
  vehicle_type     text,
  display_name     text,
  photo_url        text,
  last_seen_at     timestamptz
)
LANGUAGE sql
STABLE
SECURITY DEFINER
SET search_path = public
AS $$
  SELECT
    ll.user_id,
    ROUND(ST_Distance(
      ll.location::geography,
      ST_SetSRID(ST_MakePoint(customer_lng, customer_lat), 4326)::geography
    )::numeric, 2)              AS distance_meters,
    ll.latitude,
    ll.longitude,
    ll.heading,
    ll.speed_kmh,
    ll.vehicle_type,
    ll.display_name,
    ll.photo_url,
    ll.last_seen_at
  FROM live_locations ll
  WHERE
    ll.role         = 'driver'
    AND ll.is_online    = true
    AND ll.is_available = true
    AND (find_nearest_driver.vehicle_type IS NULL
         OR ll.vehicle_type = find_nearest_driver.vehicle_type)
    AND ST_DWithin(
      ll.location::geography,
      ST_SetSRID(ST_MakePoint(customer_lng, customer_lat), 4326)::geography,
      search_radius_km * 1000
    )
    AND ll.updated_at > now() - interval '2 minutes'
  ORDER BY distance_meters ASC
  LIMIT 10;
$$;

-- ─── Update find_nearest_delivery_agent ──────────────────────────────────────

CREATE OR REPLACE FUNCTION find_nearest_delivery_agent(
  delivery_lat     numeric,
  delivery_lng     numeric,
  search_radius_km numeric DEFAULT 8
)
RETURNS TABLE (
  user_id         text,
  distance_meters numeric,
  latitude        numeric,
  longitude       numeric,
  display_name    text,
  photo_url       text,
  last_seen_at    timestamptz
)
LANGUAGE sql
STABLE
SECURITY DEFINER
SET search_path = public
AS $$
  SELECT
    ll.user_id,
    ROUND(ST_Distance(
      ll.location::geography,
      ST_SetSRID(ST_MakePoint(delivery_lng, delivery_lat), 4326)::geography
    )::numeric, 2) AS distance_meters,
    ll.latitude, ll.longitude,
    ll.display_name, ll.photo_url,
    ll.last_seen_at
  FROM live_locations ll
  WHERE
    ll.role IN ('delivery_agent', 'agent')
    AND ll.is_online    = true
    AND ll.is_available = true
    AND ST_DWithin(
      ll.location::geography,
      ST_SetSRID(ST_MakePoint(delivery_lng, delivery_lat), 4326)::geography,
      search_radius_km * 1000
    )
    AND ll.updated_at > now() - interval '2 minutes'
  ORDER BY distance_meters ASC
  LIMIT 5;
$$;

-- ─── New: find_all_online_fleet (dispatcher / heat map) ──────────────────────

CREATE OR REPLACE FUNCTION find_all_online_fleet(
  bounding_lat_min numeric DEFAULT -90,
  bounding_lat_max numeric DEFAULT  90,
  bounding_lng_min numeric DEFAULT -180,
  bounding_lng_max numeric DEFAULT  180
)
RETURNS TABLE (
  user_id       text,
  role          text,
  latitude      numeric,
  longitude     numeric,
  heading       numeric,
  speed_kmh     numeric,
  is_available  boolean,
  display_name  text,
  photo_url     text,
  vehicle_type  text,
  vehicle_model text,
  updated_at    timestamptz
)
LANGUAGE sql
STABLE
SECURITY DEFINER
SET search_path = public
AS $$
  SELECT
    ll.user_id, ll.role,
    ll.latitude, ll.longitude,
    ll.heading, ll.speed_kmh,
    ll.is_available,
    ll.display_name, ll.photo_url,
    ll.vehicle_type, ll.vehicle_model,
    ll.updated_at
  FROM live_locations ll
  WHERE
    ll.is_online = true
    AND ll.latitude  BETWEEN bounding_lat_min AND bounding_lat_max
    AND ll.longitude BETWEEN bounding_lng_min AND bounding_lng_max
    AND ll.updated_at > now() - interval '5 minutes'
  ORDER BY ll.updated_at DESC;
$$;
