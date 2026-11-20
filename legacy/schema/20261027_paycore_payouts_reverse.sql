-- [FEATURE] PayCore payouts layer — REVERSE
-- [CHANGES] drops payouts table, RLS policies
--
-- Rollback: drops exactly this feature's tables; payout records are removed;
-- wallet balances survive the rollback (recalculated from ledger if needed).
-- Audit log intact (preserved as part of financial history).
--
begin;

drop table if exists public.payouts;

notify pgrst, 'reload schema';

commit;

-- End of reverse migration.