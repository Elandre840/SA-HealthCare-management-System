"""
Shared fixtures for the API test suite.

Each test gets its own in-memory SQLite database so tests are fully isolated
and never need a running PostgreSQL instance.  The `get_db` dependency is
overridden via FastAPI's dependency_overrides mechanism so the rest of the
application code is unchanged.
"""

import pytest
from fastapi.testclient import TestClient
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker
from sqlalchemy.pool import StaticPool

from app.core.config import settings
from app.db.base import Base
from app.db.models.user import AccountType, StaffRole
from app.db.session import get_db
from app.main import app
from app.schemas.user import UserCreate
from app.services.auth_service import create_user
from app.services.token_store import reset_token_store_for_tests


@pytest.fixture(autouse=True)
def _use_memory_token_store(monkeypatch: pytest.MonkeyPatch):
    # Local .env often sets REDIS_URL for Docker. Tests must not depend on Redis.
    monkeypatch.setattr(settings, "redis_url", None)
    reset_token_store_for_tests()
    yield
    reset_token_store_for_tests()


@pytest.fixture()
def client():
    # StaticPool makes every SQLAlchemy connection reuse the same underlying
    # SQLite connection, so Base.metadata.create_all() and all test requests
    # share the same in-memory database.  Without it, each new connection
    # gets an empty database and "no such table" errors occur.
    engine = create_engine(
        "sqlite://",
        connect_args={"check_same_thread": False},
        poolclass=StaticPool,
    )
    Base.metadata.create_all(bind=engine)
    Session = sessionmaker(bind=engine, autoflush=False, autocommit=False)

    def _get_test_db():
        db = Session()
        try:
            yield db
        finally:
            db.close()

    app.dependency_overrides[get_db] = _get_test_db

    with TestClient(app) as c:
        # Helpers bootstrap the first admin via the service layer (register is
        # admin-only and cannot create the initial account over HTTP).
        c.test_session_factory = Session
        yield c

    app.dependency_overrides.clear()
    Base.metadata.drop_all(bind=engine)
    engine.dispose()


# ---------------------------------------------------------------------------
# Reusable helpers — fixtures that build on `client`
# ---------------------------------------------------------------------------

def _bootstrap_user(
    client: TestClient,
    email: str,
    role: str,
    facility_id: int | None = None,
) -> None:
    db = client.test_session_factory()
    try:
        create_user(
            db,
            UserCreate(
                account_type=AccountType.staff,
                first_name="Test",
                surname=role.capitalize(),
                email=email,
                password="TestPass123!",
                role=StaffRole(role),
                department=role.capitalize(),
                facility_id=facility_id,
            ),
        )
    finally:
        db.close()


def _register(
    client: TestClient,
    admin_tokens: dict,
    email: str,
    role: str,
    facility_id: int | None = None,
) -> dict:
    payload: dict = {
        "account_type": "staff",
        "first_name": "Test",
        "surname": role.capitalize(),
        "email": email,
        "password": "TestPass123!",
        "role": role,
        "department": role.capitalize(),
    }
    if facility_id is not None:
        payload["facility_id"] = facility_id
    resp = client.post(
        "/api/v1/auth/register",
        headers=_auth_headers(admin_tokens),
        json=payload,
    )
    assert resp.status_code == 201, resp.text
    return resp.json()


def _login(client: TestClient, email: str, password: str = "TestPass123!") -> dict:
    resp = client.post("/api/v1/auth/login", json={"email": email, "password": password})
    assert resp.status_code == 200, resp.text
    return resp.json()


def _auth_headers(tokens: dict) -> dict:
    return {"Authorization": f"Bearer {tokens['access_token']}"}


@pytest.fixture()
def admin_tokens(client: TestClient) -> dict:
    _bootstrap_user(client, "admin@test.co.za", "admin")
    return _login(client, "admin@test.co.za")


@pytest.fixture()
def facility(client: TestClient, admin_tokens: dict) -> dict:
    resp = client.post(
        "/api/v1/facilities/",
        headers=_auth_headers(admin_tokens),
        json={
            "province": "Gauteng",
            "city": "Johannesburg",
            "name": "Test Clinic",
        },
    )
    assert resp.status_code == 201, resp.text
    return resp.json()


@pytest.fixture()
def reception_tokens(client: TestClient, admin_tokens: dict, facility: dict) -> dict:
    _register(client, admin_tokens, "reception@test.co.za", "reception", facility["id"])
    return _login(client, "reception@test.co.za")


@pytest.fixture()
def auth_headers(reception_tokens: dict) -> dict:
    return _auth_headers(reception_tokens)
