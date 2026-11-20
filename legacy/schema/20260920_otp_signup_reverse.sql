-- Reverse for 20260920_otp_signup.sql
DROP FUNCTION IF EXISTS public.purge_expired_otp(INTEGER);
DROP TABLE IF EXISTS public.reserved_phones;
DROP TABLE IF EXISTS public.signup_sessions;
DROP TABLE IF EXISTS public.otp_blocks;
DROP TABLE IF EXISTS public.otp_requests;
DELETE FROM public.role_permissions WHERE permission IN ('otp:view','otp:manage');
DELETE FROM public.app_settings WHERE key IN (
  'otp_length','otp_expiry_minutes','otp_resend_cooldown_sec','otp_max_attempts',
  'otp_max_sends_10m','otp_max_device_sends_10m','otp_require_integrity','otp_block_hours','otp_allowed_countries','otp_channel_priority',
  'otp_template_en','otp_template_fr','otp_daily_spend_cap','otp_cost_per_send','otp_whitelist_phones','otp_disposable_prefixes'
);
