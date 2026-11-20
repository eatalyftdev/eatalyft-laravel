-- [FEATURE] PayCore payment intents layer — REVERSE
-- [CHANGES] drops payment_intents table, payment_intents_status enum, indexes, RLS policies
--
-- Rollback: drops exactly this feature's tables; Firestore transactions/{ref}
-- remain the canonical payment ledger. After rollback, payment flow falls back
-- to the payment_attempts/gateway_webhook_events operational mirrors.

begin;

drop table if exists public.payment_intents;
drop type if exists public.payment_intents_status;

notify pgrst, 'reload schema';

commit;

-- End of reverse migration.