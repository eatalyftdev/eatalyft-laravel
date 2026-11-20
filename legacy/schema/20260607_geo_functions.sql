-- EataLyft Geo Services — PostGIS Database Functions
-- Nearest-actor queries, location updates, and atomic assignments

-- ─────────────────────────────────────────────────────────────────────────────
-- FIND NEAREST AVAILABLE DRIVER (for rides)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION public.find_nearest_driver(
  customer_lat     double precision,
  customer_lng     double precision,
  vehicle_type     text DEFAULT 'car',
  search_radius_km numeric DEFAULT 10
)
RETURNS TABLE (
  user_id         uuid,
  distance_meters double precision,
  latitude        double precision,
  longitude       double precision,
  heading         numeric,
  speed_kmh       numeric,
  vehicle_type    text,
  last_seen_at    timestamptz
)
LANGUAGE sql
STABLE
SECURITY DEFINER
SET search_path = public, extensions
AS $$
  SELECT
    ll.user_id,
    ST_Distance(
      ll.location,
      ST_SetSRID(ST_MakePoint(customer_lng, customer_lat), 4326)::geography
    )                                           AS distance_meters,
    ST_Y(ll.location::geometry)                 AS latitude,
    ST_X(ll.location::geometry)                 AS longitude,
    ll.heading,
    ll.speed_kmh,
    ll.vehicle_type,
    ll.last_seen_at
  FROM public.live_locations ll
  WHERE ll.role = 'driver'
    AND ll.is_online = true
    AND ll.is_available = true
    AND (find_nearest_driver.vehicle_type IS NULL
         OR ll.vehicle_type = find_nearest_driver.vehicle_type)
    AND ST_DWithin(
      ll.location,
      ST_SetSRID(ST_MakePoint(customer_lng, customer_lat), 4326)::geography,
      search_radius_km * 1000
    )
    AND ll.last_seen_at > NOW() - INTERVAL '3 minutes'
  ORDER BY distance_meters ASC
  LIMIT 10;
$$;

GRANT EXECUTE ON FUNCTION public.find_nearest_driver TO authenticated, anon;


-- ─────────────────────────────────────────────────────────────────────────────
-- FIND NEAREST DELIVERY AGENT (food / parcel)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION public.find_nearest_delivery_agent(
  delivery_lat     double precision,
  delivery_lng     double precision,
  search_radius_km numeric DEFAULT 8
)
RETURNS TABLE (
  user_id         uuid,
  distance_meters double precision,
  latitude        double precision,
  longitude       double precision,
  last_seen_at    timestamptz
)
LANGUAGE sql
STABLE
SECURITY DEFINER
SET search_path = public, extensions
AS $$
  SELECT
    ll.user_id,
    ST_Distance(
      ll.location,
      ST_SetSRID(ST_MakePoint(delivery_lng, delivery_lat), 4326)::geography
    ) AS distance_meters,
    ST_Y(ll.location::geometry) AS latitude,
    ST_X(ll.location::geometry) AS longitude,
    ll.last_seen_at
  FROM public.live_locations ll
  WHERE ll.role = 'delivery_agent'
    AND ll.is_online = true
    AND ll.is_available = true
    AND ST_DWithin(
      ll.location,
      ST_SetSRID(ST_MakePoint(delivery_lng, delivery_lat), 4326)::geography,
      search_radius_km * 1000
    )
    AND ll.last_seen_at > NOW() - INTERVAL '3 minutes'
  ORDER BY distance_meters ASC
  LIMIT 5;
$$;

GRANT EXECUTE ON FUNCTION public.find_nearest_delivery_agent TO authenticated;


