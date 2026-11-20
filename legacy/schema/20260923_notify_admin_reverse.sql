-- ============================================================
-- EataLyft — REVERSE: notification sounds bucket (Phase 6)
-- ============================================================

DROP POLICY IF EXISTS eal_notify_sounds_select ON storage.objects;
DELETE FROM storage.buckets WHERE id = 'notification-sounds';
