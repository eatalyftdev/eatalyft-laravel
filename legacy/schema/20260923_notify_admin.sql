-- ============================================================
-- EataLyft — Supabase Migration: notification sounds bucket (Phase 6)
-- Public read (clients stream alert sounds), service-role writes only.
-- Reversible — see 20260923_notify_admin_reverse.sql.
-- Run: npx supabase db push
-- ============================================================

INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES ('notification-sounds','notification-sounds',true,524288,ARRAY['audio/mpeg','audio/wav','audio/ogg'])
ON CONFLICT (id) DO UPDATE
  SET public = EXCLUDED.public,
      file_size_limit = EXCLUDED.file_size_limit,
      allowed_mime_types = EXCLUDED.allowed_mime_types;

DROP POLICY IF EXISTS eal_notify_sounds_select ON storage.objects;
CREATE POLICY eal_notify_sounds_select ON storage.objects
  FOR SELECT USING (bucket_id = 'notification-sounds');
