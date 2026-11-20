-- ============================================================================
-- REVERSE of supabase/migrations/20260820_dashboard_media_storage.sql
-- ============================================================================
-- Removes what the forward added:
--   * the 'media' storage bucket (and any objects left inside it)
--   * table public.media_assets (+ its 2 indexes, RLS, and 2 policies)
--   * the 2 storage.objects policies tied to bucket_id = 'media'
-- WARNING: this deletes uploaded media objects in the bucket as part of the
-- reverse. Splitting the reverse so bucket/objects deletion is manual/optional
-- is a deliberate choice: a schema-only rollback should NOT destroy stored
-- user media automatically.
-- ============================================================================

begin;

drop policy if exists media_assets_public_read  on public.media_assets;
drop policy if exists media_assets_service_write on public.media_assets;

drop table if exists public.media_assets;

drop policy if exists media_bucket_public_read  on storage.objects;
drop policy if exists media_bucket_service_write on storage.objects;

-- Optional: delete stored objects then the bucket row itself.
-- delete from storage.objects where bucket_id = 'media';
-- delete from storage.buckets where id = 'media';

commit;