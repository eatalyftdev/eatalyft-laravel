-- 20261010_user_sessions.sql
-- Per-device session registry for multi-device login.
--
-- One row per successful POST /api/auth/session. Sessions are independent:
-- creating a session on a new device NEVER revokes existing rows, so signing in
-- on a second device does not sign the first one out. Revocation is explicit
-- (single device) or global ("log out everywhere", which additionally calls
-- Firebase revokeRefreshTokens).
--
-- The session JWT itself stays stateless (HS256, verified at the edge). This
-- table is the audit + revocation surface, not the auth mechanism.

CREATE TABLE IF NOT EXISTS public.user_sessions (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    profile_id      UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    -- Opaque, non-secret identifier for this session (JWT jti). The JWT itself
    -- is never stored, so a DB leak cannot be replayed as a cookie.
    session_id      TEXT NOT NULL,
    device_label    TEXT NOT NULL DEFAULT 'Unknown device',
    user_agent      TEXT,
    ip_address      INET,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    last_seen_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    revoked_at      TIMESTAMPTZ,
    revoked_reason  TEXT,
    CONSTRAINT user_sessions_session_id_key UNIQUE (session_id),
    CONSTRAINT user_sessions_revoked_reason_chk
        CHECK (revoked_reason IS NULL OR revoked_reason IN
            ('logout', 'revoked_by_user', 'logout_everywhere', 'expired', 'admin'))
);

-- Hot path: "list this profile's live devices", newest first.
CREATE INDEX IF NOT EXISTS user_sessions_profile_active_idx
    ON public.user_sessions (profile_id, created_at DESC)
    WHERE revoked_at IS NULL;

-- Same profile_id as the FK target so revocation cascades cleanly.
CREATE INDEX IF NOT EXISTS user_sessions_profile_idx
    ON public.user_sessions (profile_id);

ALTER TABLE public.user_sessions ENABLE ROW LEVEL SECURITY;

-- A profile may only ever see and manage its own sessions.
DROP POLICY IF EXISTS user_sessions_select_own ON public.user_sessions;
CREATE POLICY user_sessions_select_own ON public.user_sessions
    FOR SELECT
    USING (auth.uid() = profile_id);

DROP POLICY IF EXISTS user_sessions_insert_own ON public.user_sessions;
CREATE POLICY user_sessions_insert_own ON public.user_sessions
    FOR INSERT
    WITH CHECK (auth.uid() = profile_id);

-- Revocation is an UPDATE of revoked_at only; ownership still required.
DROP POLICY IF EXISTS user_sessions_update_own ON public.user_sessions;
CREATE POLICY user_sessions_update_own ON public.user_sessions
    FOR UPDATE
    USING (auth.uid() = profile_id)
    WITH CHECK (auth.uid() = profile_id);

-- Deliberately no DELETE policy: rows are revoked, never hard-deleted, so the
-- security history survives. Service role bypasses RLS for retention jobs.

COMMENT ON TABLE public.user_sessions IS
    'Per-device session registry. Rows are revoked (revoked_at), never deleted.';
COMMENT ON COLUMN public.user_sessions.session_id IS
    'Opaque non-secret session identifier (JWT jti). Never the signed JWT.';