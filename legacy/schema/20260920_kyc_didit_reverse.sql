-- ============================================================
-- EataLyft — REVERSE migration for 20260920_kyc_didit.sql
-- Drops KYC tables + seeds. profiles.kyc_status/kyc_tier columns are
-- removed; all other profile data is untouched.
-- ============================================================

DROP TABLE IF EXISTS public.integration_credentials;
DROP TABLE IF EXISTS public.kyc_review_actions;
DROP TABLE IF EXISTS public.kyc_webhook_events;
DROP TABLE IF EXISTS public.kyc_results;
DROP TABLE IF EXISTS public.kyc_sessions;
DROP TABLE IF EXISTS public.kyc_profiles;
DROP TABLE IF EXISTS public.kyc_tier_limits;
DO $$ BEGIN
  DROP TYPE IF EXISTS kyc_status;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name='profiles') THEN
    ALTER TABLE public.profiles DROP COLUMN IF EXISTS kyc_status;
    ALTER TABLE public.profiles DROP COLUMN IF EXISTS kyc_tier;
  END IF;
END $$;

DELETE FROM public.role_permissions WHERE permission IN ('kyc:view','kyc:review','kyc:configure');
DELETE FROM public.app_settings WHERE key IN (
  'kyc_max_attempts','kyc_cooldown_hours','kyc_retention_days',
  'kyc_reverify_on_expire','kyc_workflow_tier1','kyc_workflow_tier2'
);
