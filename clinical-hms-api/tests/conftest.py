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

from app.db.base import Base
from app.db.session import get_db
from app.main import app


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
        yield c

    app.dependency_overrides.clear()
    Base.metadata.drop_all(bind=engine)
    engine.dispose()


# ---------------------------------------------------------------------------
# Reusable helpers — fixtures that build on `client`
# ---------------------------------------------------------------------------

@pytest.fixture()
def facility(client: TestClient) -> dict:
    resp = client.post("/api/v1/facilities/", json={
        "province": "Gauteng",
        "city": "Johannesburg",
        "name": "Test Clinic",
    })
    assert resp.status_code == 201
    return resp.json()


def _register(client: TestClient, email: str, role: str, facility_id: int | None = None) -> dict:
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
    resp = client.post("/api/v1/auth/register", json=payload)
    assert resp.status_code == 201, resp.text
    return resp.json()


def _login(client: TestClient, email: str, password: str = "TestPass123!") -> dict:
    resp = client.post("/api/v1/auth/login", json={"email": email, "password": password})
    assert resp.status_code == 200, resp.text
    return resp.json()


@pytest.fixture()
def reception_tokens(client: TestClient, facility: dict) -> dict:
    _register(client, "reception@test.co.za", "reception", facility["id"])
    return _login(client, "reception@test.co.za")


@pytest.fixture()
def auth_headers(reception_tokens: dict) -> dict:
    return {"Authorization": f"Bearer {reception_tokens['access_token']}"}
