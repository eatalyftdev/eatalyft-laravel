-- ============================================================
-- EataLyft Horizon — Supabase Migration 003: Wallet RPC
-- Atomic, race-condition-safe balance operations.
-- Called exclusively by the wallet-operation Edge Function
-- which runs with the service-role key (bypasses RLS).
-- ============================================================

CREATE OR REPLACE FUNCTION public.update_wallet_balance(
  p_user_id    UUID,
  p_amount     NUMERIC,
  p_type       TEXT,           -- 'credit' | 'debit'
  p_description TEXT,
  p_reference  TEXT DEFAULT NULL,
  p_service    TEXT DEFAULT NULL
)
RETURNS JSONB
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
  v_wallet_id UUID;
  v_balance   NUMERIC;
  v_points    NUMERIC;
BEGIN
  -- Lock the wallet row to prevent concurrent race conditions
  SELECT id, balance, points
    INTO v_wallet_id, v_balance, v_points
    FROM public.wallets
   WHERE user_id = p_user_id
     FOR UPDATE;

  IF NOT FOUND THEN
    RAISE EXCEPTION 'Wallet not found for user %', p_user_id;
  END IF;

  -- Guard: reject debits that would overdraw the wallet
  IF p_type = 'debit' AND v_balance < p_amount THEN
    RAISE EXCEPTION 'Insufficient balance: have %, need %', v_balance, p_amount;
  END IF;

  -- Apply the balance delta
  UPDATE public.wallets
     SET balance = balance + CASE WHEN p_type = 'credit' THEN p_amount ELSE -p_amount END
   WHERE id = v_wallet_id
  RETURNING balance INTO v_balance;

  -- Record the transaction
  INSERT INTO public.wallet_transactions(wallet_id, user_id, amount, type, description, reference, service)
  VALUES (v_wallet_id, p_user_id, p_amount, p_type::tx_type, p_description, p_reference, p_service);

  RETURN jsonb_build_object(
    'success',     true,
    'new_balance', v_balance,
    'wallet_id',   v_wallet_id
  );
END;
$$;

-- ── Points / Stars operations (separate from XAF balance) ──

CREATE OR REPLACE FUNCTION public.credit_points(
  p_user_id UUID,
  p_points  NUMERIC,
  p_reason  TEXT DEFAULT NULL
)
RETURNS JSONB
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
  v_new_points NUMERIC;
BEGIN
  UPDATE public.wallets
     SET points = points + p_points
   WHERE user_id = p_user_id
  RETURNING points INTO v_new_points;

  IF NOT FOUND THEN
    RAISE EXCEPTION 'Wallet not found for user %', p_user_id;
  END IF;

  RETURN jsonb_build_object('success', true, 'new_points', v_new_points);
END;
$$;

CREATE OR REPLACE FUNCTION public.redeem_points(
  p_user_id       UUID,
  p_points        NUMERIC,
  p_xaf_per_point NUMERIC DEFAULT 0.01  -- 100 stars = 1 XAF by default
)
RETURNS JSONB
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
  v_wallet_id   UUID;
  v_points      NUMERIC;
  v_credit_xaf  NUMERIC;
  v_new_points  NUMERIC;
  v_new_balance NUMERIC;
BEGIN
  SELECT id, points INTO v_wallet_id, v_points
    FROM public.wallets WHERE user_id = p_user_id FOR UPDATE;

  IF NOT FOUND THEN RAISE EXCEPTION 'Wallet not found'; END IF;
  IF v_points < p_points THEN RAISE EXCEPTION 'Insufficient stars: have %, need %', v_points, p_points; END IF;

  v_credit_xaf := FLOOR(p_points * p_xaf_per_point);
  IF v_credit_xaf <= 0 THEN RAISE EXCEPTION 'Stars amount too small to redeem'; END IF;

  UPDATE public.wallets
     SET points  = points  - p_points,
         balance = balance + v_credit_xaf
   WHERE id = v_wallet_id
  RETURNING points, balance INTO v_new_points, v_new_balance;

  INSERT INTO public.wallet_transactions(wallet_id, user_id, amount, type, description, service)
  VALUES (v_wallet_id, p_user_id, v_credit_xaf, 'credit', format('Redeemed %s Stars for %s XAF', p_points, v_credit_xaf), 'loyalty');

  RETURN jsonb_build_object(
    'success',      true,
    'points_used',  p_points,
    'xaf_credited', v_credit_xaf,
    'new_points',   v_new_points,
    'new_balance',  v_new_balance
  );
END;
$$;

-- ── Admin wallet adjustment (mirrors adminAdjustWalletAction) ──
CREATE OR REPLACE FUNCTION public.admin_adjust_wallet(
  p_admin_id  UUID,
  p_user_id   UUID,
  p_balance   NUMERIC DEFAULT NULL,
  p_points    NUMERIC DEFAULT NULL
)
RETURNS JSONB
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM public.profiles WHERE id = p_admin_id AND role IN ('admin','superadmin')) THEN
    RAISE EXCEPTION 'Caller is not an admin';
  END IF;

  UPDATE public.wallets
     SET balance = COALESCE(p_balance, balance),
         points  = COALESCE(p_points, points)
   WHERE user_id = p_user_id;

  IF NOT FOUND THEN RAISE EXCEPTION 'Wallet not found for user %', p_user_id; END IF;

  RETURN jsonb_build_object('success', true);
END;
$$;
