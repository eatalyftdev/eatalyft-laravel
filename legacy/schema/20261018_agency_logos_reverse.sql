-- Reverse of 20261018_agency_logos.sql

DROP POLICY IF EXISTS agency_logos_public_read ON storage.objects;
DROP POLICY IF EXISTS agency_logos_no_client_write ON storage.objects;

DELETE FROM storage.buckets WHERE id = 'agency-logos';
