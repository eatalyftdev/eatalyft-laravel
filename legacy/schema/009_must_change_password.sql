-- Migration 009: Add must_change_password and last_seen_at to profiles
-- Safe to run multiple times (IF NOT EXISTS guards)

ALTER TABLE public.profiles
  ADD COLUMN IF NOT EXISTS must_change_password BOOLEAN NOT NULL DEFAULT FALSE;

ALTER TABLE public.profiles
  ADD COLUMN IF NOT EXISTS last_seen_at TIMESTAMPTZ;

-- Ensure the default is explicitly FALSE so all existing and self-registered
-- users (driver, agent, handyman, nanny, maid, hotel, customer) are never
-- shown the forced change-password screen.
ALTER TABLE public.profiles
  ALTER COLUMN must_change_password SET DEFAULT FALSE;

COMMENT ON COLUMN public.profiles.must_change_password IS
  'When TRUE the user is forced to /admin/change-password before accessing any dashboard. Only set by admin seed or admin-created accounts.';

COMMENT ON COLUMN public.profiles.last_seen_at IS
  'Timestamp of the user''s last authenticated session — updated on login.';
