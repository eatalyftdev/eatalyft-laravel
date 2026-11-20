-- ============================================================
-- EataLyft — REVERSE: Listings Phase 4
-- Drops review/reply/save/search/contact tables + added columns.
-- Core listings tables untouched.
-- ============================================================

ALTER TABLE public.listing_reports DROP COLUMN IF EXISTS review_id;
ALTER TABLE public.listings DROP COLUMN IF EXISTS rating_avg;
ALTER TABLE public.listings DROP COLUMN IF EXISTS rating_count;

DROP TABLE IF EXISTS public.listing_contact_events;
DROP TABLE IF EXISTS public.saved_searches;
DROP TABLE IF EXISTS public.listing_saves;
DROP TABLE IF EXISTS public.listing_inquiry_replies;
DROP TABLE IF EXISTS public.listing_reviews;

DELETE FROM public.app_settings WHERE key IN ('listing_photo_cap', 'contact_countries_disabled');
