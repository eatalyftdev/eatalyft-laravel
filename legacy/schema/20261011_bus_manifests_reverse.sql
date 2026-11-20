-- RUN C rollback — Bus trip manifests (staging/local ONLY until approved).
-- Rollback: psql "$STAGING_DB_URL" -f supabase/migrations/20261011_bus_manifests_reverse.sql
-- Drops policies/helpers/tables created by 20261011_bus_manifests.sql.
-- The storage bucket row is removed; stored PDFs under it go with the
-- bucket delete (Supabase removes objects when the bucket is deleted).

DROP POLICY IF EXISTS bus_manifest_items_client_read ON public.bus_manifest_items;
DROP POLICY IF EXISTS bus_manifests_client_read ON public.bus_manifests;
DROP FUNCTION IF EXISTS public.manifest_can_read(TEXT, UUID);

DROP TABLE IF EXISTS public.bus_manifest_downloads;
DROP TABLE IF EXISTS public.bus_manifest_items;
DROP TABLE IF EXISTS public.bus_manifests;

DELETE FROM storage.buckets WHERE id = 'trip-manifests';
