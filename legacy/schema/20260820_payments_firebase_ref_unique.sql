-- Ensure payment mirror upserts can conflict on the stable Firebase reference.
-- The live EataPay Supabase table had firebase_ref but no unique constraint,
-- which caused PostgREST to reject onConflict=firebase_ref.
ALTER TABLE public.payments
  ADD CONSTRAINT payments_firebase_ref_key UNIQUE (firebase_ref);

COMMENT ON CONSTRAINT payments_firebase_ref_key ON public.payments IS
  'Stable idempotency key used by the EataLyft Firestore-to-Supabase payment mirror';

NOTIFY pgrst, 'reload schema';

-- End of migration.

