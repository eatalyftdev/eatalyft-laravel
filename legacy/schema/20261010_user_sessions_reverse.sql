-- 20261010_user_sessions_reverse.sql
-- Reverses 20261010_user_sessions.sql.
--
-- DROP TABLE removes the policies with it, so they are not dropped separately.

DROP TABLE IF EXISTS public.user_sessions;