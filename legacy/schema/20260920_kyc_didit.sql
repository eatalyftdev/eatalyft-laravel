-- ============================================================
-- EataLyft — Supabase Migration: KYC / Identity Verification (Didit)
-- XAF base untouched. All user_ids are Firebase UIDs (TEXT), matching
-- the FX `user_currency_preferences` pattern: per-user reads go through
-- the API (service role + Firebase verifyToken), never raw SQL.
-- Money/limits are INTEGER MINOR UNITS. Run: npx supabase db push
-- Reverse: 20260920_kyc_didit_reverse.sql
-- ============================================================

-- ── Internal status enum (app-side only; provider strings never leak) ──
DO $$ BEGIN
  CREATE TYPE kyc_status AS ENUM (
    'NOT_STARTED','PENDING','IN_REVIEW','VERIFIED','REJECTED','EXPIRED'
  );
EXCEPTION WHEN duplicate_object THEN NULL;
END $$;

-- ════════════════════════════════════════════════════════════
-- 1. kyc_profiles — one row per user (canonical state)
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS public.kyc_profiles (
  user_id               TEXT PRIMARY KEY,   -- Firebase UID
  status                kyc_status NOT NULL DEFAULT 'NOT_STARTED',
  tier                  INTEGER NOT NULL DEFAULT 0 CHECK (tier BETWEEN 0 AND 2),
  provider              TEXT NOT NULL DEFAULT 'didit',
  current_session_id    TEXT,
  verified_at           TIMESTAMPTZ,
  rejected_reason_code  TEXT,
  attempts              INTEGER NOT NULL DEFAULT 0 CHECK (attempts >= 0),
  last_attempt_at       TIMESTAMPTZ,
  created_at            TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at            TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ════════════════════════════════════════════════════════════
-- 2. kyc_sessions — every provider session we open
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS public.kyc_sessions (
  id                   UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id              TEXT NOT NULL REFERENCES public.kyc_profiles(user_id) ON DELETE CASCADE,
  provider             TEXT NOT NULL DEFAULT 'didit',
  provider_session_id  TEXT NOT NULL UNIQUE,          -- Didit session_id (ses_…)
  workflow_id          TEXT NOT NULL,
  environment          TEXT NOT NULL DEFAULT 'sandbox' CHECK (environment IN ('sandbox','production')),
  provider_status      TEXT,                           -- raw Didit string (audit only)
  internal_status      kyc_status NOT NULL DEFAULT 'PENDING',
  tier_target          INTEGER NOT NULL DEFAULT 1 CHECK (tier_target BETWEEN 1 AND 2),
  created_at           TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  completed_at         TIMESTAMPTZ
);
CREATE INDEX IF NOT EXISTS idx_kyc_sessions_user ON public.kyc_sessions(user_id, created_at DESC);

-- ════════════════════════════════════════════════════════════
-- 3. kyc_results — data minimization: flags + encrypted PII only.
--    NEVER document images / selfies / full OCR extracts.
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS public.kyc_results (
  id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id             TEXT NOT NULL REFERENCES public.kyc_profiles(user_id) ON DELETE CASCADE,
  session_id          UUID NOT NULL REFERENCES public.kyc_sessions(id) ON DELETE CASCADE,
  document_type       TEXT,
  issuing_country     CHAR(2),
  full_name_enc       TEXT,        -- AES-256-GCM, only when a business rule needs it
  dob_enc             TEXT,        -- AES-256-GCM, only when a business rule needs it
  liveness_pass       BOOLEAN,
  face_match_pass     BOOLEAN,
  ip_check_pass       BOOLEAN,
  aml_pass            BOOLEAN,
  address_pass        BOOLEAN,
  is_current          BOOLEAN NOT NULL DEFAULT TRUE,
  retained_until      TIMESTAMPTZ, -- retention deadline; janitor clears past-due rows
  created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_kyc_results_user ON public.kyc_results(user_id, is_current);

-- ════════════════════════════════════════════════════════════
-- 4. kyc_webhook_events — idempotency + audit (payload ENCRYPTED)
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS public.kyc_webhook_events (
  id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  event_id         TEXT NOT NULL UNIQUE,   -- Didit event_id (reused on retries)
  session_ref      TEXT,                    -- Didit session_id
  signature_valid  BOOLEAN NOT NULL,
  processed_at     TIMESTAMPTZ,
  payload_enc      TEXT NOT NULL,           -- AES-256-GCM raw body
  received_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_kyc_webhook_session ON public.kyc_webhook_events(session_ref);

-- ════════════════════════════════════════════════════════════
-- 5. kyc_review_actions — manual admin decisions (fully audited)
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS public.kyc_review_actions (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id     TEXT NOT NULL REFERENCES public.kyc_profiles(user_id) ON DELETE CASCADE,
  session_id  UUID REFERENCES public.kyc_sessions(id) ON DELETE SET NULL,
  decision    TEXT NOT NULL CHECK (decision IN ('APPROVED','DECLINED')),
  reason      TEXT NOT NULL,
  actor_id    TEXT NOT NULL,                -- admin Firebase UID
  created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_kyc_review_user ON public.kyc_review_actions(user_id, created_at DESC);

-- ════════════════════════════════════════════════════════════
-- 6. kyc_tier_limits — admin-editable limits (minor units)
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS public.kyc_tier_limits (
  tier              INTEGER NOT NULL CHECK (tier BETWEEN 0 AND 2),
  currency_code     TEXT NOT NULL DEFAULT 'XAF',
  service           TEXT NOT NULL DEFAULT '*',   -- '*' = all services
  daily_max_minor   BIGINT CHECK (daily_max_minor IS NULL OR daily_max_minor >= 0),
  monthly_max_minor BIGINT CHECK (monthly_max_minor IS NULL OR monthly_max_minor >= 0),
  max_single_minor  BIGINT CHECK (max_single_minor IS NULL OR max_single_minor >= 0),
  updated_by        TEXT,
  updated_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  PRIMARY KEY (tier, currency_code, service)
);

-- ════════════════════════════════════════════════════════════
-- 7. integration_credentials — encrypted third-party vault (Phase 6 UI)
--    Secrets are NEVER readable by anon/authenticated: RLS deny-all,
--    service role only. UI shows masked values (last 4) from metadata.
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS public.integration_credentials (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  provider        TEXT NOT NULL,                 -- 'didit', 'mesomb', 'fx_ecb', 'mailgun', …
  environment     TEXT NOT NULL DEFAULT 'sandbox' CHECK (environment IN ('sandbox','production')),
  key_name        TEXT NOT NULL,                 -- 'api_key', 'workflow_tier1', 'webhook_secret', …
  value_enc       TEXT NOT NULL,                 -- AES-256-GCM (iv:ciphertext:tag, base64)
  key_version     INTEGER NOT NULL DEFAULT 1,
  hint_last4      TEXT,                          -- masked display, safe for UI
  created_by      TEXT,
  rotated_at      TIMESTAMPTZ,
  last_tested_at  TIMESTAMPTZ,
  last_test_ok    BOOLEAN,
  status          TEXT NOT NULL DEFAULT 'active',
  is_active       BOOLEAN NOT NULL DEFAULT TRUE,
  created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  UNIQUE (provider, environment, key_name)
);

-- ════════════════════════════════════════════════════════════
-- 8. Denormalized fast-check columns on profiles (Firebase-UID join)
-- ════════════════════════════════════════════════════════════
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name='profiles') THEN
    ALTER TABLE public.profiles ADD COLUMN IF NOT EXISTS kyc_status TEXT NOT NULL DEFAULT 'NOT_STARTED';
    ALTER TABLE public.profiles ADD COLUMN IF NOT EXISTS kyc_tier INTEGER NOT NULL DEFAULT 0;
  END IF;
END $$;

-- ════════════════════════════════════════════════════════════
-- 9. RLS — deny-by-default; service role bypasses for all writes.
--    Per-user reads happen in the API (Firebase verifyToken), never SQL.
-- ════════════════════════════════════════════════════════════
ALTER TABLE public.kyc_profiles            ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.kyc_sessions            ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.kyc_results             ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.kyc_webhook_events      ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.kyc_review_actions      ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.kyc_tier_limits         ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.integration_credentials ENABLE ROW LEVEL SECURITY;

-- Own-status reads happen in the API (Firebase verifyToken), never raw SQL:
-- Firebase UIDs are opaque to auth.uid(), so no per-user SELECT policy exists
-- (deny-by-default + REVOKE below). Limits are finance-readable for consoles.
CREATE POLICY "kyc_limits: finance read" ON public.kyc_tier_limits
  FOR SELECT USING (is_admin() OR is_finance_role());
-- Results / sessions / events / reviews / vault: NO client policies at all
-- (anon + authenticated denied; service role bypasses RLS implicitly).
REVOKE ALL ON public.kyc_profiles            FROM anon, authenticated;
REVOKE ALL ON public.kyc_sessions            FROM anon, authenticated;
REVOKE ALL ON public.kyc_results             FROM anon, authenticated;
REVOKE ALL ON public.kyc_webhook_events      FROM anon, authenticated;
REVOKE ALL ON public.kyc_review_actions      FROM anon, authenticated;
REVOKE ALL ON public.integration_credentials FROM anon, authenticated;
GRANT SELECT ON public.kyc_tier_limits TO authenticated;

-- ════════════════════════════════════════════════════════════
-- 10. Seeds — tier limits (XAF minor), RBAC, knobs
-- ════════════════════════════════════════════════════════════
INSERT INTO public.kyc_tier_limits (tier, currency_code, service, daily_max_minor, monthly_max_minor, max_single_minor, updated_by) VALUES
  (0,'XAF','*',        25000,    200000,    25000, 'seed'),
  (1,'XAF','*',       500000,   5000000,   250000, 'seed'),
  (2,'XAF','*',      5000000, 100000000,  2000000, 'seed'),
  (2,'XAF','transfer',5000000, 100000000,  2000000, 'seed')
ON CONFLICT (tier, currency_code, service) DO NOTHING;

INSERT INTO public.role_permissions (role, permission) VALUES
  ('admin','kyc:view'),('admin','kyc:review'),('admin','kyc:configure'),
  ('superadmin','kyc:view'),('superadmin','kyc:review'),('superadmin','kyc:configure'),
  ('accountant','kyc:view')
ON CONFLICT (role, permission) DO NOTHING;

INSERT INTO public.app_settings (key, value, description) VALUES
  ('kyc_max_attempts',        '3',    'Max verification sessions per user before cooldown'),
  ('kyc_cooldown_hours',      '24',   'Cooldown after exhausting attempts'),
  ('kyc_retention_days',      '365',  'PII retention for kyc_results before janitor purge'),
  ('kyc_reverify_on_expire',  'true', 'Kyc Expired events demote tier to re-verification'),
  ('kyc_workflow_tier1',      '""',   'Didit workflow UUID for Tier 1 (basic ID+liveness+face+IP)'),
  ('kyc_workflow_tier2',      '""',   'Didit workflow UUID for Tier 2 (+AML/PoA enhanced)')
ON CONFLICT (key) DO NOTHING;
