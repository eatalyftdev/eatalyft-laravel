-- Reverse for 20260920_hotels.sql
DROP FUNCTION IF EXISTS public.hold_hotel_inventory(TEXT, UUID, DATE, DATE, INTEGER, INTEGER);
DROP TABLE IF EXISTS public.manager_payouts;
DROP TABLE IF EXISTS public.hotel_reviews;
DROP TABLE IF EXISTS public.hotel_cancellation_policies;
DROP TABLE IF EXISTS public.booking_payments;
DROP TABLE IF EXISTS public.hotel_bookings;
DROP TABLE IF EXISTS public.booking_holds;
DROP TABLE IF EXISTS public.hotel_availability;
DROP TABLE IF EXISTS public.hotel_rate_plans;
DROP TABLE IF EXISTS public.hotel_room_types;
DROP TABLE IF EXISTS public.hotel_properties;
DELETE FROM public.app_settings WHERE key = 'hotel_commission_pct';
