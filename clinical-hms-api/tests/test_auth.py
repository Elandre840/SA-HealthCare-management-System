"""
Auth endpoint tests — covers every route in /api/v1/auth/*.

The happy path and the most important failure modes are tested.
Each test is independent: it receives a fresh in-memory SQLite database
via the `client` fixture in conftest.py.
"""

import pytest
from fastapi.testclient import TestClient

# ---------------------------------------------------------------------------
# POST /api/v1/auth/register
# ---------------------------------------------------------------------------

class TestRegister:
    def test_success(self, client: TestClient) -> None:
        resp = client.post("/api/v1/auth/register", json={
            "account_type": "staff",
            "first_name": "Amina",
            "surname": "Admin",
            "email": "amina@test.co.za",
            "password": "Password123!",
            "role": "admin",
            "department": "Administration",
        })
        assert resp.status_code == 201
        body = resp.json()
        assert body["email"] == "amina@test.co.za"
        assert body["role"] == "admin"
        assert "hashed_password" not in body

    def test_duplicate_email_returns_409(self, client: TestClient) -> None:
        payload = {
            "account_type": "staff",
            "first_name": "Nandi",
            "surname": "Nurse",
            "email": "nandi@test.co.za",
            "password": "Password123!",
            "role": "nurse",
            "department": "Nursing",
        }
        client.post("/api/v1/auth/register", json=payload)
        resp = client.post("/api/v1/auth/register", json=payload)
        assert resp.status_code == 409


# ---------------------------------------------------------------------------
# POST /api/v1/auth/login
# ---------------------------------------------------------------------------

class TestLogin:
    @pytest.fixture(autouse=True)
    def _seed_user(self, client: TestClient) -> None:
        client.post("/api/v1/auth/register", json={
            "account_type": "staff",
            "first_name": "Rachel",
            "surname": "Reception",
            "email": "rachel@test.co.za",
            "password": "Password123!",
            "role": "reception",
            "department": "Reception",
        })

    def test_valid_credentials_return_tokens(self, client: TestClient) -> None:
        resp = client.post("/api/v1/auth/login", json={
            "email": "rachel@test.co.za",
            "password": "Password123!",
        })
        assert resp.status_code == 200
        body = resp.json()
        assert "access_token" in body
        assert "refresh_token" in body
        assert body["token_type"] == "bearer"

    def test_wrong_password_returns_401(self, client: TestClient) -> None:
        resp = client.post("/api/v1/auth/login", json={
            "email": "rachel@test.co.za",
            "password": "wrongpassword",
        })
        assert resp.status_code == 401

    def test_unknown_email_returns_401(self, client: TestClient) -> None:
        resp = client.post("/api/v1/auth/login", json={
            "email": "ghost@test.co.za",
            "password": "Password123!",
        })
        assert resp.status_code == 401


# ---------------------------------------------------------------------------
# GET /api/v1/auth/me
# ---------------------------------------------------------------------------

class TestGetMe:
    def test_returns_current_user(self, client: TestClient, auth_headers: dict) -> None:
        resp = client.get("/api/v1/auth/me", headers=auth_headers)
        assert resp.status_code == 200
        body = resp.json()
        assert body["email"] == "reception@test.co.za"
        assert body["role"] == "reception"
        assert "hashed_password" not in body

    def test_no_token_returns_401(self, client: TestClient) -> None:
        resp = client.get("/api/v1/auth/me")
        assert resp.status_code == 401

    def test_malformed_token_returns_401(self, client: TestClient) -> None:
        resp = client.get("/api/v1/auth/me", headers={"Authorization": "Bearer notavalidtoken"})
        assert resp.status_code == 401

    def test_access_token_not_accepted_as_refresh(self, client: TestClient, reception_tokens: dict) -> None:
        resp = client.post("/api/v1/auth/refresh", json={
            "refresh_token": reception_tokens["access_token"],
        })
        assert resp.status_code == 401


# ---------------------------------------------------------------------------
# POST /api/v1/auth/refresh
# ---------------------------------------------------------------------------

class TestRefresh:
    def test_valid_refresh_token_returns_new_tokens(
        self, client: TestClient, reception_tokens: dict
    ) -> None:
        resp = client.post("/api/v1/auth/refresh", json={
            "refresh_token": reception_tokens["refresh_token"],
        })
        assert resp.status_code == 200
        body = resp.json()
        assert "access_token" in body
        assert "refresh_token" in body

    def test_invalid_refresh_token_returns_401(self, client: TestClient) -> None:
        resp = client.post("/api/v1/auth/refresh", json={"refresh_token": "garbage"})
        assert resp.status_code == 401

    def test_access_token_rejected_as_refresh(
        self, client: TestClient, reception_tokens: dict
    ) -> None:
        resp = client.post("/api/v1/auth/refresh", json={
            "refresh_token": reception_tokens["access_token"],
        })
        assert resp.status_code == 401


# ---------------------------------------------------------------------------
# POST /api/v1/auth/logout
# ---------------------------------------------------------------------------

class TestLogout:
    def test_valid_token_returns_204(self, client: TestClient, auth_headers: dict) -> None:
        resp = client.post("/api/v1/auth/logout", headers=auth_headers)
        assert resp.status_code == 204

    def test_no_token_returns_401(self, client: TestClient) -> None:
        resp = client.post("/api/v1/auth/logout")
        assert resp.status_code == 401
