-- [FEATURE] Payment gateway layer (Notch Pay primary, MeSomb fallback) — REVERSE
-- [CHANGES] drops payment_providers, payment_attempts, gateway_webhook_events (policies drop with tables)
--
-- Rollback note: safe to run even long after the forward. Drops ONLY the three
-- tables this feature created (DROP ... IF EXISTS — touches no other column,
-- index, or table). Firestore transactions/{ref} and the payments mirror are
-- untouched; the canonical payment ledger survives the rollback. After rollback,
-- gateway routing falls back to env-key detection (isNotchConfigured /
-- isMeSombConfigured) until the forward is re-applied.

begin;

drop table if exists public.gateway_webhook_events;
drop table if exists public.payment_attempts;
drop table if exists public.payment_providers;

notify pgrst, 'reload schema';

commit;

-- End of reverse migration.
