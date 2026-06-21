from collections.abc import Generator

from sqlalchemy import create_engine
from sqlalchemy.orm import Session, sessionmaker

from app.core.config import settings

# SQLite needs check_same_thread=False for FastAPI's async request handling.
# PostgreSQL uses the default connection settings from DATABASE_URL.
connect_args = (
    {"check_same_thread": False}
    if settings.database_url.startswith("sqlite")
    else {}
)

engine = create_engine(
    settings.database_url,
    connect_args=connect_args,
    pool_pre_ping=True,
)
SessionLocal = sessionmaker(bind=engine, autoflush=False, autocommit=False)


def get_db() -> Generator[Session, None, None]:
    # FastAPI dependency that opens one database session per request.
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()
