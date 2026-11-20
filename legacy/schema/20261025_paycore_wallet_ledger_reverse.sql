-- [FEATURE] PayCore wallet ledger layer — REVERSE
-- [CHANGES] drops wallet_ledger table, RLS policies
--
-- Rollback: drops exactly this feature's tables; wallet balances survive the
-- rollback (recalculated from ledger if needed). Audit log intact.

begin;

drop table if exists public.wallet_ledger;

notify pgrst, 'reload schema';

commit;

-- End of reverse migration.