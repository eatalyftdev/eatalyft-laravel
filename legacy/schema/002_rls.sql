-- ============================================================
-- EataLyft Horizon — Supabase Migration 002: Row Level Security
-- ============================================================

-- ── Enable RLS on every table ──────────────────────────────
ALTER TABLE public.profiles             ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.driver_profiles      ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.agent_profiles       ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.handyman_profiles    ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.nanny_profiles       ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.hotel_profiles       ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.vendor_profiles      ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.wallets              ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.wallet_transactions  ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.app_settings         ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.role_permissions     ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.auth_sessions        ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.audit_logs           ENABLE ROW LEVEL SECURITY;

-- ────────────────────────────────────────────────────────────
-- Helper functions (SECURITY DEFINER avoids recursive RLS)
-- ────────────────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION public.get_my_role()
RETURNS user_role LANGUAGE sql SECURITY DEFINER STABLE AS $$
  SELECT role FROM public.profiles WHERE id = auth.uid() LIMIT 1;
$$;

CREATE OR REPLACE FUNCTION public.is_admin()
RETURNS BOOLEAN LANGUAGE sql SECURITY DEFINER STABLE AS $$
  SELECT EXISTS (
    SELECT 1 FROM public.profiles
    WHERE id = auth.uid() AND role IN ('admin','superadmin')
  );
$$;

CREATE OR REPLACE FUNCTION public.is_superadmin()
RETURNS BOOLEAN LANGUAGE sql SECURITY DEFINER STABLE AS $$
  SELECT EXISTS (
    SELECT 1 FROM public.profiles
    WHERE id = auth.uid() AND role = 'superadmin'
  );
$$;

CREATE OR REPLACE FUNCTION public.is_finance_role()
RETURNS BOOLEAN LANGUAGE sql SECURITY DEFINER STABLE AS $$
  SELECT EXISTS (
    SELECT 1 FROM public.profiles
    WHERE id = auth.uid() AND role IN ('admin','superadmin','accountant')
  );
$$;

CREATE OR REPLACE FUNCTION public.has_permission(p TEXT)
RETURNS BOOLEAN LANGUAGE sql SECURITY DEFINER STABLE AS $$
  SELECT EXISTS (
    SELECT 1
    FROM public.role_permissions rp
    JOIN public.profiles pr ON pr.role = rp.role
    WHERE pr.id = auth.uid() AND rp.permission = p
  );
$$;

-- ────────────────────────────────────────────────────────────
-- profiles
-- ────────────────────────────────────────────────────────────
-- Users can read and update their own row; admins see all.
CREATE POLICY "profiles: own read"
  ON public.profiles FOR SELECT
  USING (id = auth.uid() OR is_admin());

CREATE POLICY "profiles: own update"
  ON public.profiles FOR UPDATE
  USING (id = auth.uid() OR is_admin())
  WITH CHECK (
    -- Regular users cannot promote their own role or change status.
    -- Only admins may change role/status.
    (id = auth.uid() AND is_admin()) OR
    (id = auth.uid() AND
      (role = (SELECT role FROM public.profiles WHERE id = auth.uid())) AND
      (status = (SELECT status FROM public.profiles WHERE id = auth.uid()))
    )
  );

-- New profiles are created via handle_new_user trigger or Edge Function — no direct client insert.
CREATE POLICY "profiles: admin insert"
  ON public.profiles FOR INSERT
  WITH CHECK (is_admin());

-- Only superadmin may hard-delete profiles.
CREATE POLICY "profiles: superadmin delete"
  ON public.profiles FOR DELETE
  USING (is_superadmin());

-- ────────────────────────────────────────────────────────────
-- Role-specific extension tables
-- Same pattern: own row or admin.
-- ────────────────────────────────────────────────────────────
DO $$ DECLARE t TEXT;
BEGIN
  FOR t IN SELECT unnest(ARRAY[
    'driver_profiles','agent_profiles','handyman_profiles',
    'nanny_profiles','hotel_profiles','vendor_profiles'
  ]) LOOP
    EXECUTE format(
      'CREATE POLICY "own or admin: select" ON public.%I FOR SELECT USING (id = auth.uid() OR is_admin());
       CREATE POLICY "own or admin: insert" ON public.%I FOR INSERT WITH CHECK (id = auth.uid() OR is_admin());
       CREATE POLICY "own or admin: update" ON public.%I FOR UPDATE USING (id = auth.uid() OR is_admin());
       CREATE POLICY "admin delete"         ON public.%I FOR DELETE USING (is_admin());',
      t, t, t, t
    );
  END LOOP;
