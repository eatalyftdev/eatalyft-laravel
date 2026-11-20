-- EATALYFT — Public agency logo bucket (ITEM 2). Staging/local until approved.
-- Never applied to production without explicit approval.
--
-- The manifest header renders the bus company's logo. Uploads are validated
-- and re-encoded by the server (`/api/agency/logo`) with the service role, so
-- clients never write to this bucket directly. The bucket is public-read so
-- the resulting image can be embedded in the on-screen header and fetched by
-- the PDF route; only the server (service role) can write.
--
-- Apply (staging):  psql "$STAGING_DB_URL" -f supabase/migrations/20261018_agency_logos.sql
-- Rollback:         psql "$STAGING_DB_URL" -f supabase/migrations/20261018_agency_logos_reverse.sql

-- ── Bucket: public read, server-only write ───────────────────────────────────

INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES ('agency-logos', 'agency-logos', true, 1048576, ARRAY['image/png', 'image/jpeg'])
ON CONFLICT (id) DO UPDATE
  SET public = true,
      file_size_limit = 1048576,
      allowed_mime_types = ARRAY['image/png', 'image/jpeg'];

-- ── Storage RLS: public read of the stored image; no client writes ────────────
-- Writes happen only through the service role (which bypasses RLS) after the
-- server checks that the caller is that agency's manager. Clients get no
-- INSERT/UPDATE/DELETE policy, so a direct upload is denied.

DROP POLICY IF EXISTS agency_logos_public_read ON storage.objects;
CREATE POLICY agency_logos_public_read ON storage.objects
  FOR SELECT TO anon, authenticated
  USING (bucket_id = 'agency-logos');

-- Defence in depth: even if a broader write policy existed elsewhere, this
-- keeps client writes off this bucket.
DROP POLICY IF EXISTS agency_logos_no_client_write ON storage.objects;
CREATE POLICY agency_logos_no_client_write ON storage.objects
  FOR INSERT TO anon, authenticated
  WITH CHECK (bucket_id <> 'agency-logos');
