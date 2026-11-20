-- Reverse for 20260920_listings.sql
DROP POLICY IF EXISTS eal_listing_photos_select ON storage.objects;
DELETE FROM storage.buckets WHERE id = 'listing-photos';
DROP TABLE IF EXISTS public.listing_viewings;
DROP TABLE IF EXISTS public.listing_reports;
DROP TABLE IF EXISTS public.listing_inquiries;
DROP TABLE IF EXISTS public.listings;
DELETE FROM public.app_settings WHERE key = 'realtor_live_limits';
-- NOTE: postgis/pg_trgm extensions intentionally left installed.
