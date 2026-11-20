-- ============================================================
-- EataLyft — REVERSE: Notifications mirror (Phase 1)
-- Drops the analytics mirror tables. Firestore collections are untouched
-- (authoritative store lives outside Supabase).
-- ============================================================

DROP POLICY IF EXISTS "own notifications read" ON public.notifications_mirror;
DROP POLICY IF EXISTS "own deliveries read" ON public.notification_deliveries_mirror;
DROP TABLE IF EXISTS public.notification_deliveries_mirror;
DROP TABLE IF EXISTS public.notifications_mirror;
