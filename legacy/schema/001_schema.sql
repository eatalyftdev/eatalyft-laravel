-- ============================================================
-- EataLyft Horizon — Supabase Migration 001: Core Schema
-- Firebase → Supabase cutover
-- Run: npx supabase db push
-- ============================================================

-- ── Enums ──────────────────────────────────────────────────
CREATE TYPE user_role AS ENUM (
  'customer','driver','agent','handyman','nanny','cleaner',
  'hotel','vendor','agency','creator','dispatch','accountant',
  'admin','superadmin'
);
CREATE TYPE account_status AS ENUM ('active','pending','suspended','incomplete');
CREATE TYPE tx_type AS ENUM ('credit','debit');
CREATE TYPE service_type AS ENUM ('ride','food','parcel','handyman','nanny','bus','hotel');

-- ── Core profiles (replaces /users/{uid}) ──────────────────
CREATE TABLE public.profiles (
  id               UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
  email            TEXT NOT NULL,
  role             user_role NOT NULL DEFAULT 'customer',
  display_name     TEXT,
  first_name       TEXT,
  last_name        TEXT,
  phone            TEXT,
  photo_url        TEXT,
  status           account_status NOT NULL DEFAULT 'pending',
  created_by_admin BOOLEAN DEFAULT FALSE,
  firebase_uid     TEXT UNIQUE,        -- Firebase UID for migration mapping
  public_id        TEXT UNIQUE,        -- human-readable short ID
  created_at       TIMESTAMPTZ DEFAULT NOW(),
  updated_at       TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX idx_profiles_role   ON public.profiles(role);
CREATE INDEX idx_profiles_status ON public.profiles(status);
CREATE INDEX idx_profiles_email  ON public.profiles(email);

-- ── Role-specific extension tables ─────────────────────────
CREATE TABLE public.driver_profiles (
  id              UUID PRIMARY KEY REFERENCES public.profiles(id) ON DELETE CASCADE,
  vehicle_type    TEXT DEFAULT 'car',
  vehicle_model   TEXT,
  vehicle_plate   TEXT,
  license_number  TEXT,
  is_available    BOOLEAN DEFAULT TRUE,
  is_online       BOOLEAN DEFAULT FALSE,
  rating          NUMERIC(3,2) DEFAULT 5.0,
  total_trips     INT DEFAULT 0,
  is_verified     BOOLEAN DEFAULT FALSE,
  account_status  TEXT DEFAULT 'pending_approval'
);

CREATE TABLE public.agent_profiles (
  id               UUID PRIMARY KEY REFERENCES public.profiles(id) ON DELETE CASCADE,
  vehicle_type     TEXT DEFAULT 'bike',
  vehicle_model    TEXT,
  vehicle_plate    TEXT,
  region           TEXT,
  city             TEXT,
  is_available     BOOLEAN DEFAULT TRUE,
  is_online        BOOLEAN DEFAULT FALSE,
  rating           NUMERIC(3,2) DEFAULT 5.0,
  total_deliveries INT DEFAULT 0,
  is_verified      BOOLEAN DEFAULT FALSE,
  account_status   TEXT DEFAULT 'pending_approval'
);

CREATE TABLE public.handyman_profiles (
  id             UUID PRIMARY KEY REFERENCES public.profiles(id) ON DELETE CASCADE,
  specialty      TEXT,
  skills         TEXT[],
  rate_per_hour  NUMERIC(10,2) DEFAULT 0,
  address        TEXT,
  latitude       NUMERIC(10,6),
  longitude      NUMERIC(10,6),
  is_available   BOOLEAN DEFAULT TRUE,
  rating         NUMERIC(3,2) DEFAULT 0,
  total_jobs     INT DEFAULT 0,
  is_verified    BOOLEAN DEFAULT FALSE,
  account_status TEXT DEFAULT 'pending_approval'
);

CREATE TABLE public.nanny_profiles (
  id             UUID PRIMARY KEY REFERENCES public.profiles(id) ON DELETE CASCADE,
  service_type   TEXT DEFAULT 'childcare',   -- 'childcare' | 'cleaning'
  experience_yrs INT DEFAULT 0,
  languages      TEXT[],
  rate_per_hour  NUMERIC(10,2) DEFAULT 0,
  address        TEXT,
  latitude       NUMERIC(10,6),
  longitude      NUMERIC(10,6),
  is_available   BOOLEAN DEFAULT TRUE,
  rating         NUMERIC(3,2) DEFAULT 0,
  total_jobs     INT DEFAULT 0,
  is_verified    BOOLEAN DEFAULT FALSE,
  account_status TEXT DEFAULT 'pending_approval'
);

CREATE TABLE public.hotel_profiles (
  id            UUID PRIMARY KEY REFERENCES public.profiles(id) ON DELETE CASCADE,
  property_name TEXT,
  address       TEXT,
  city          TEXT,
  latitude      NUMERIC(10,6),
  longitude     NUMERIC(10,6),
  star_rating   INT CHECK (star_rating BETWEEN 1 AND 5),
  is_verified   BOOLEAN DEFAULT FALSE,
  status        TEXT DEFAULT 'active'
);

CREATE TABLE public.vendor_profiles (
  id          UUID PRIMARY KEY REFERENCES public.profiles(id) ON DELETE CASCADE,
  shop_name   TEXT,
  category    TEXT,
  address     TEXT,
  city        TEXT,
  latitude    NUMERIC(10,6),
  longitude   NUMERIC(10,6),
  is_open     BOOLEAN DEFAULT TRUE,
  is_verified BOOLEAN DEFAULT FALSE,
  rating      NUMERIC(3,2) DEFAULT 0
);

-- ── Wallets (replaces /wallets/{uid}) ──────────────────────
CREATE TABLE public.wallets (
  id         UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id    UUID UNIQUE NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
  balance    NUMERIC(12,2) NOT NULL DEFAULT 0.00,
  points     NUMERIC(12,2) NOT NULL DEFAULT 0.00,   -- loyalty stars
  currency   TEXT NOT NULL DEFAULT 'XAF',
  status     TEXT NOT NULL DEFAULT 'active',
  updated_at TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX idx_wallets_user ON public.wallets(user_id);

-- ── Wallet transactions ─────────────────────────────────────
CREATE TABLE public.wallet_transactions (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  wallet_id   UUID NOT NULL REFERENCES public.wallets(id) ON DELETE CASCADE,
  user_id     UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
  amount      NUMERIC(12,2) NOT NULL,
  type        tx_type NOT NULL,
  description TEXT,
  reference   TEXT,
  service     TEXT,
  created_at  TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX idx_wallet_tx_wallet ON public.wallet_transactions(wallet_id);
CREATE INDEX idx_wallet_tx_user   ON public.wallet_transactions(user_id);

-- ── App settings (replaces all /system/settings/* Firestore docs) ──
-- key is the Firestore path flattened to underscore notation
-- e.g. system/settings/maintenance/main → maintenance_main
CREATE TABLE public.app_settings (
  key         TEXT PRIMARY KEY,
  value       JSONB NOT NULL,
  description TEXT,
  updated_by  UUID REFERENCES public.profiles(id),
  updated_at  TIMESTAMPTZ DEFAULT NOW()
);

-- ── Role permissions (replaces custom Firebase custom claims) ──
CREATE TABLE public.role_permissions (
  id         UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  role       user_role NOT NULL,
  permission TEXT NOT NULL,
  UNIQUE(role, permission)
);

-- ── Auth sessions log (optional — mirrors auth_sessions collection) ──
CREATE TABLE public.auth_sessions (
  id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id      UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
  device_info  TEXT,
  ip_address   TEXT,
  created_at   TIMESTAMPTZ DEFAULT NOW(),
  expires_at   TIMESTAMPTZ,
  revoked_at   TIMESTAMPTZ
);

-- ── Audit logs (replaces /audit_logs collection) ────────────
CREATE TABLE public.audit_logs (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  action      TEXT NOT NULL,
  actor_id    UUID REFERENCES public.profiles(id),
  target_id   TEXT,
  target_type TEXT,
  details     JSONB,
  created_at  TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX idx_audit_actor  ON public.audit_logs(actor_id);
CREATE INDEX idx_audit_action ON public.audit_logs(action);

-- ────────────────────────────────────────────────────────────
-- SEED: Role permissions
-- ────────────────────────────────────────────────────────────
INSERT INTO public.role_permissions (role, permission) VALUES
  -- Admin
  ('admin','create_user'),('admin','delete_user'),('admin','suspend_user'),
  ('admin','view_all_profiles'),('admin','edit_settings'),('admin','view_wallets'),
  ('admin','manage_commissions'),('admin','view_audit_logs'),('admin','send_campaigns'),
  -- Superadmin (all admin + extras)
  ('superadmin','create_user'),('superadmin','delete_user'),('superadmin','suspend_user'),
  ('superadmin','view_all_profiles'),('superadmin','edit_settings'),('superadmin','view_wallets'),
  ('superadmin','manage_admins'),('superadmin','manage_commissions'),
  ('superadmin','view_audit_logs'),('superadmin','send_campaigns'),('superadmin','danger_zone'),
  -- Partner roles
  ('driver','view_own_profile'),('driver','update_own_profile'),('driver','view_own_wallet'),
  ('agent','view_own_profile'),('agent','update_own_profile'),('agent','view_own_wallet'),
  ('handyman','view_own_profile'),('handyman','update_own_profile'),('handyman','view_own_wallet'),
  ('nanny','view_own_profile'),('nanny','update_own_profile'),('nanny','view_own_wallet'),
  ('cleaner','view_own_profile'),('cleaner','update_own_profile'),('cleaner','view_own_wallet'),
  ('hotel','view_own_profile'),('hotel','update_own_profile'),('hotel','view_own_wallet'),
  ('vendor','view_own_profile'),('vendor','update_own_profile'),('vendor','view_own_wallet'),
  ('creator','view_own_profile'),('creator','update_own_profile'),('creator','view_own_wallet'),
  -- Customer
  ('customer','view_own_profile'),('customer','update_own_profile'),('customer','view_own_wallet'),
  ('customer','place_orders'),('customer','track_orders'),('customer','view_history');

-- ────────────────────────────────────────────────────────────
-- SEED: App settings
-- All config previously scattered across Firestore /system/settings/*
-- ────────────────────────────────────────────────────────────
INSERT INTO public.app_settings (key, value, description) VALUES

  -- ── Branding / Frontend ─────────────────────────────────
  ('frontend_app_name',       '"EataLyft"',          'App display name'),
  ('frontend_support_email',  '"support@eatalyft.cm"','Support contact email'),
  ('frontend_currency',       '"XAF"',               'Default currency code'),
  ('frontend_country_code',   '"+237"',              'Default country dial code'),
  ('frontend_app_version',    '"2.0.4"',             'Current app version string'),

  -- ── Maintenance ──────────────────────────────────────────
  ('maintenance_enabled', 'false',                                                  'Put platform in maintenance mode'),
  ('maintenance_message', '"Platform undergoing scheduled maintenance. Back shortly."','Maintenance banner message'),

  -- ── Launch countdown ─────────────────────────────────────
  ('launch_enabled',  'false',                     'Show launch countdown page'),
  ('launch_title',    '"EataLyft is Launching"',   'Countdown page headline'),
  ('launch_subtitle', '"Get ready for the future of mobility in Cameroon."','Countdown page sub-headline'),

  -- ── Tax ──────────────────────────────────────────────────
  ('tax_enabled', 'false', 'Apply VAT/tax to order totals'),
  ('tax_rate',    '19.25', 'Tax percentage (Cameroon VAT)'),

  -- ── Finance ──────────────────────────────────────────────
  ('finance_payout_fee_percent', '2.5',  'Fee charged on each payout request (%)'),
  ('finance_min_payout_amount',  '1000', 'Minimum payout request in XAF'),

  -- ── Wallet / Loyalty ─────────────────────────────────────
  ('wallet_min_topup',      '500',     'Minimum wallet top-up amount (XAF)'),
  ('loyalty_points_name',   '"Stars"', 'Display name for loyalty points'),
  ('loyalty_accrual_rate',  '1',       'Stars earned per 100 XAF spent'),
  ('loyalty_redemption_rate','100',    'Stars required to redeem 1 XAF'),
  ('loyalty_min_redemption','100',     'Minimum stars to trigger a redemption'),
  ('referral_bonus',        '250',     'Wallet credit for successful referral (XAF)'),

  -- ── Commission rates (replaces system/settings/commissions/rates) ──
  ('commission_ride',     '{"percentage":25,"fixedFee":0,"active":true}',  'Ride-hailing commission split'),
  ('commission_food',     '{"percentage":25,"fixedFee":0,"active":true}',  'Food delivery commission split'),
  ('commission_parcel',   '{"percentage":25,"fixedFee":0,"active":true}',  'Parcel delivery commission split'),
  ('commission_handyman', '{"percentage":5,"fixedFee":0,"active":true}',   'Handyman commission split'),
  ('commission_nanny',    '{"percentage":5,"fixedFee":0,"active":true}',   'Nanny/care commission split'),
  ('commission_bus',      '{"percentage":0,"fixedFee":0,"active":false}',  'Bus ticket commission split'),
  ('commission_hotel',    '{"percentage":0,"fixedFee":0,"active":false}',  'Hotel booking commission split'),

  -- ── Ride tiers (replaces system/settings/rides/config) ───
  ('rides_tiers', '[
    {"id":"lyftgo","name":"LyftGo","baseFare":1000,"baseKm":3,"perKmCost":200,"perMinCost":15,"minFare":600},
    {"id":"premium","name":"LyftGo Premium","baseFare":2500,"baseKm":2,"perKmCost":500,"perMinCost":50,"minFare":1500}
  ]', 'Ride fare tier configuration'),

  -- ── Parcel pricing (replaces system/settings/parcels/*) ──
  ('parcels_base_fare',     '500',  'Parcel base fare (XAF)'),
  ('parcels_per_km_rate',   '100',  'Parcel per-km rate (XAF)'),
  ('parcels_min_fare',      '500',  'Parcel minimum fare (XAF)'),
  ('parcels_max_weight_kg', '50',   'Max parcel weight (kg)'),

  -- ── Proximity / dispatch settings ────────────────────────
  ('proximity_search_radius_km',   '5',    'Driver/agent search radius (km)'),
  ('proximity_max_candidates',     '10',   'Max partner candidates to notify'),
  ('proximity_job_timeout_seconds','90',   'Seconds before a job offer expires'),
  ('proximity_auto_assign',        'false','Auto-assign nearest partner without offer'),

  -- ── Roles requiring admin approval after self-signup ─────
  ('roles_require_approval', '["driver","agent","handyman","nanny","cleaner","hotel"]',
    'Roles with status=pending after self-registration'),

  -- ── Feature flags (replaces system/settings/features collection) ──
  ('feature_food_delivery',    'true',  'Enable food ordering vertical'),
  ('feature_ride_hailing',     'true',  'Enable ride-hailing vertical'),
  ('feature_parcel_delivery',  'true',  'Enable parcel delivery vertical'),
  ('feature_bus_booking',      'true',  'Enable inter-city bus booking'),
  ('feature_hotel_booking',    'true',  'Enable hotel booking vertical'),
  ('feature_handyman',         'true',  'Enable FixHub handyman vertical'),
  ('feature_nanny',            'true',  'Enable LyftCare nanny vertical'),
  ('feature_cleaner',          'true',  'Enable cleaning service vertical'),
  ('feature_loyalty_program',  'true',  'Enable Stars loyalty program'),
  ('feature_referral_program', 'true',  'Enable referral bonus system'),
  ('feature_whatsapp_notify',  'true',  'Send WhatsApp order notifications'),
  ('feature_email_notify',     'true',  'Send transactional emails'),
  ('feature_push_notify',      'true',  'Send FCM push notifications'),

  -- ── Third-party / integration keys (public-safe metadata only) ──
  ('integrations_meta', '{
    "mailgun_domain":"mg.eatalyft.cm",
    "whatsapp_provider":"meta_cloud_api",
    "maps_provider":"google",
    "storage_provider":"aws_s3",
    "payment_gateway":"mesomb"
  }', 'Public integration metadata (no secrets)');

-- ────────────────────────────────────────────────────────────
-- Auto-updated timestamps trigger
-- ────────────────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION update_updated_at()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN NEW.updated_at = NOW(); RETURN NEW; END;
$$;

CREATE TRIGGER trg_profiles_ts
  BEFORE UPDATE ON public.profiles
  FOR EACH ROW EXECUTE FUNCTION update_updated_at();

CREATE TRIGGER trg_wallets_ts
  BEFORE UPDATE ON public.wallets
  FOR EACH ROW EXECUTE FUNCTION update_updated_at();

CREATE TRIGGER trg_settings_ts
  BEFORE UPDATE ON public.app_settings
  FOR EACH ROW EXECUTE FUNCTION update_updated_at();

-- ────────────────────────────────────────────────────────────
-- Auto-create profile + wallet on new auth.users row
-- (mirrors Firebase signup batch that wrote /users + /wallets)
-- ────────────────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION handle_new_user()
RETURNS TRIGGER LANGUAGE plpgsql SECURITY DEFINER AS $$
DECLARE
  v_role    user_role;
  v_status  account_status;
  v_fn      TEXT;
  v_ln      TEXT;
BEGIN
  v_role   := COALESCE((NEW.raw_user_meta_data->>'role')::user_role, 'customer');
  v_status := CASE
    WHEN (NEW.raw_user_meta_data->>'created_by_admin')::boolean = TRUE THEN 'active'::account_status
    WHEN v_role IN ('driver','agent','handyman','nanny','cleaner','hotel') THEN 'pending'::account_status
    ELSE 'active'::account_status
  END;
  v_fn := COALESCE(NEW.raw_user_meta_data->>'first_name', split_part(NEW.raw_user_meta_data->>'display_name',' ',1), '');
  v_ln := COALESCE(NEW.raw_user_meta_data->>'last_name',  '', '');

  INSERT INTO public.profiles(id, email, role, display_name, first_name, last_name, phone, photo_url, status, created_by_admin, firebase_uid)
  VALUES (
    NEW.id,
    NEW.email,
    v_role,
    COALESCE(NEW.raw_user_meta_data->>'display_name', NEW.email),
    v_fn, v_ln,
    NEW.raw_user_meta_data->>'phone',
    NEW.raw_user_meta_data->>'photo_url',
    v_status,
    COALESCE((NEW.raw_user_meta_data->>'created_by_admin')::boolean, FALSE),
    NEW.raw_user_meta_data->>'firebase_uid'
  ) ON CONFLICT (id) DO NOTHING;

  INSERT INTO public.wallets(user_id) VALUES (NEW.id) ON CONFLICT DO NOTHING;

  RETURN NEW;
END;
$$;

CREATE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW EXECUTE FUNCTION handle_new_user();
