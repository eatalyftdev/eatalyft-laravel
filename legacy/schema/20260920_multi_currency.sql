-- ============================================================
-- EataLyft — Supabase Migration: Multi-Currency System (Phase 1)
-- XAF remains the base/default currency. All existing ledger data
-- is backfilled to XAF (reversible — see _reverse migration).
-- Money is INTEGER MINOR UNITS (BIGINT) or NUMERIC(24,10); never floats.
-- Run: npx supabase db push
-- ============================================================

-- ════════════════════════════════════════════════════════════
-- SECTION 1 — CURRENCIES CATALOG
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS public.currencies (
  code                    TEXT PRIMARY KEY,            -- ISO 4217
  name                    TEXT NOT NULL,
  symbol                  TEXT,
  decimal_places          INTEGER NOT NULL DEFAULT 0 CHECK (decimal_places BETWEEN 0 AND 6),
  is_active               BOOLEAN NOT NULL DEFAULT TRUE,
  is_base                 BOOLEAN NOT NULL DEFAULT FALSE,   -- XAF = true, exactly one
  is_selectable_by_users  BOOLEAN NOT NULL DEFAULT TRUE,
  allowed_countries       TEXT[] NOT NULL DEFAULT '{}',
  display_order           INTEGER NOT NULL DEFAULT 0,
  icon                    TEXT,                            -- emoji flag / icon
  updated_by              TEXT,
  created_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at              TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ════════════════════════════════════════════════════════════
-- SECTION 2 — EXCHANGE RATES + APPEND-ONLY HISTORY
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS public.exchange_rates (
  id                   UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  base_code            TEXT NOT NULL REFERENCES public.currencies(code),
  quote_code           TEXT NOT NULL REFERENCES public.currencies(code),
  rate                 NUMERIC(24,10) NOT NULL CHECK (rate > 0),
  source               TEXT NOT NULL DEFAULT 'manual',     -- provider id, 'BEAC_PEG', or admin key
  fetched_at           TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  is_manual_override   BOOLEAN NOT NULL DEFAULT FALSE,
  override_by          TEXT,
  expires_at           TIMESTAMPTZ,                        -- NULL = never
  created_at           TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at           TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  UNIQUE (base_code, quote_code),
  CHECK (base_code <> quote_code)
);
CREATE INDEX IF NOT EXISTS idx_exchange_rates_base   ON public.exchange_rates(base_code);
CREATE INDEX IF NOT EXISTS idx_exchange_rates_fetched ON public.exchange_rates(fetched_at DESC);

-- Append-only log of every rate change (old, new, source, who/what changed it).
CREATE TABLE IF NOT EXISTS public.exchange_rate_history (
  id          BIGSERIAL PRIMARY KEY,
  base_code   TEXT NOT NULL REFERENCES public.currencies(code),
  quote_code  TEXT NOT NULL REFERENCES public.currencies(code),
  old_rate    NUMERIC(24,10),
  new_rate    NUMERIC(24,10) NOT NULL,
  source      TEXT NOT NULL,
  reason      TEXT,
  changed_by  TEXT NOT NULL DEFAULT 'system',
  created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_rate_history_pair ON public.exchange_rate_history(base_code, quote_code, created_at DESC);

-- Trigger: every INSERT/rate-change is mirrored into the history log.
-- `changed_by`/`reason` come from `set_config('app.actor_id'|'app.change_reason', ...)`
-- set by the writing server code; defaults to 'system' when absent.
CREATE OR REPLACE FUNCTION public.log_exchange_rate_change()
RETURNS TRIGGER LANGUAGE plpgsql SECURITY DEFINER SET search_path = public AS $$
DECLARE
  v_actor  TEXT := NULLIF(current_setting('app.actor_id', true), '');
  v_reason TEXT := NULLIF(current_setting('app.change_reason', true), '');
BEGIN
  IF TG_OP = 'INSERT' THEN
    INSERT INTO public.exchange_rate_history(base_code, quote_code, old_rate, new_rate, source, reason, changed_by)
    VALUES (NEW.base_code, NEW.quote_code, NULL, NEW.rate, NEW.source, COALESCE(v_reason, 'refresh'), COALESCE(v_actor, 'system'));
    RETURN NEW;
  END IF;

  IF OLD.rate IS DISTINCT FROM NEW.rate OR OLD.is_manual_override IS DISTINCT FROM NEW.is_manual_override THEN
    INSERT INTO public.exchange_rate_history(base_code, quote_code, old_rate, new_rate, source, reason, changed_by)
    VALUES (NEW.base_code, NEW.quote_code, OLD.rate, NEW.rate, NEW.source, COALESCE(v_reason, 'update'), COALESCE(v_actor, 'system'));
  END IF;
  RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_exchange_rates_audit ON public.exchange_rates;
CREATE TRIGGER trg_exchange_rates_audit
  AFTER INSERT OR UPDATE ON public.exchange_rates
  FOR EACH ROW EXECUTE FUNCTION public.log_exchange_rate_change();

-- ════════════════════════════════════════════════════════════
-- SECTION 3 — FX MARGINS (spread / fixed fee / min-max per pair)
-- Row with base_code IS NULL AND quote_code IS NULL = GLOBAL DEFAULT.
-- customer rate = market rate adjusted by margin (spread % + fixed fee).
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS public.fx_margins (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  base_code         TEXT REFERENCES public.currencies(code),   -- NULL = default
  quote_code        TEXT REFERENCES public.currencies(code),
  spread_percent    NUMERIC(10,6) NOT NULL DEFAULT 0 CHECK (spread_percent >= 0),
  fixed_fee_minor   BIGINT NOT NULL DEFAULT 0 CHECK (fixed_fee_minor >= 0),
  min_quote_minor   BIGINT CHECK (min_quote_minor IS NULL OR min_quote_minor >= 0),
  max_quote_minor   BIGINT CHECK (max_quote_minor IS NULL OR max_quote_minor >= 0),
  is_active         BOOLEAN NOT NULL DEFAULT TRUE,
  updated_by        TEXT,
  created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CHECK ((base_code IS NULL AND quote_code IS NULL) OR (base_code IS NOT NULL AND quote_code IS NOT NULL))
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_fx_margins_pair
  ON public.fx_margins (COALESCE(base_code,''), COALESCE(quote_code,''));

-- ════════════════════════════════════════════════════════════
-- SECTION 4 — COUNTRY → CURRENCY MAP
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS public.country_currency_map (
  country_code      CHAR(2) PRIMARY KEY,
  country_name      TEXT NOT NULL,
  default_currency  TEXT NOT NULL REFERENCES public.currencies(code),
  is_supported      BOOLEAN NOT NULL DEFAULT FALSE,
  created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at        TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ════════════════════════════════════════════════════════════
-- SECTION 5 — USER CURRENCY PREFERENCES
-- Server-written (Next API + rate-engine caller). `user_id` = Firebase UID.
-- Supabase mirrors the Firestore authoritative `users/{uid}/currency` doc.
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS public.user_currency_preferences (
  id                 UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id            TEXT NOT NULL,
  display_currency   TEXT NOT NULL DEFAULT 'XAF' REFERENCES public.currencies(code),
  detected_country   CHAR(2),
  detected_at        TIMESTAMPTZ,
  selected_manually  BOOLEAN NOT NULL DEFAULT FALSE,
  created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  UNIQUE (user_id)
);
CREATE INDEX IF NOT EXISTS idx_user_prefs_currency ON public.user_currency_preferences(display_currency);

-- ════════════════════════════════════════════════════════════
-- SECTION 6 — WALLET COLUMNS + PER-CURRENCY BALANCES
-- One wallet per user per currency lives in the normalized
-- `wallet_balances` table (mirrors Firestore wallets/{uid}/balances/*).
-- The legacy XAF balance columns are untouched (ledger immutability).
-- ════════════════════════════════════════════════════════════
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'wallets') THEN
    ALTER TABLE public.wallets ADD COLUMN IF NOT EXISTS currency_code TEXT NOT NULL DEFAULT 'XAF';
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'wallet_transactions') THEN
    ALTER TABLE public.wallet_transactions ADD COLUMN IF NOT EXISTS currency_code TEXT NOT NULL DEFAULT 'XAF';
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'payments') THEN
    ALTER TABLE public.payments ADD COLUMN IF NOT EXISTS currency_code TEXT NOT NULL DEFAULT 'XAF';
  END IF;
END $$;

-- FKs to currencies.code are added LATER (see "SECTION 8b") because the
-- currency seed must run first — existing rows default to 'XAF'.

CREATE TABLE IF NOT EXISTS public.wallet_balances (
  id                      UUID NOT NULL DEFAULT gen_random_uuid() UNIQUE,
  wallet_ref              TEXT NOT NULL,          -- wallet key (Firebase UID / wallet id)
  wallet_id               UUID REFERENCES public.wallets(id) ON DELETE CASCADE,
  user_id                 TEXT,                   -- Firebase UID
  currency_code           TEXT NOT NULL REFERENCES public.currencies(code),
  balance_minor           BIGINT NOT NULL DEFAULT 0 CHECK (balance_minor >= 0),
  total_credited_minor    BIGINT NOT NULL DEFAULT 0 CHECK (total_credited_minor >= 0),
  total_debited_minor     BIGINT NOT NULL DEFAULT 0 CHECK (total_debited_minor >= 0),
  is_frozen               BOOLEAN NOT NULL DEFAULT FALSE,
  created_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  PRIMARY KEY (wallet_ref, currency_code)
);
CREATE INDEX IF NOT EXISTS idx_wallet_balances_user     ON public.wallet_balances(user_id);
CREATE INDEX IF NOT EXISTS idx_wallet_balances_currency ON public.wallet_balances(currency_code);

-- Backfill: existing wallets are XAF. Mirror only — NEVER UPDATE existing
-- ledger rows. Both legacy wallet shapes are handled (Horizon `uid/balance_xaf`
-- and migration-001 `user_id/balance`). Reversible: drop `wallet_balances`
-- and the added columns; source balances are untouched.
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'wallets')
     AND EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'wallets' AND column_name = 'balance_xaf') THEN
    INSERT INTO public.wallet_balances (wallet_ref, wallet_id, user_id, currency_code, balance_minor, total_credited_minor, total_debited_minor)
    SELECT w.uid, w.id, w.uid, 'XAF',
           COALESCE(w.balance_xaf, 0), COALESCE(w.total_credited, 0), COALESCE(w.total_debited, 0)
    FROM public.wallets w
    ON CONFLICT (wallet_ref, currency_code) DO NOTHING;
  ELSIF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'wallets')
        AND EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'wallets' AND column_name = 'balance') THEN
    INSERT INTO public.wallet_balances (wallet_ref, wallet_id, user_id, currency_code, balance_minor, total_credited_minor, total_debited_minor)
    SELECT w.user_id::TEXT, w.id, w.user_id::TEXT, 'XAF',
           COALESCE(ROUND(w.balance)::BIGINT, 0), 0, 0
    FROM public.wallets w
    ON CONFLICT (wallet_ref, currency_code) DO NOTHING;
  END IF;
END $$;

-- ════════════════════════════════════════════════════════════
-- SECTION 7 — ROW LEVEL SECURITY (defense-in-depth; service role bypasses)
-- ════════════════════════════════════════════════════════════
ALTER TABLE public.currencies               ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.exchange_rates           ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.exchange_rate_history    ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.fx_margins               ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.country_currency_map     ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.user_currency_preferences ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.wallet_balances          ENABLE ROW LEVEL SECURITY;

-- Public catalog — readable by everyone (drives the rate calculator + selector).
CREATE POLICY "currencies: public read"        ON public.currencies        FOR SELECT USING (TRUE);
CREATE POLICY "currencies: admin write"        ON public.currencies        FOR ALL USING (is_admin()) WITH CHECK (is_admin());

-- Public rates — readable by everyone; writes via admin API or rate engine (service role).
CREATE POLICY "rates: public read"             ON public.exchange_rates    FOR SELECT USING (TRUE);
CREATE POLICY "rates: admin write"             ON public.exchange_rates    FOR ALL USING (is_admin() OR has_permission('rates:override')) WITH CHECK (is_admin() OR has_permission('rates:override'));

-- Append-only history — readable by finance; client writes forbidden (trigger is SECURITY DEFINER).
CREATE POLICY "rate_history: finance read"     ON public.exchange_rate_history FOR SELECT USING (is_admin() OR is_finance_role() OR has_permission('currencies:view'));
CREATE POLICY "rate_history: no client insert" ON public.exchange_rate_history FOR INSERT WITH CHECK (FALSE);
CREATE POLICY "rate_history: no update"        ON public.exchange_rate_history FOR UPDATE USING (FALSE);
CREATE POLICY "rate_history: no delete"        ON public.exchange_rate_history FOR DELETE USING (FALSE);

-- FX margins — finance only.
CREATE POLICY "fx_margins: finance read"       ON public.fx_margins        FOR SELECT USING (is_admin() OR is_finance_role());
CREATE POLICY "fx_margins: manage write"       ON public.fx_margins        FOR ALL USING (is_admin() OR has_permission('fx_margins:manage')) WITH CHECK (is_admin() OR has_permission('fx_margins:manage'));

-- Country map — public read, admin write.
CREATE POLICY "country_map: public read"       ON public.country_currency_map FOR SELECT USING (TRUE);
CREATE POLICY "country_map: admin write"       ON public.country_currency_map FOR ALL USING (is_admin()) WITH CHECK (is_admin());

-- Preferences — server-driven (Next API with service role). No direct client writes.
CREATE POLICY "prefs: finance read"            ON public.user_currency_preferences FOR SELECT USING (is_admin() OR is_finance_role());
CREATE POLICY "prefs: admin write"             ON public.user_currency_preferences FOR ALL USING (is_admin()) WITH CHECK (is_admin());

-- Per-currency balances — finance read, admin write.
CREATE POLICY "wallet_balances: finance read"  ON public.wallet_balances    FOR SELECT USING (is_admin() OR is_finance_role());
CREATE POLICY "wallet_balances: admin write"   ON public.wallet_balances    FOR ALL USING (is_admin()) WITH CHECK (is_admin());

-- ════════════════════════════════════════════════════════════
-- SECTION 8 — SEED DATA
-- Actor/reason tags so the audit trigger attributes these rows to the seed
-- (the rate engine's own writes default to actor 'system' / reason 'refresh').
-- ════════════════════════════════════════════════════════════
SELECT set_config('app.actor_id', 'seed', true);
SELECT set_config('app.change_reason', 'seed', true);
INSERT INTO public.currencies (code, name, symbol, decimal_places, is_active, is_base, is_selectable_by_users, allowed_countries, display_order, icon, updated_by) VALUES
  ('XAF','Central African CFA Franc','FCFA',0,TRUE,TRUE,TRUE,'{CM,GA,GQ,TD,CF,CG}',0,'🇨🇲','seed'),
  ('XOF','West African CFA Franc','FCFA',0,TRUE,FALSE,TRUE,'{SN,BF,CI,ML,NE,TG,BJ,GW}',1,'🌍','seed'),
  ('EUR','Euro','€',2,TRUE,FALSE,TRUE,'{FR,DE,ES,IT,BE,PT,NL,BE,LU,GR,AT,FI,IE}',2,'🇪🇺','seed'),
  ('USD','US Dollar','$',2,TRUE,FALSE,TRUE,'{US}',3,'🇺🇸','seed'),
  ('GBP','British Pound','£',2,TRUE,FALSE,TRUE,'{GB}',4,'🇬🇧','seed'),
  ('NGN','Nigerian Naira','₦',2,TRUE,FALSE,TRUE,'{NG}',5,'🇳🇬','seed'),
  ('GHS','Ghanaian Cedi','GH₵',2,TRUE,FALSE,TRUE,'{GH}',6,'🇬🇭','seed'),
  ('KES','Kenyan Shilling','KSh',2,TRUE,FALSE,TRUE,'{KE}',7,'🇰🇪','seed'),
  ('ZAR','South African Rand','R',2,TRUE,FALSE,TRUE,'{ZA}',8,'🇿🇦','seed'),
  ('CAD','Canadian Dollar','CA$',2,TRUE,FALSE,TRUE,'{CA}',9,'🇨🇦','seed'),
  ('CNY','Chinese Yuan','¥',2,TRUE,FALSE,TRUE,'{CN}',10,'🇨🇳','seed'),
  ('JPY','Japanese Yen','¥',0,TRUE,FALSE,TRUE,'{JP}',11,'🇯🇵','seed'),
  ('AED','UAE Dirham','د.إ',2,TRUE,FALSE,TRUE,'{AE}',12,'🇦🇪','seed'),
  ('SAR','Saudi Riyal','﷼',2,TRUE,FALSE,TRUE,'{SA}',13,'🇸🇦','seed'),
  ('MAD','Moroccan Dirham','MAD',2,TRUE,FALSE,TRUE,'{MA}',14,'🇲🇦','seed'),
  ('CHF','Swiss Franc','CHF',2,TRUE,FALSE,TRUE,'{CH}',15,'🇨🇭','seed'),
  ('INR','Indian Rupee','₹',2,TRUE,FALSE,TRUE,'{IN}',16,'🇮🇳','seed')
ON CONFLICT (code) DO UPDATE SET
  name = EXCLUDED.name, symbol = EXCLUDED.symbol, decimal_places = EXCLUDED.decimal_places,
  allowed_countries = EXCLUDED.allowed_countries, icon = EXCLUDED.icon,
  updated_at = NOW();

-- Fixed BEAC pegs: XAF and XOF are fixed at 1 EUR = 655.957 (never market-driven).
-- Cross rates to other currencies are derived through EUR by the rate engine.
INSERT INTO public.exchange_rates (base_code, quote_code, rate, source, fetched_at, is_manual_override, override_by, expires_at) VALUES
  ('EUR','XAF',655.957,'BEAC_PEG',NOW(),TRUE,'system',NULL),
  ('XAF','EUR',0.0015245284,'BEAC_PEG',NOW(),TRUE,'system',NULL),
  ('EUR','XOF',655.957,'BEAC_PEG',NOW(),TRUE,'system',NULL),
  ('XOF','EUR',0.0015245284,'BEAC_PEG',NOW(),TRUE,'system',NULL),
  ('XAF','XOF',1,'UEMOA_BEAC_PEG',NOW(),TRUE,'system',NULL),
  ('XOF','XAF',1,'UEMOA_BEAC_PEG',NOW(),TRUE,'system',NULL)
ON CONFLICT (base_code, quote_code) DO UPDATE SET
  rate = EXCLUDED.rate, source = EXCLUDED.source, fetched_at = NOW(),
  is_manual_override = TRUE, override_by = 'system', expires_at = NULL;

-- Global default margin (no spread, no fixed fee).
INSERT INTO public.fx_margins (base_code, quote_code, spread_percent, fixed_fee_minor, min_quote_minor, max_quote_minor, updated_by)
VALUES (NULL, NULL, 0, 0, NULL, NULL, 'seed')
ON CONFLICT (COALESCE(base_code,''), COALESCE(quote_code,'')) DO NOTHING;

-- Country → currency map (CM supported; everything else = convenience pricing only).
INSERT INTO public.country_currency_map (country_code, country_name, default_currency, is_supported) VALUES
  ('CM','Cameroon','XAF',TRUE),
  ('GA','Gabon','XAF',FALSE),('GQ','Equatorial Guinea','XAF',FALSE),('TD','Chad','XAF',FALSE),
  ('CF','Central African Republic','XAF',FALSE),('CG','Congo','XAF',FALSE),
  ('SN','Senegal','XOF',FALSE),('BF','Burkina Faso','XOF',FALSE),('CI','Côte d''Ivoire','XOF',FALSE),
  ('ML','Mali','XOF',FALSE),('NE','Niger','XOF',FALSE),('TG','Togo','XOF',FALSE),('BJ','Benin','XOF',FALSE),
  ('US','United States','USD',FALSE),('GB','United Kingdom','GBP',FALSE),
  ('FR','France','EUR',FALSE),('DE','Germany','EUR',FALSE),('ES','Spain','EUR',FALSE),
  ('IT','Italy','EUR',FALSE),('BE','Belgium','EUR',FALSE),('PT','Portugal','EUR',FALSE),
  ('CH','Switzerland','CHF',FALSE),('NG','Nigeria','NGN',FALSE),('GH','Ghana','GHS',FALSE),
  ('KE','Kenya','KES',FALSE),('ZA','South Africa','ZAR',FALSE),('CA','Canada','CAD',FALSE),
  ('CN','China','CNY',FALSE),('JP','Japan','JPY',FALSE),('AE','United Arab Emirates','AED',FALSE),
  ('SA','Saudi Arabia','SAR',FALSE),('MA','Morocco','MAD',FALSE),('IN','India','INR',FALSE)
ON CONFLICT (country_code) DO NOTHING;

-- FX RBAC permissions for the admin console (finance_admin = accountant/admin/superadmin).
INSERT INTO public.role_permissions (role, permission) VALUES
  ('admin','currencies:view'),('admin','currencies:manage'),('admin','rates:override'),('admin','fx_margins:manage'),
  ('superadmin','currencies:view'),('superadmin','currencies:manage'),('superadmin','rates:override'),('superadmin','fx_margins:manage'),
  ('accountant','currencies:view'),('accountant','fx_margins:manage')
ON CONFLICT (role, permission) DO NOTHING;

-- Rate-engine tuning knobs (admin-editable via /admin/currencies).
INSERT INTO public.app_settings (key, value, description) VALUES
  ('fx_pivot_currency',            '"EUR"',  'Cross-rate pivot currency'),
  ('fx_refresh_interval_seconds',  '900',    'Scheduled rate refresh interval (default 15 min)'),
  ('fx_stale_after_seconds',       '3600',   'Treat rates older than this as stale and block conversions'),
  ('fx_deviation_threshold_percent','2.0',   'Alert + reject provider updates deviating more than X%'),
  ('fx_quote_ttl_seconds',         '60',     'Signed FX quote validity window'),
  ('fx_major_pairs',               '["EUR-XAF","USD-XAF","GBP-XAF","EUR-XOF"]','Pairs refreshed every tick')
ON CONFLICT (key) DO NOTHING;

-- ════════════════════════════════════════════════════════════
-- SECTION 8b — DEFERRED FKs (must run AFTER currencies are seeded:
-- existing ledger rows default to 'XAF' and XAF must already exist)
-- ════════════════════════════════════════════════════════════
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'wallets')
     AND NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_wallets_currency_code') THEN
    ALTER TABLE public.wallets ADD CONSTRAINT fk_wallets_currency_code FOREIGN KEY (currency_code) REFERENCES public.currencies(code);
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'wallet_transactions')
     AND NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_wallet_tx_currency_code') THEN
    ALTER TABLE public.wallet_transactions ADD CONSTRAINT fk_wallet_tx_currency_code FOREIGN KEY (currency_code) REFERENCES public.currencies(code);
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'payments')
     AND NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_payments_currency_code') THEN
    ALTER TABLE public.payments ADD CONSTRAINT fk_payments_currency_code FOREIGN KEY (currency_code) REFERENCES public.currencies(code);
  END IF;
END $$;

-- ════════════════════════════════════════════════════════════
-- SECTION 9 — GRANTS (service_role keeps implicit full access)
-- ════════════════════════════════════════════════════════════
REVOKE ALL ON public.exchange_rate_history    FROM anon, authenticated;
REVOKE ALL ON public.fx_margins               FROM anon, authenticated;
REVOKE ALL ON public.user_currency_preferences FROM anon, authenticated;
REVOKE ALL ON public.wallet_balances          FROM anon, authenticated;

GRANT SELECT ON public.currencies           TO anon, authenticated;
GRANT SELECT ON public.exchange_rates       TO anon, authenticated;
GRANT SELECT ON public.country_currency_map TO anon, authenticated;