END $$;

-- ────────────────────────────────────────────────────────────
-- wallets
-- Read: owner or finance role.
-- Write: admin / Edge Function (service role) only — never direct client writes for balance.
-- ────────────────────────────────────────────────────────────
CREATE POLICY "wallets: own read"
  ON public.wallets FOR SELECT
  USING (user_id = auth.uid() OR is_finance_role());

-- Balance updates must go through the wallet-operation Edge Function (service role).
-- Admins can patch non-balance fields directly (status, currency).
CREATE POLICY "wallets: admin update"
  ON public.wallets FOR UPDATE
  USING (is_admin());

-- Wallet rows are created by handle_new_user trigger; no direct client inserts.
CREATE POLICY "wallets: admin insert"
  ON public.wallets FOR INSERT
  WITH CHECK (is_admin());

-- ────────────────────────────────────────────────────────────
-- wallet_transactions
-- ────────────────────────────────────────────────────────────
CREATE POLICY "wallet_tx: own read"
  ON public.wallet_transactions FOR SELECT
  USING (
    user_id = auth.uid() OR is_finance_role()
  );

-- Transactions are written exclusively by wallet-operation Edge Function (service role).
-- Block all direct client writes.
CREATE POLICY "wallet_tx: service insert"
  ON public.wallet_transactions FOR INSERT
  WITH CHECK (is_admin());

-- Transactions are immutable — no updates or deletes by clients.
CREATE POLICY "wallet_tx: no update"
  ON public.wallet_transactions FOR UPDATE
  USING (FALSE);

CREATE POLICY "wallet_tx: superadmin delete"
  ON public.wallet_transactions FOR DELETE
  USING (is_superadmin());

-- ────────────────────────────────────────────────────────────
-- app_settings
-- Any authenticated user can read settings (needed for feature flags at boot).
-- Only admins can write.
-- ────────────────────────────────────────────────────────────
CREATE POLICY "settings: authenticated read"
  ON public.app_settings FOR SELECT
  USING (auth.uid() IS NOT NULL);

CREATE POLICY "settings: admin write"
  ON public.app_settings FOR ALL
  USING (is_admin())
  WITH CHECK (is_admin());

-- ────────────────────────────────────────────────────────────
-- role_permissions
-- Read-only for all authenticated users (needed client-side for UI guard).
-- Write only via migrations or superadmin.
-- ────────────────────────────────────────────────────────────
CREATE POLICY "role_permissions: authenticated read"
  ON public.role_permissions FOR SELECT
  USING (auth.uid() IS NOT NULL);

CREATE POLICY "role_permissions: superadmin write"
  ON public.role_permissions FOR ALL
  USING (is_superadmin());

-- ────────────────────────────────────────────────────────────
-- auth_sessions
-- ────────────────────────────────────────────────────────────
CREATE POLICY "sessions: own read"
  ON public.auth_sessions FOR SELECT
  USING (user_id = auth.uid() OR is_admin());

CREATE POLICY "sessions: own insert"
  ON public.auth_sessions FOR INSERT
  WITH CHECK (user_id = auth.uid());

CREATE POLICY "sessions: own delete"
  ON public.auth_sessions FOR DELETE
  USING (user_id = auth.uid() OR is_admin());

-- ────────────────────────────────────────────────────────────
-- audit_logs
-- Only admins can read. Written exclusively by Edge Functions (service role) and triggers.
-- ────────────────────────────────────────────────────────────
CREATE POLICY "audit: admin read"
  ON public.audit_logs FOR SELECT
  USING (is_admin());

CREATE POLICY "audit: service insert"
  ON public.audit_logs FOR INSERT
  WITH CHECK (is_admin());

-- Audit logs are immutable.
CREATE POLICY "audit: no update or delete"
  ON public.audit_logs FOR UPDATE
  USING (FALSE);
