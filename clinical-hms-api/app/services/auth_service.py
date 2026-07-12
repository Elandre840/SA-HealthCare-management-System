"""
User CRUD and authentication service.

Business logic for user management lives here rather than in route handlers so
it can be called from both the API routes and the seed script without duplicating
code. Route handlers should import these functions rather than building SQL
queries directly.
"""

from sqlalchemy import select
from sqlalchemy.orm import Session

from app.core.security import hash_password, verify_password
from app.db.models.user import User
from app.schemas.user import UserCreate


def get_user_by_id(db: Session, user_id: int) -> User | None:
    return db.get(User, user_id)


def get_user_by_email(db: Session, email: str) -> User | None:
    return db.scalar(select(User).where(User.email == email))


def create_user(db: Session, user_in: UserCreate) -> User:
    user = User(
        account_type=user_in.account_type,
        first_name=user_in.first_name,
        surname=user_in.surname,
        email=user_in.email,
        hashed_password=hash_password(user_in.password),
        facility_id=user_in.facility_id,
        id_number=user_in.id_number,
        phone=user_in.phone,
        employee_number=user_in.employee_number,
        role=user_in.role,
        department=user_in.department,
    )
    db.add(user)
    db.commit()
    db.refresh(user)
    return user


def authenticate_user(db: Session, email: str, password: str) -> User | None:
    user = get_user_by_email(db, email)
    if user is None:
        return None
    # Always run verify_password even when the user is not found to prevent
    # timing-based user enumeration. Currently we return early above, which
    # leaks timing. TODO: replace with a constant-time dummy verify when user
    # is None before this goes to production.
    if not verify_password(password, user.hashed_password):
        return None
    return user
