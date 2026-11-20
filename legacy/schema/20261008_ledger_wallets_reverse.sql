-- [FEATURE] Double-entry ledger, secure transfers, withdrawals — REVERSE
-- [CHANGES] drops flag_ledger_mismatches/wallet_transfer/guard functions, ledger_reconciliation_flags/withdrawal_events/withdrawals/transfer_idempotency/ledger_entries/ledger_accounts tables
--
-- Rollback note: DESTRUCTIVE to financial history — ledger rows vanish. Only
-- run before go-live or after exporting ledger_entries. wallets balances are
-- NOT rolled back (mirror column survives). After rollback, transfers fall
-- back to the Firestore path until the forward is re-applied.

begin;

drop function if exists public.flag_ledger_mismatches();
drop function if exists public.wallet_transfer(uuid, uuid, bigint, text, text);
drop function if exists public.guard_withdrawal_transition();
drop function if exists public.forbid_ledger_mutation();
drop function if exists public.enforce_balanced_transaction();

drop table if exists public.ledger_reconciliation_flags;
drop table if exists public.withdrawal_events;
drop table if exists public.withdrawals;
drop table if exists public.transfer_idempotency;
drop table if exists public.ledger_entries;
drop table if exists public.ledger_accounts;

notify pgrst, 'reload schema';

commit;

-- End of reverse migration.
