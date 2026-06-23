from typing import Annotated

from fastapi import Depends, HTTPException, status
from fastapi.security import OAuth2PasswordBearer
from sqlalchemy.orm import Session

from app.core.security import decode_token
from app.db.session import get_db
from app.db.models.user import User
from app.services.auth_service import get_user_by_id


DbSession = Annotated[Session, Depends(get_db)]
oauth2_scheme = OAuth2PasswordBearer(tokenUrl="/auth/login")
BearerToken = Annotated[str, Depends(oauth2_scheme)]


def get_current_user(db: DbSession, token: BearerToken) -> User:
    # Shared guard for protected endpoints. It decodes the bearer token, loads the
    # matching user, and fails early if the session is no longer valid.
    subject = decode_token(token, expected_type="access")

    if subject is None:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid or expired access token.",
            headers={"WWW-Authenticate": "Bearer"},
        )

    try:
        user_id = int(subject)
    except ValueError:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid access token subject.",
            headers={"WWW-Authenticate": "Bearer"},
        ) from None

    user = get_user_by_id(db, user_id)

    if user is None:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="User for this access token no longer exists.",
            headers={"WWW-Authenticate": "Bearer"},
        )

    return user


CurrentUser = Annotated[User, Depends(get_current_user)]
