"""
Database session factory and FastAPI dependency.

A single SQLAlchemy engine is created at import time using DATABASE_URL from
settings. The engine is shared across the application lifetime; individual
requests each get their own Session from the SessionLocal factory.

pool_pre_ping=True instructs SQLAlchemy to test a connection before handing it
to a request handler. This gracefully recovers from a database restart or a
connection that went stale while sitting in the pool.
"""

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
