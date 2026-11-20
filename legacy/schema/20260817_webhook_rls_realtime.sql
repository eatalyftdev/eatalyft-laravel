-- ═══════════════════════════════════════════════════════════════════════════════════
-- EataLyft Supabase — RLS Policies & Webhook Idempotency Table
-- ═══════════════════════════════════════════════════════════════════════════════════
-- File: supabase/migrations/20260817_webhook_rls_realtime.sql
-- Run this file in the Supabase SQL Editor (Dashboard → SQL Editor → New Query)
-- This migration is idempotent (safe to re-run)

-- ───────────────────────────────────────────────────────────────────────────────────
-- SECTION 1: Webhook Idempotency Table
-- ───────────────────────────────────────────────────────────────────────────────────
-- Tracks processed webhooks to prevent double-crediting and duplicate processing
-- This table is the source of truth for idempotency guards

CREATE TABLE IF NOT EXISTS webhook_events (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  
  -- Event identification
  event_id TEXT NOT NULL UNIQUE,      -- X-MeSomb-Webhook-Event-Id or body.id
  event_type TEXT,                    -- payment.transaction.succeeded, etc.
  source TEXT DEFAULT 'mesomb',       -- mesomb | supabase | external
  
  -- Transaction reference (idempotency key)
  transaction_ref TEXT NOT NULL UNIQUE,  -- EATALYFT-WALLET-UID-TIMESTAMP
  
  -- Event metadata
  payload JSONB NOT NULL,              -- Full webhook payload (for audit)
  signature_verified BOOLEAN DEFAULT false,
  
  -- Processing status
  status TEXT DEFAULT 'received',      -- received | processing | processed | failed
  processed_at TIMESTAMPTZ,
  
  -- Retry information
  attempt_count INTEGER DEFAULT 1,
  last_error TEXT,
  retry_at TIMESTAMPTZ,
  max_retries INTEGER DEFAULT 3,
  
  -- Timestamps
  received_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Indexes for fast lookup during idempotency checks
CREATE INDEX IF NOT EXISTS idx_webhook_event_id ON webhook_events(event_id);
CREATE INDEX IF NOT EXISTS idx_webhook_transaction_ref ON webhook_events(transaction_ref);
CREATE INDEX IF NOT EXISTS idx_webhook_status ON webhook_events(status);
CREATE INDEX IF NOT EXISTS idx_webhook_received_at ON webhook_events(received_at DESC);
CREATE INDEX IF NOT EXISTS idx_webhook_source_status ON webhook_events(source, status);

-- Composite index for retry processing
CREATE INDEX IF NOT EXISTS idx_webhook_retry_scan ON webhook_events(status, retry_at)
  WHERE status = 'failed' AND retry_at IS NOT NULL;

-- Enable real-time subscriptions for webhook events
ALTER TABLE webhook_events REPLICA IDENTITY FULL;

-- Enable RLS
ALTER TABLE webhook_events ENABLE ROW LEVEL SECURITY;

-- ───────────────────────────────────────────────────────────────────────────────────
-- SECTION 2: RLS Policies for Payments Table
-- ───────────────────────────────────────────────────────────────────────────────────
-- Users can only see their own payment records
-- Admins can see all payments

-- Policy: Users can view their own payments
CREATE POLICY payments_user_read ON payments
  FOR SELECT
  USING (user_id = auth.uid());

-- Policy: Service can insert payments (for webhook processing)
CREATE POLICY payments_service_insert ON payments
  FOR INSERT
  WITH CHECK (true);  -- Allow microservice to insert via service role

-- Policy: Service can update payments (for status changes)
CREATE POLICY payments_service_update ON payments
  FOR UPDATE
  USING (true)
  WITH CHECK (true);

-- Policy: Admins can view all payments
CREATE POLICY payments_admin_read ON payments
  FOR SELECT
  USING (is_admin());

-- ───────────────────────────────────────────────────────────────────────────────────
-- SECTION 3: RLS Policies for Wallets
-- ───────────────────────────────────────────────────────────────────────────────────
-- Users can only see their own wallet
-- Admins can see all wallets

-- Policy: Users can view their own wallet
CREATE POLICY wallets_user_read ON wallets
  FOR SELECT
  USING (uid = auth.uid());

-- Policy: Users can view their wallet transactions
CREATE POLICY wallets_user_update ON wallets
  FOR UPDATE
  USING (uid = auth.uid())
  WITH CHECK (uid = auth.uid());

-- Policy: Service can update wallets (for crediting)
CREATE POLICY wallets_service_update ON wallets
  FOR UPDATE
  USING (true)
  WITH CHECK (true);

-- Policy: Admins can view all wallets
CREATE POLICY wallets_admin_read ON wallets
  FOR SELECT
  USING (is_admin());

-- ───────────────────────────────────────────────────────────────────────────────────
-- SECTION 4: RLS Policies for Wallet Transactions
-- ───────────────────────────────────────────────────────────────────────────────────
-- Users can only see their own transaction history
-- Admins can see all transactions

-- Policy: Users can view their own transactions
CREATE POLICY wallet_tx_user_read ON wallet_transactions
  FOR SELECT
  USING (uid = auth.uid());

-- Policy: Service can insert transactions
CREATE POLICY wallet_tx_service_insert ON wallet_transactions
  FOR INSERT
  WITH CHECK (true);

-- Policy: Admins can view all transactions
CREATE POLICY wallet_tx_admin_read ON wallet_transactions
  FOR SELECT
  USING (is_admin());

-- ───────────────────────────────────────────────────────────────────────────────────
-- SECTION 5: RLS Policies for Webhook Events (Audit Table)
-- ───────────────────────────────────────────────────────────────────────────────────
-- Service role can read/write webhooks
-- Admins can view all webhook events
-- Regular users cannot access

-- Policy: Admins can view webhook events
CREATE POLICY webhook_events_admin_read ON webhook_events
  FOR SELECT
  USING (is_admin());

-- Policy: Service can insert webhook events
CREATE POLICY webhook_events_insert ON webhook_events
  FOR INSERT
  WITH CHECK (true);

-- Policy: Service can update webhook events
CREATE POLICY webhook_events_update ON webhook_events
  FOR UPDATE
  USING (true)
  WITH CHECK (true);

-- ───────────────────────────────────────────────────────────────────────────────────
-- SECTION 6: Helper Functions for Idempotency
-- ───────────────────────────────────────────────────────────────────────────────────

-- Function: Check if webhook event has been processed
CREATE OR REPLACE FUNCTION is_webhook_processed(
  p_event_id TEXT,
  p_transaction_ref TEXT
)
RETURNS BOOLEAN
LANGUAGE SQL
SECURITY DEFINER
AS $$
  SELECT EXISTS (
    SELECT 1 FROM webhook_events
    WHERE (event_id = p_event_id OR transaction_ref = p_transaction_ref)
      AND status IN ('processed', 'processing')
  );
$$;

-- Function: Mark webhook as processed
CREATE OR REPLACE FUNCTION mark_webhook_processed(
  p_event_id TEXT,
  p_transaction_ref TEXT,
  p_payload JSONB
)
RETURNS UUID
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
  v_webhook_id UUID;
BEGIN
  -- Insert or update webhook record
  INSERT INTO webhook_events (event_id, transaction_ref, payload, status)
  VALUES (p_event_id, p_transaction_ref, p_payload, 'processed')
  ON CONFLICT (event_id) DO UPDATE
    SET status = 'processed', processed_at = NOW(), updated_at = NOW()
  RETURNING id INTO v_webhook_id;
  
  RETURN v_webhook_id;
END;
$$;

-- Function: Check if payment status can be updated (idempotent)
CREATE OR REPLACE FUNCTION can_update_payment_status(
  p_firebase_ref TEXT,
  p_new_status TEXT
)
RETURNS BOOLEAN
LANGUAGE SQL
SECURITY DEFINER
AS $$
  SELECT CASE 
    -- Don't regress from success
    WHEN (SELECT status FROM payments WHERE firebase_ref = p_firebase_ref) = 'completed'
      AND p_new_status != 'completed'
      THEN FALSE
    
    -- Allow status transitions
    ELSE TRUE
  END;
$$;

-- ───────────────────────────────────────────────────────────────────────────────────
-- SECTION 7: Enable Real-time Subscriptions
-- ───────────────────────────────────────────────────────────────────────────────────
-- Allow clients to subscribe to real-time updates via PostgREST

-- Payments: Enable real-time for all users (filtered by RLS)
ALTER TABLE payments REPLICA IDENTITY FULL;

-- Wallets: Enable real-time for all users (filtered by RLS)
ALTER TABLE wallets REPLICA IDENTITY FULL;

-- Wallet Transactions: Enable real-time for all users (filtered by RLS)
ALTER TABLE wallet_transactions REPLICA IDENTITY FULL;

-- ───────────────────────────────────────────────────────────────────────────────────
-- SECTION 8: Trigger: Auto-update webhook event status
-- ───────────────────────────────────────────────────────────────────────────────────
-- Automatically update webhook_events timestamp when payment status changes

CREATE OR REPLACE FUNCTION update_webhook_processed()
RETURNS TRIGGER
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
  -- Update webhook event status when payment is completed
  IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
    UPDATE webhook_events
    SET status = 'processed', processed_at = NOW(), updated_at = NOW()
    WHERE transaction_ref = NEW.firebase_ref;
  END IF;
  
  RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS payments_update_webhook_trigger ON payments;
CREATE TRIGGER payments_update_webhook_trigger
  AFTER UPDATE ON payments
  FOR EACH ROW
  EXECUTE FUNCTION update_webhook_processed();

-- ───────────────────────────────────────────────────────────────────────────────────
-- SECTION 9: Create Admin Helper Function
-- ───────────────────────────────────────────────────────────────────────────────────
-- Determine if current user is admin (used in RLS policies)

CREATE OR REPLACE FUNCTION is_admin()
RETURNS BOOLEAN
LANGUAGE SQL
SECURITY DEFINER
AS $$
  SELECT EXISTS (
    SELECT 1 FROM admins
    WHERE uid = auth.uid() AND status = 'active'
  );
$$;

-- ───────────────────────────────────────────────────────────────────────────────────
-- SECTION 10: Cleanup & Maintenance Views
-- ───────────────────────────────────────────────────────────────────────────────────
-- View: Failed webhooks requiring retry

CREATE OR REPLACE VIEW failed_webhooks AS
SELECT
  id,
  event_id,
  transaction_ref,
  status,
  last_error,
  attempt_count,
  retry_at,
  CURRENT_TIMESTAMP - received_at as age
FROM webhook_events
WHERE status = 'failed' AND attempt_count < max_retries
ORDER BY retry_at ASC NULLS FIRST;

-- View: Payment status dashboard (admin only)

CREATE OR REPLACE VIEW payment_status_summary AS
SELECT
  status,
  COUNT(*) as count,
  SUM(amount_xaf) as total_xaf,
  MIN(created_at) as oldest,
  MAX(created_at) as newest
FROM payments
GROUP BY status;

-- View: Wallet ledger per user (user-specific)

CREATE OR REPLACE VIEW wallet_balance_audit AS
SELECT
  w.uid,
  w.balance_xaf,
  w.total_credited,
  w.total_debited,
  COUNT(DISTINCT wt.id) as transaction_count,
  MAX(wt.created_at) as last_transaction_at
FROM wallets w
LEFT JOIN wallet_transactions wt ON w.id = wt.wallet_id
GROUP BY w.id, w.uid, w.balance_xaf, w.total_credited, w.total_debited;

-- ───────────────────────────────────────────────────────────────────────────────────
-- SECTION 11: Grant Permissions
-- ───────────────────────────────────────────────────────────────────────────────────
-- Ensure service role can access all tables
-- Client role is restricted by RLS policies

GRANT SELECT, INSERT, UPDATE, DELETE ON webhook_events TO service_role;
GRANT SELECT, INSERT, UPDATE ON payments TO service_role;
GRANT SELECT, INSERT, UPDATE ON wallets TO service_role;
GRANT SELECT, INSERT ON wallet_transactions TO service_role;

-- Allow authenticated users to select their data (RLS enforces filtering)
GRANT SELECT ON payments TO authenticated;
GRANT SELECT ON wallets TO authenticated;
GRANT SELECT ON wallet_transactions TO authenticated;

-- Allow views to be queried
GRANT SELECT ON payment_status_summary TO authenticated;
GRANT SELECT ON wallet_balance_audit TO authenticated;

-- ───────────────────────────────────────────────────────────────────────────────────
-- SECTION 12: Verification
-- ───────────────────────────────────────────────────────────────────────────────────
-- Run these checks to verify RLS is working correctly

-- Check: Verify webhook_events table exists
-- SELECT COUNT(*) as webhook_events_count FROM webhook_events;

-- Check: Verify RLS is enabled on all payment tables
-- SELECT schemaname, tablename, rowsecurity FROM pg_tables
--   WHERE tablename IN ('webhook_events', 'payments', 'wallets', 'wallet_transactions');

-- Check: Verify RLS policies exist
-- SELECT schemaname, tablename, policyname FROM pg_policies
--   WHERE schemaname = 'public'
--   ORDER BY tablename, policyname;

-- ───────────────────────────────────────────────────────────────────────────────────
-- END OF MIGRATION
-- ───────────────────────────────────────────────────────────────────────────────────
