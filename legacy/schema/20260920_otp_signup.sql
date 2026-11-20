-- ============================================================
-- EataLyft — Supabase Migration: OTP signup + abuse store (Phase 1)
-- Supabase is the OTP system of record (the older Firestore otp_codes
-- used by the realtor flow is untouched). All writes service-role only.
-- Run: npx supabase db push
-- ============================================================

-- ── 1. otp_requests ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.otp_requests (
  id                   UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  phone_hash           TEXT NOT NULL,
  phone_masked         TEXT NOT NULL,
  country_code         CHAR(2),                       -- for abuse analytics (never PII alone)
  channel              TEXT NOT NULL DEFAULT 'whatsapp' CHECK (channel IN ('whatsapp','sms','email')),
  code_hash            TEXT NOT NULL,                    -- HMAC-SHA256, peppered; never plaintext
  purpose              TEXT NOT NULL DEFAULT 'signup',
  expires_at           TIMESTAMPTZ NOT NULL,
  attempts             INTEGER NOT NULL DEFAULT 0,
  max_attempts         INTEGER NOT NULL DEFAULT 5,
  status               TEXT NOT NULL DEFAULT 'pending'
                       CHECK (status IN ('pending','verified','expired','failed','blocked')),
  provider_message_id  TEXT,
  delivery_status      TEXT,                              -- sent|delivered|read|failed (Meta ticks)
  delivery_error       TEXT,
  ip                   TEXT,
  device_id            TEXT,
  created_at           TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_otp_phone    ON public.otp_requests (phone_hash, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_otp_country  ON public.otp_requests (country_code, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_otp_provider ON public.otp_requests (provider_message_id);

-- ── 2. otp_blocks (phones, IPs, devices, countries) ─────────
CREATE TABLE IF NOT EXISTS public.otp_blocks (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  subject_type  TEXT NOT NULL CHECK (subject_type IN ('phone','ip','device','country')),
  subject_value TEXT NOT NULL,
  reason        TEXT NOT NULL DEFAULT '',
  blocked_until TIMESTAMPTZ NOT NULL,
  strike_count  INTEGER NOT NULL DEFAULT 1,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  UNIQUE (subject_type, subject_value)
);

-- ── 3. signup_sessions (one row per phone, token rotates) ───
CREATE TABLE IF NOT EXISTS public.signup_sessions (
  phone        TEXT PRIMARY KEY,                          -- E.164 digits
  token_hash   TEXT NOT NULL,                             -- current step token (sha256)
  state        TEXT NOT NULL DEFAULT 'PHONE_PENDING'
               CHECK (state IN ('PHONE_PENDING','PHONE_VERIFIED','PROFILE_COMPLETE','ACTIVE')),
  profile      JSONB,                                     -- validated profile payload (pre-activation only)
  expires_at   TIMESTAMPTZ NOT NULL,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ── 4. reserved_phones (registered + deleted numbers stay taken) ──
CREATE TABLE IF NOT EXISTS public.reserved_phones (
  phone       TEXT PRIMARY KEY,                           -- E.164 digits
  reason      TEXT NOT NULL DEFAULT 'registered'
              CHECK (reason IN ('registered','deleted','blocked')),
  reserved_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ── 5. RLS: deny-by-default, service role only ──────────────
ALTER TABLE public.otp_requests    ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.otp_blocks      ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.signup_sessions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.reserved_phones ENABLE ROW LEVEL SECURITY;

REVOKE ALL ON public.otp_requests    FROM anon, authenticated;
REVOKE ALL ON public.otp_blocks      FROM anon, authenticated;
REVOKE ALL ON public.signup_sessions FROM anon, authenticated;
REVOKE ALL ON public.reserved_phones FROM anon, authenticated;
-- No GRANTs, no policies: authenticated/anon see nothing. Reads that must
-- be public-adjacent (none) go through service-role API routes.

-- ── 6. Retention janitor (call from cron; pg_cron optional) ──
CREATE OR REPLACE FUNCTION public.purge_expired_otp(p_retention_days INTEGER DEFAULT 30)
RETURNS INTEGER LANGUAGE plpgsql SECURITY DEFINER SET search_path = public AS $$
DECLARE
  v_count INTEGER;
BEGIN
  DELETE FROM public.otp_requests
  WHERE created_at < NOW() - (p_retention_days || ' days')::INTERVAL;
  GET DIAGNOSTICS v_count = ROW_COUNT;
  DELETE FROM public.otp_blocks WHERE blocked_until < NOW() - INTERVAL '7 days';
  DELETE FROM public.signup_sessions WHERE expires_at < NOW() - INTERVAL '1 day';
  RETURN v_count;
END;
$$;

-- ── 7. Seeds: knobs + settings RBAC ─────────────────────────
INSERT INTO public.app_settings (key, value, description) VALUES
  ('otp_length',               '6',     'OTP digits'),
  ('otp_expiry_minutes',       '5',     'Code TTL'),
  ('otp_resend_cooldown_sec',  '60',    'Resend countdown'),
  ('otp_max_attempts',         '5',     'Wrong entries before the code dies'),
  ('otp_max_sends_10m',        '3',     'Sends per phone per 10 minutes'),
  ('otp_max_device_sends_10m', '10',    'Sends per device per 10 minutes'),
  ('otp_require_integrity',    'false', 'Require Play Integrity / App Attest on mobile sends'),
  ('otp_block_hours',          '[1,6,24]','Escalation ladder for repeat offenders'),
  ('otp_allowed_countries',    '["CM","GA","GQ","TD","CF","CG","NG","SN","CI","GH","BJ","TG","ML","BF","NE"]', 'Country allowlist (calling codes mapped in code)'),
  ('otp_channel_priority',     '["whatsapp","sms","email"]', 'Fallback order'),
  ('otp_template_en',          '"eatalyft_otp"', 'Meta auth template (EN)'),
  ('otp_template_fr',          '"eatalyft_otp_fr"', 'Meta auth template (FR)'),
  ('otp_daily_spend_cap',      '500',   'Max Meta sends per day before alert+pause'),
  ('otp_cost_per_send',        '0.03',  'Blended unit cost (USD) for spend estimates'),
  ('otp_whitelist_phones',     '[]',    'E.164 digits exempt from per-phone flood blocks (test numbers)'),
  ('otp_disposable_prefixes',  '[]',    'Blocked virtual/disposable number prefixes')
ON CONFLICT (key) DO NOTHING;

INSERT INTO public.role_permissions (role, permission) VALUES
  ('admin','otp:view'),('admin','otp:manage'),
  ('superadmin','otp:view'),('superadmin','otp:manage'),
  ('accountant','otp:view')
ON CONFLICT (role, permission) DO NOTHING;
