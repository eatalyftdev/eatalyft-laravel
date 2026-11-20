-- Migration tool: maid extension + bookings + generic migrated table helper (optional DDL)

CREATE TABLE IF NOT EXISTS public.maid_profiles (
  id             UUID PRIMARY KEY REFERENCES public.profiles(id) ON DELETE CASCADE,
  service_type   TEXT DEFAULT 'cleaning',
  experience_yrs INT DEFAULT 0,
  languages      TEXT[],
  rate_per_hour  NUMERIC(10,2) DEFAULT 0,
  address        TEXT,
  latitude       NUMERIC(10,6),
  longitude      NUMERIC(10,6),
  is_available   BOOLEAN DEFAULT TRUE,
  rating         NUMERIC(3,2) DEFAULT 0,
  total_jobs     INT DEFAULT 0,
  is_verified    BOOLEAN DEFAULT FALSE,
  account_status TEXT DEFAULT 'pending_approval'
);

ALTER TABLE public.maid_profiles ENABLE ROW LEVEL SECURITY;

DO $$ DECLARE t TEXT := 'maid_profiles'; BEGIN
  EXECUTE format(
    'CREATE POLICY "own or admin: select" ON public.%I FOR SELECT USING (id = auth.uid() OR is_admin());
     CREATE POLICY "own or admin: insert" ON public.%I FOR INSERT WITH CHECK (id = auth.uid() OR is_admin());
     CREATE POLICY "own or admin: update" ON public.%I FOR UPDATE USING (id = auth.uid() OR is_admin());
     CREATE POLICY "admin delete"         ON public.%I FOR DELETE USING (is_admin());',
    t, t, t, t
  );
END $$;

CREATE TABLE IF NOT EXISTS public.bookings (
  id          TEXT PRIMARY KEY,
  user_id     UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
  data        JSONB NOT NULL DEFAULT '{}',
  migrated_at TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_bookings_user ON public.bookings(user_id);

ALTER TABLE public.bookings ENABLE ROW LEVEL SECURITY;

CREATE POLICY "bookings: owner or admin select"
  ON public.bookings FOR SELECT
  USING (user_id IS NULL OR user_id = auth.uid() OR is_admin());

CREATE POLICY "bookings: admin insert"
  ON public.bookings FOR INSERT
  WITH CHECK (is_admin());

CREATE POLICY "bookings: admin update"
  ON public.bookings FOR UPDATE
  USING (is_admin())
  WITH CHECK (is_admin());

CREATE POLICY "bookings: admin delete"
  ON public.bookings FOR DELETE
  USING (is_admin());
