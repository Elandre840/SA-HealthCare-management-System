"""
Password hashing and JWT token utilities.

Password security
-----------------
bcrypt is used for hashing because it is intentionally slow, making offline
brute-force attacks impractical even if the database is leaked.

JWT strategy
------------
The system issues two token types signed with the same HMAC-SHA256 secret:

  access  — short-lived (default 60 min), sent as Authorization: Bearer on
             every protected API request.
  refresh — longer-lived (default 7 days), used once to obtain a new access
             token when it expires, then discarded by the client.

A custom "typ" claim in the JWT payload prevents a client from accidentally (or
maliciously) using a refresh token in place of an access token on a protected
endpoint.

Refresh tokens also carry a unique "jti" (JWT ID). That ID is stored in Redis
(or an in-memory store during tests) so tokens can be rotated and revoked on
logout — a stolen refresh token cannot be replayed after rotation or logout.
"""

from datetime import datetime, timedelta, timezone
from uuid import uuid4

import bcrypt
from jose import JWTError, jwt

from app.core.config import settings


ALGORITHM = "HS256"


# bcrypt is intentionally slow (cost factor 12 by default) to make offline
# brute-force attacks expensive. gensalt() embeds both the cost factor and a
# random salt in the returned hash string, so verify only needs the plain text
# and the stored string — no separate salt column required.
def hash_password(password: str) -> str:
    return bcrypt.hashpw(password.encode(), bcrypt.gensalt()).decode()


def verify_password(plain_password: str, hashed_password: str) -> bool:
    return bcrypt.checkpw(plain_password.encode(), hashed_password.encode())


def create_token(
    subject: str,
    expires_delta: timedelta,
    token_type: str,
    *,
    jti: str | None = None,
) -> str:
    # "typ" is a custom claim that distinguishes access tokens from refresh tokens.
    # Both are signed with the same key, so without this check a client could
    # send a refresh token to a protected endpoint and get a 200 instead of 401.
    expires_at = datetime.now(timezone.utc) + expires_delta
    payload: dict = {"sub": subject, "exp": expires_at, "typ": token_type}
    if jti is not None:
        payload["jti"] = jti
    return jwt.encode(payload, settings.secret_key, algorithm=ALGORITHM)


def create_access_token(subject: str) -> str:
    return create_token(
        subject=subject,
        expires_delta=timedelta(minutes=settings.access_token_expire_minutes),
        token_type="access",
    )


def create_refresh_token(subject: str) -> tuple[str, str]:
    """Return (token, jti). Callers must persist the jti via the token store."""
    jti = str(uuid4())
    token = create_token(
        subject=subject,
        expires_delta=timedelta(days=settings.refresh_token_expire_days),
        token_type="refresh",
        jti=jti,
    )
    return token, jti


def refresh_token_ttl_seconds() -> int:
    return settings.refresh_token_expire_days * 24 * 60 * 60


def decode_token(token: str | None, expected_type: str = "access") -> str | None:
    if not token:
        return None
    try:
        payload = jwt.decode(token, settings.secret_key, algorithms=[ALGORITHM])
    except JWTError:
        return None

    subject = payload.get("sub")
    token_type = payload.get("typ")

    if not isinstance(subject, str):
        return None

    if expected_type == "access" and token_type in (None, "access"):
        return subject

    if token_type == expected_type:
        return subject

    return None


def decode_refresh_token(token: str | None) -> tuple[str, str] | None:
    """Return (subject, jti) for a valid refresh token, or None."""
    if not token:
        return None
    try:
        payload = jwt.decode(token, settings.secret_key, algorithms=[ALGORITHM])
    except JWTError:
        return None

    if payload.get("typ") != "refresh":
        return None

    subject = payload.get("sub")
    jti = payload.get("jti")
    if not isinstance(subject, str) or not isinstance(jti, str) or not jti:
        return None

    return subject, jti
