-- EataLyft Geo Services — RLS Policies + Realtime Publication

-- ─────────────────────────────────────────────────────────────────────────────
-- live_locations
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE public.live_locations ENABLE ROW LEVEL SECURITY;

CREATE POLICY "actors can update own location"
  ON public.live_locations FOR ALL
  USING (user_id = auth.uid())
  WITH CHECK (user_id = auth.uid());

CREATE POLICY "customers can see nearby drivers"
  ON public.live_locations FOR SELECT
  USING (
    is_online = true
    AND is_available = true
    AND auth.uid() IS NOT NULL
  );

-- ─────────────────────────────────────────────────────────────────────────────
-- rides
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE public.rides ENABLE ROW LEVEL SECURITY;

CREATE POLICY "customers see own rides"
  ON public.rides FOR SELECT
  USING (customer_id = auth.uid());

CREATE POLICY "drivers see assigned rides"
  ON public.rides FOR SELECT
  USING (driver_id = auth.uid());

CREATE POLICY "customers create rides"
  ON public.rides FOR INSERT
  WITH CHECK (customer_id = auth.uid());

CREATE POLICY "driver updates own ride"
  ON public.rides FOR UPDATE
  USING (driver_id = auth.uid());

CREATE POLICY "admins see all rides"
  ON public.rides FOR ALL
  USING (
    auth.jwt()->'app_metadata'->>'role' IN ('superadmin','admin','dispatch')
  );

-- ─────────────────────────────────────────────────────────────────────────────
-- food_orders
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE public.food_orders ENABLE ROW LEVEL SECURITY;

CREATE POLICY "customers see own food orders"
  ON public.food_orders FOR SELECT
  USING (customer_id = auth.uid());

CREATE POLICY "vendors see their orders"
  ON public.food_orders FOR SELECT
  USING (vendor_id = auth.uid());

CREATE POLICY "agents see assigned food orders"
  ON public.food_orders FOR SELECT
  USING (delivery_agent_id = auth.uid());

CREATE POLICY "customers create food orders"
  ON public.food_orders FOR INSERT
  WITH CHECK (customer_id = auth.uid());

CREATE POLICY "admins manage food orders"
  ON public.food_orders FOR ALL
  USING (
    auth.jwt()->'app_metadata'->>'role' IN ('superadmin','admin','dispatch')
  );

-- ─────────────────────────────────────────────────────────────────────────────
-- parcels
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE public.parcels ENABLE ROW LEVEL SECURITY;

CREATE POLICY "senders see own parcels"
  ON public.parcels FOR SELECT
  USING (sender_id = auth.uid());

CREATE POLICY "agents see assigned parcels"
  ON public.parcels FOR SELECT
  USING (
    delivery_agent_id = auth.uid()
    OR agent_id = auth.uid()
  );

CREATE POLICY "senders create parcels"
  ON public.parcels FOR INSERT
  WITH CHECK (sender_id = auth.uid());

CREATE POLICY "admins manage parcels"
  ON public.parcels FOR ALL
  USING (
    auth.jwt()->'app_metadata'->>'role' IN ('superadmin','admin','dispatch')
  );

-- ─────────────────────────────────────────────────────────────────────────────
-- payments (read-only from client — writes come from Firebase sync)
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE public.payments ENABLE ROW LEVEL SECURITY;

CREATE POLICY "users see own payments"
  ON public.payments FOR SELECT
  USING (user_id = auth.uid());

CREATE POLICY "admins see all payments"
  ON public.payments FOR ALL
  USING (
    auth.jwt()->'app_metadata'->>'role' IN ('superadmin','admin','accountant')
  );

-- ─────────────────────────────────────────────────────────────────────────────
-- agent_zones
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE public.agent_zones ENABLE ROW LEVEL SECURITY;

CREATE POLICY "agents see own zone"
  ON public.agent_zones FOR SELECT
  USING (agent_id = auth.uid());

CREATE POLICY "admins manage zones"
  ON public.agent_zones FOR ALL
  USING (
    auth.jwt()->'app_metadata'->>'role' IN ('superadmin','admin','dispatch')
  );

-- ─────────────────────────────────────────────────────────────────────────────
-- REALTIME PUBLICATION
-- Enable Supabase Realtime for all geo-service tables
-- ─────────────────────────────────────────────────────────────────────────────
ALTER PUBLICATION supabase_realtime
  ADD TABLE public.live_locations,
            public.rides,
            public.food_orders,
            public.parcels,
            public.payments;
