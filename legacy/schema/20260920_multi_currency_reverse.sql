-- ============================================================
-- EataLyft — REVERSE migration for 20260920_multi_currency.sql
--
-- Rollback is SAFE because the backfill only MIRRORED existing XAF
-- balances into wallet_balances; the legacy XAF wallet columns were
-- never modified. Drop the mirror tables/columns and all ledger state
-- survives untouched in `wallets`.
--
-- Run manually ONLY after confirming the rate engine is stopped:
--   npx supabase db reset  (fresh)  — or apply via SQL editor.
-- ============================================================

-- 1. Drop trigger + audit function (reverse order)
DROP TRIGGER IF EXISTS trg_exchange_rates_audit ON public.exchange_rates;
DROP FUNCTION IF EXISTS public.log_exchange_rate_change();

-- 2. Remove FX ledger columns FIRST — this also drops the
--    fk_wallets/fk_wallet_tx/fk_payments_currency_code constraints,
--    unblocking the drop of `currencies` below.
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'wallets') THEN
    ALTER TABLE public.wallets DROP COLUMN IF EXISTS currency_code;
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'wallet_transactions') THEN
    ALTER TABLE public.wallet_transactions DROP COLUMN IF EXISTS currency_code;
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'payments') THEN
    ALTER TABLE public.payments DROP COLUMN IF EXISTS currency_code;
  END IF;
END $$;

-- 3. Drop per-currency mirror tables (FK order)
DROP TABLE IF EXISTS public.wallet_balances;
DROP TABLE IF EXISTS public.user_currency_preferences;
DROP TABLE IF EXISTS public.country_currency_map;
DROP TABLE IF EXISTS public.fx_margins;
DROP TABLE IF EXISTS public.exchange_rate_history;
DROP TABLE IF EXISTS public.exchange_rates;
DROP TABLE IF EXISTS public.currencies;

-- 4. Remove FX seed permissions + settings (idempotent)
DELETE FROM public.role_permissions WHERE permission IN ('currencies:view','currencies:manage','rates:override','fx_margins:manage');
DELETE FROM public.app_settings WHERE key IN (
  'fx_pivot_currency','fx_refresh_interval_seconds','fx_stale_after_seconds',
  'fx_deviation_threshold_percent','fx_quote_ttl_seconds','fx_major_pairs'
);

-- NOTE: RLS policy objects on the dropped tables are removed automatically.
-- The legacy XAF balance columns (`balance_xaf` / `balance`) are untouched.