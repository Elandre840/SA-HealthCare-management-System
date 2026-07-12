"""
Authentication routes — /api/v1/auth/*

Endpoints
---------
POST /register  — create a staff account (admin only; returns the user, not tokens)
POST /login     — exchange credentials for access + refresh tokens
GET  /me        — return the currently signed-in user (validates the access token)
POST /refresh   — exchange a valid refresh token for a new token pair (rotates jti)
POST /logout    — revoke the refresh token (if provided) and clear client session

Token strategy: see app/core/security.py for the full explanation. The short
version is that access tokens expire in 60 minutes and refresh tokens in 7 days.
Refresh-token JTIs are stored so rotation and logout invalidate prior tokens.
The client should call /refresh automatically when it receives a 401 and retry
the failed request exactly once — see src/lib/api.ts in the frontend.
"""

from fastapi import APIRouter, HTTPException, Response, status

from app.api.deps import AdminUser, CurrentUser, DbSession
from app.core.security import (
    create_access_token,
    create_refresh_token,
    decode_refresh_token,
    refresh_token_ttl_seconds,
)
from app.schemas.auth import LoginRequest, LogoutRequest, RefreshRequest, TokenResponse
from app.schemas.user import UserCreate, UserOut
from app.services.auth_service import (
    authenticate_user,
    create_user,
    get_user_by_email,
    get_user_by_id,
)
from app.services.token_store import get_token_store


router = APIRouter()


def _issue_token_pair(subject: str) -> TokenResponse:
    store = get_token_store()
    refresh_token, jti = create_refresh_token(subject=subject)
    store.store(jti, subject, refresh_token_ttl_seconds())
    return TokenResponse(
        access_token=create_access_token(subject=subject),
        refresh_token=refresh_token,
    )


@router.post("/register", response_model=UserOut, status_code=status.HTTP_201_CREATED)
def register_user(
    user_in: UserCreate,
    _: AdminUser,
    db: DbSession,
) -> UserOut:
    # Staff accounts are invite-only via an authenticated admin. Demo users are
    # created by scripts/seed_demo.py (direct DB insert), not this endpoint.
    if get_user_by_email(db, user_in.email):
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="A user with this email already exists.",
        )
    return create_user(db, user_in)


@router.post("/login", response_model=TokenResponse)
def login(login_in: LoginRequest, db: DbSession) -> TokenResponse:
    user = authenticate_user(db, login_in.email, login_in.password)
    if user is None:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Incorrect email or password.",
        )

    # The frontend keeps these tokens in session state/storage and sends the
    # access token as Authorization: Bearer <token> on protected requests.
    return _issue_token_pair(str(user.id))


@router.get("/me", response_model=UserOut)
def get_me(current_user: CurrentUser) -> UserOut:
    return current_user


@router.post("/refresh", response_model=TokenResponse)
def refresh_token(refresh_in: RefreshRequest, db: DbSession) -> TokenResponse:
    # Rotation: validate the presented jti is still active, revoke it, then
    # issue a fresh pair. Replaying the old refresh token after this returns 401.
    decoded = decode_refresh_token(refresh_in.refresh_token)

    if decoded is None:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid or expired refresh token.",
        )

    subject, jti = decoded
    store = get_token_store()

    if not store.exists(jti):
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Refresh token has been revoked or already used.",
        )

    try:
        user_id = int(subject)
    except ValueError:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid refresh token subject.",
        ) from None

    user = get_user_by_id(db, user_id)

    if user is None:
        store.revoke(jti)
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="User for this refresh token no longer exists.",
        )

    store.revoke(jti)
    return _issue_token_pair(subject)


@router.post("/logout", status_code=status.HTTP_204_NO_CONTENT)
def logout(
    current_user: CurrentUser,
    logout_in: LogoutRequest = LogoutRequest(),
) -> Response:
    # Access token proves the caller; optional refresh_token lets us revoke the
    # long-lived credential so it cannot be reused after sign-out.
    if logout_in.refresh_token:
        decoded = decode_refresh_token(logout_in.refresh_token)
        if decoded is not None:
            subject, jti = decoded
            if subject == str(current_user.id):
                get_token_store().revoke(jti)

    return Response(status_code=status.HTTP_204_NO_CONTENT)
