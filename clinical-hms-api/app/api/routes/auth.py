"""
Authentication routes — /api/v1/auth/*

Endpoints
---------
POST /register  — create a new staff account (returns the user record, not tokens)
POST /login     — exchange credentials for access + refresh tokens
GET  /me        — return the currently signed-in user (validates the access token)
POST /refresh   — exchange a valid refresh token for a new token pair
POST /logout    — validate the access token then tell the client to clear its tokens

Token strategy: see app/core/security.py for the full explanation. The short
version is that access tokens expire in 60 minutes and refresh tokens in 7 days.
The client should call /refresh automatically when it receives a 401 and retry
the failed request exactly once — see src/lib/api.ts in the frontend.
"""

from fastapi import APIRouter, HTTPException, Response, status

from app.api.deps import CurrentUser, DbSession
from app.core.security import create_access_token, create_refresh_token, decode_token
from app.schemas.auth import LoginRequest, RefreshRequest, TokenResponse
from app.schemas.user import UserCreate, UserOut
from app.services.auth_service import (
    authenticate_user,
    create_user,
    get_user_by_email,
    get_user_by_id,
)


router = APIRouter()


@router.post("/register", response_model=UserOut, status_code=status.HTTP_201_CREATED)
def register_user(user_in: UserCreate, db: DbSession) -> UserOut:
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

    subject = str(user.id)
    # The frontend keeps these tokens in session state/storage and sends the
    # access token as Authorization: Bearer <token> on protected requests.
    return TokenResponse(
        access_token=create_access_token(subject=subject),
        refresh_token=create_refresh_token(subject=subject),
    )


@router.get("/me", response_model=UserOut)
def get_me(current_user: CurrentUser) -> UserOut:
    return current_user


@router.post("/refresh", response_model=TokenResponse)
def refresh_token(refresh_in: RefreshRequest, db: DbSession) -> TokenResponse:
    # TODO: Refresh tokens are currently not invalidated after use. A stolen
    # refresh token can be replayed indefinitely until it expires (7 days).
    # Fix: store issued refresh token IDs in Redis and reject any token whose ID
    # is not in the set. On refresh, delete the old ID and insert the new one.
    subject = decode_token(refresh_in.refresh_token, expected_type="refresh")

    if subject is None:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid or expired refresh token.",
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
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="User for this refresh token no longer exists.",
        )

    return TokenResponse(
        access_token=create_access_token(subject=subject),
        refresh_token=create_refresh_token(subject=subject),
    )


@router.post("/logout", status_code=status.HTTP_204_NO_CONTENT)
def logout(_: CurrentUser) -> Response:
    # JWT logout is stateless for now: the backend validates the caller and the
    # frontend clears its stored tokens. Redis-based revocation can be added later.
    return Response(status_code=status.HTTP_204_NO_CONTENT)