-- ─────────────────────────────────────────────────────────────────────────────
-- FIND NEAREST AGENT (for parcel zone assignment)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION public.find_nearest_agent(
  pickup_lat       double precision,
  pickup_lng       double precision,
  search_radius_km numeric DEFAULT 15
)
RETURNS TABLE (
  user_id         uuid,
  distance_meters double precision,
  latitude        double precision,
  longitude       double precision
)
LANGUAGE sql
STABLE
SECURITY DEFINER
SET search_path = public, extensions
AS $$
  SELECT
    ll.user_id,
    ST_Distance(
      ll.location,
      ST_SetSRID(ST_MakePoint(pickup_lng, pickup_lat), 4326)::geography
    ) AS distance_meters,
    ST_Y(ll.location::geometry) AS latitude,
    ST_X(ll.location::geometry) AS longitude
  FROM public.live_locations ll
  WHERE ll.role = 'agent'
    AND ll.is_online = true
    AND ll.is_available = true
    AND ST_DWithin(
      ll.location,
      ST_SetSRID(ST_MakePoint(pickup_lng, pickup_lat), 4326)::geography,
      search_radius_km * 1000
    )
    AND ll.last_seen_at > NOW() - INTERVAL '5 minutes'
  ORDER BY distance_meters ASC
  LIMIT 5;
$$;

GRANT EXECUTE ON FUNCTION public.find_nearest_agent TO authenticated;


-- ─────────────────────────────────────────────────────────────────────────────
-- UPDATE MY LOCATION (called from mobile app every 5–10 s)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION public.update_my_location(
  lat       double precision,
  lng       double precision,
  heading   numeric DEFAULT NULL,
  speed_kmh numeric DEFAULT NULL
)
RETURNS void
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, extensions
AS $$
BEGIN
  INSERT INTO public.live_locations (
    user_id, role, location, heading, speed_kmh, last_seen_at
  )
  SELECT
    auth.uid(),
    COALESCE(
      (SELECT raw_app_meta_data->>'role'
         FROM auth.users WHERE id = auth.uid()),
      'driver'
    ),
    ST_SetSRID(ST_MakePoint(lng, lat), 4326)::geography,
    update_my_location.heading,
    update_my_location.speed_kmh,
    NOW()
  ON CONFLICT (user_id) DO UPDATE SET
    location     = ST_SetSRID(ST_MakePoint(lng, lat), 4326)::geography,
    heading      = update_my_location.heading,
    speed_kmh    = update_my_location.speed_kmh,
    last_seen_at = NOW();
END;
$$;

GRANT EXECUTE ON FUNCTION public.update_my_location TO authenticated;


-- ─────────────────────────────────────────────────────────────────────────────
-- ASSIGN RIDE TO DRIVER (atomic — prevents double-assignment)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION public.assign_ride_to_driver(
  ride_id   uuid,
  driver_id uuid
)
RETURNS boolean
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  updated_count integer;
BEGIN
  UPDATE public.rides r
  SET
    driver_id   = assign_ride_to_driver.driver_id,
    status      = 'accepted',
    accepted_at = NOW(),
    updated_at  = NOW()
  WHERE r.id = ride_id
    AND r.status = 'searching'
    AND r.driver_id IS NULL;

  GET DIAGNOSTICS updated_count = ROW_COUNT;

  IF updated_count = 0 THEN
    RETURN false;
  END IF;

  UPDATE public.live_locations
  SET is_available = false
  WHERE user_id = assign_ride_to_driver.driver_id;

  RETURN true;
END;
$$;

GRANT EXECUTE ON FUNCTION public.assign_ride_to_driver TO authenticated;


-- ─────────────────────────────────────────────────────────────────────────────
-- ASSIGN FOOD ORDER TO DELIVERY AGENT (atomic)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION public.assign_food_order(
  order_id          uuid,
  delivery_agent_id uuid
)
RETURNS boolean
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  updated_count integer;
BEGIN
  UPDATE public.food_orders
  SET
    delivery_agent_id = assign_food_order.delivery_agent_id,
    status            = 'picked_up',
    updated_at        = NOW()
  WHERE id = order_id
    AND status = 'ready_for_pickup'
    AND delivery_agent_id IS NULL;

  GET DIAGNOSTICS updated_count = ROW_COUNT;

  IF updated_count = 0 THEN
    RETURN false;
  END IF;

  UPDATE public.live_locations
  SET is_available = false
  WHERE user_id = assign_food_order.delivery_agent_id;

  RETURN true;
END;
$$;

GRANT EXECUTE ON FUNCTION public.assign_food_order TO authenticated;
