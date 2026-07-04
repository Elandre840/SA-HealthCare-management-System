from typing import Annotated

from fastapi import Depends, HTTPException, status
from fastapi.security import HTTPAuthorizationCredentials, HTTPBearer
from sqlalchemy.orm import Session

from app.core.security import decode_token
from app.db.session import get_db
from app.db.models.user import StaffRole, User
from app.services.auth_service import get_user_by_id


DbSession = Annotated[Session, Depends(get_db)]

# HTTPBearer shows a plain token field in Swagger UI ("Authorize → Bearer token").
# OAuth2PasswordBearer would tell Swagger to POST form-encoded credentials to the
# login endpoint, but that endpoint expects JSON — causing a 422 in Swagger only.
_bearer_scheme = HTTPBearer(auto_error=False)


def _get_bearer_token(
    credentials: Annotated[HTTPAuthorizationCredentials | None, Depends(_bearer_scheme)],
) -> str | None:
    return credentials.credentials if credentials else None


BearerToken = Annotated[str | None, Depends(_get_bearer_token)]


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


def require_roles(*roles: StaffRole):
    # Factory that returns a FastAPI dependency — this is the pattern for
    # parameterised guards. Usage on a route:
    #   DoctorOrAdmin = Annotated[User, Depends(require_roles(StaffRole.doctor, StaffRole.admin))]
    # Add the type alias to this file and use it as a route parameter type.
    def _require_role(current_user: CurrentUser) -> User:
        if current_user.role not in roles:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="You do not have permission to perform this action.",
            )
        return current_user

    return _require_role


NurseUser = Annotated[User, Depends(require_roles(StaffRole.nurse))]
