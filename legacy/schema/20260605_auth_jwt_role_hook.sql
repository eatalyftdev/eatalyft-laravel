-- ============================================================
-- EataLyft — Custom JWT Access Token Hook
-- Migration: 20260605_auth_jwt_role_hook
--
-- Run this in Supabase SQL Editor (project: mwrqparialiqdfgxiave)
-- Then register the hook in:
--   Dashboard → Authentication → Hooks → Custom Access Token
--   Schema: public  |  Function: custom_access_token_hook
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- STEP 1A: Hook function — injects role/status into every JWT
-- ─────────────────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION public.custom_access_token_hook(event jsonb)
RETURNS jsonb
LANGUAGE plpgsql
STABLE
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  claims         jsonb;
  user_profile   record;
BEGIN
  SELECT role, status, must_change_password
  INTO   user_profile
  FROM   public.profiles
  WHERE  id = (event->>'user_id')::uuid;

  claims := event->'claims';

  IF user_profile IS NOT NULL THEN
    claims := jsonb_set(
      claims,
      '{app_metadata}',
      COALESCE(claims->'app_metadata', '{}') ||
      jsonb_build_object(
        'role',                  user_profile.role,
        'status',                user_profile.status,
        'must_change_password',  COALESCE(user_profile.must_change_password, false)
      )
    );
  END IF;

  RETURN jsonb_set(event, '{claims}', claims);
END;
$$;

GRANT EXECUTE ON FUNCTION public.custom_access_token_hook TO supabase_auth_admin;
REVOKE EXECUTE ON FUNCTION public.custom_access_token_hook FROM PUBLIC, anon, authenticated;


-- ─────────────────────────────────────────────────────────────
-- STEP 1B: Backfill raw_app_meta_data for all 21 existing users
-- ─────────────────────────────────────────────────────────────
UPDATE auth.users u
SET raw_app_meta_data =
  COALESCE(u.raw_app_meta_data, '{}') ||
  jsonb_build_object(
    'role',                 p.role,
    'status',               p.status,
    'must_change_password', COALESCE(p.must_change_password, false)
  )
FROM public.profiles p
WHERE p.id = u.id;


-- ─────────────────────────────────────────────────────────────
-- STEP 1C: Trigger — auto-sync when admin changes role/status
-- ─────────────────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION public.sync_user_role_to_auth()
RETURNS TRIGGER
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
BEGIN
  IF (OLD.role IS DISTINCT FROM NEW.role)
  OR (OLD.status IS DISTINCT FROM NEW.status)
  OR (OLD.must_change_password IS DISTINCT FROM NEW.must_change_password)
  THEN
    UPDATE auth.users
    SET raw_app_meta_data =
      COALESCE(raw_app_meta_data, '{}') ||
      jsonb_build_object(
        'role',                 NEW.role,
        'status',               NEW.status,
        'must_change_password', COALESCE(NEW.must_change_password, false)
      )
    WHERE id = NEW.id;
  END IF;

  RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_sync_role_to_auth ON public.profiles;

CREATE TRIGGER trg_sync_role_to_auth
  AFTER UPDATE ON public.profiles
  FOR EACH ROW
  EXECUTE FUNCTION public.sync_user_role_to_auth();
