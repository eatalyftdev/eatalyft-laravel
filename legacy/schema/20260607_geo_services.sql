-- EataLyft Geo Services Migration
-- Tables: live_locations, rides, food_orders, parcels, payments, agent_zones

-- Enable PostGIS for geolocation
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS postgis_topology;

-- ─────────────────────────────────────────────────────────────────────────────
-- DRIVER / AGENT LIVE LOCATION TABLE
-- Single table for all moving actors
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.live_locations (
  id              uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id         uuid NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  role            text NOT NULL CHECK (role IN (
                    'driver', 'delivery_agent', 'dispatch', 'agent'
                  )),
  location        geography(POINT, 4326) NOT NULL,
  heading         numeric(5,2),
  speed_kmh       numeric(6,2),
  is_online       boolean DEFAULT true,
  is_available    boolean DEFAULT true,
  vehicle_type    text,
  last_seen_at    timestamptz DEFAULT NOW(),
  created_at      timestamptz DEFAULT NOW(),
  UNIQUE(user_id)
);

CREATE INDEX IF NOT EXISTS idx_live_locations_geography
  ON public.live_locations USING GIST(location);

CREATE INDEX IF NOT EXISTS idx_live_locations_available
  ON public.live_locations(is_online, is_available, role);

-- ─────────────────────────────────────────────────────────────────────────────
-- RIDES TABLE
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.rides (
  id                  uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  customer_id         uuid NOT NULL REFERENCES auth.users(id),
  driver_id           uuid REFERENCES auth.users(id),

  pickup_location     geography(POINT, 4326) NOT NULL,
  pickup_address      text NOT NULL,
  dropoff_location    geography(POINT, 4326) NOT NULL,
  dropoff_address     text NOT NULL,

  status              text NOT NULL DEFAULT 'searching'
                      CHECK (status IN (
                        'searching','accepted','en_route_pickup',
                        'arrived_pickup','in_progress','completed',
                        'cancelled'
                      )),
  vehicle_type        text DEFAULT 'car',

  distance_km         numeric(8,2),
  duration_minutes    integer,
  fare_xaf            numeric(12,2),

  requested_at        timestamptz DEFAULT NOW(),
  accepted_at         timestamptz,
  picked_up_at        timestamptz,
  completed_at        timestamptz,
  cancelled_at        timestamptz,
  cancel_reason       text,

  payment_status      text DEFAULT 'pending'
                      CHECK (payment_status IN (
                        'pending','paid','failed','refunded'
                      )),
  payment_ref         text,

  created_at          timestamptz DEFAULT NOW(),
  updated_at          timestamptz DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_rides_customer ON public.rides(customer_id, status);
CREATE INDEX IF NOT EXISTS idx_rides_driver   ON public.rides(driver_id, status);
CREATE INDEX IF NOT EXISTS idx_rides_status   ON public.rides(status, requested_at DESC);

-- ─────────────────────────────────────────────────────────────────────────────
-- FOOD ORDERS TABLE
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.food_orders (
  id                  uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  customer_id         uuid NOT NULL REFERENCES auth.users(id),
  vendor_id           uuid NOT NULL REFERENCES auth.users(id),
  delivery_agent_id   uuid REFERENCES auth.users(id),

  delivery_location   geography(POINT, 4326) NOT NULL,
  delivery_address    text NOT NULL,
  vendor_location     geography(POINT, 4326),

  status              text NOT NULL DEFAULT 'placed'
                      CHECK (status IN (
                        'placed','confirmed','preparing','ready_for_pickup',
                        'picked_up','en_route','delivered','cancelled'
                      )),

  items               jsonb NOT NULL DEFAULT '[]',
  subtotal_xaf        numeric(12,2) NOT NULL,
  delivery_fee_xaf    numeric(12,2) DEFAULT 0,
  total_xaf           numeric(12,2) NOT NULL,

  payment_status      text DEFAULT 'pending',
  payment_ref         text,

  special_instructions        text,
  estimated_delivery_minutes  integer,

  placed_at           timestamptz DEFAULT NOW(),
  confirmed_at        timestamptz,
  delivered_at        timestamptz,
  updated_at          timestamptz DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_food_orders_customer ON public.food_orders(customer_id, status);
CREATE INDEX IF NOT EXISTS idx_food_orders_agent    ON public.food_orders(delivery_agent_id, status);
CREATE INDEX IF NOT EXISTS idx_food_orders_vendor   ON public.food_orders(vendor_id, status);

-- ─────────────────────────────────────────────────────────────────────────────
-- PARCEL DELIVERIES TABLE
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.parcels (
  id                  uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  sender_id           uuid NOT NULL REFERENCES auth.users(id),
  recipient_name      text NOT NULL,
  recipient_phone     text NOT NULL,
  delivery_agent_id   uuid REFERENCES auth.users(id),
  agent_id            uuid REFERENCES auth.users(id),

  pickup_location     geography(POINT, 4326) NOT NULL,
  pickup_address      text NOT NULL,
  dropoff_location    geography(POINT, 4326) NOT NULL,
  dropoff_address     text NOT NULL,

  status              text NOT NULL DEFAULT 'pending'
                      CHECK (status IN (
                        'pending','assigned','picked_up',
                        'in_transit','delivered','failed','cancelled'
                      )),

  weight_kg           numeric(6,2),
  size                text CHECK (size IN ('small','medium','large','extra_large')),
  fragile             boolean DEFAULT false,
  description         text,

  delivery_fee_xaf    numeric(12,2),
  payment_status      text DEFAULT 'pending',
  payment_ref         text,

  pickup_code         text,
  delivery_code       text,

  created_at          timestamptz DEFAULT NOW(),
  assigned_at         timestamptz,
  delivered_at        timestamptz,
  updated_at          timestamptz DEFAULT NOW()
);

-- ─────────────────────────────────────────────────────────────────────────────
-- PAYMENT RECORDS (synced from Firebase)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.payments (
  id                  uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  firebase_ref        text UNIQUE NOT NULL,
  user_id             uuid REFERENCES auth.users(id),

  service_type        text NOT NULL CHECK (service_type IN (
                        'ride','food','parcel','subscription','topup'
                      )),
  service_id          uuid,

  amount_xaf          numeric(12,2) NOT NULL,
  currency            text DEFAULT 'XAF',
  provider            text,
  provider_ref        text,

  status              text NOT NULL DEFAULT 'pending'
                      CHECK (status IN (
                        'pending','processing','completed','failed','refunded'
                      )),

  metadata            jsonb DEFAULT '{}',

  initiated_at        timestamptz DEFAULT NOW(),
  completed_at        timestamptz,
  created_at          timestamptz DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_payments_user     ON public.payments(user_id, status);
CREATE INDEX IF NOT EXISTS idx_payments_firebase ON public.payments(firebase_ref);
CREATE INDEX IF NOT EXISTS idx_payments_service  ON public.payments(service_type, service_id);

-- ─────────────────────────────────────────────────────────────────────────────
-- AGENT ZONES TABLE
-- tracks which agent is assigned to which zone
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.agent_zones (
  id          uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  agent_id    uuid NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  zone_name   text NOT NULL,
  zone_area   geography(POLYGON, 4326),
  center      geography(POINT, 4326),
  radius_km   numeric(6,2) DEFAULT 5,
  is_active   boolean DEFAULT true,
  created_at  timestamptz DEFAULT NOW(),
  UNIQUE(agent_id)
);
