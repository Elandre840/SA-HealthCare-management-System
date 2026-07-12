"""
Auth endpoint tests — covers every route in /api/v1/auth/*.

The happy path and the most important failure modes are tested.
Each test is independent: it receives a fresh in-memory SQLite database
via the `client` fixture in conftest.py.
"""

import pytest
from fastapi.testclient import TestClient

from tests.conftest import _auth_headers, _bootstrap_user


# ---------------------------------------------------------------------------
# POST /api/v1/auth/register
# ---------------------------------------------------------------------------

class TestRegister:
    def test_success(self, client: TestClient, admin_tokens: dict) -> None:
        resp = client.post(
            "/api/v1/auth/register",
            headers=_auth_headers(admin_tokens),
            json={
                "account_type": "staff",
                "first_name": "Amina",
                "surname": "Admin",
                "email": "amina@test.co.za",
                "password": "Password123!",
                "role": "admin",
                "department": "Administration",
            },
        )
        assert resp.status_code == 201
        body = resp.json()
        assert body["email"] == "amina@test.co.za"
        assert body["role"] == "admin"
        assert "hashed_password" not in body

    def test_unauthenticated_returns_401(self, client: TestClient) -> None:
        resp = client.post(
            "/api/v1/auth/register",
            json={
                "account_type": "staff",
                "first_name": "Open",
                "surname": "User",
                "email": "open@test.co.za",
                "password": "Password123!",
                "role": "nurse",
                "department": "Nursing",
            },
        )
        assert resp.status_code == 401

    def test_non_admin_returns_403(
        self, client: TestClient, auth_headers: dict
    ) -> None:
        resp = client.post(
            "/api/v1/auth/register",
            headers=auth_headers,
            json={
                "account_type": "staff",
                "first_name": "Forbidden",
                "surname": "User",
                "email": "forbidden@test.co.za",
                "password": "Password123!",
                "role": "nurse",
                "department": "Nursing",
            },
        )
        assert resp.status_code == 403

    def test_duplicate_email_returns_409(
        self, client: TestClient, admin_tokens: dict
    ) -> None:
        payload = {
            "account_type": "staff",
            "first_name": "Nandi",
            "surname": "Nurse",
            "email": "nandi@test.co.za",
            "password": "Password123!",
            "role": "nurse",
            "department": "Nursing",
        }
        headers = _auth_headers(admin_tokens)
        assert client.post("/api/v1/auth/register", headers=headers, json=payload).status_code == 201
        resp = client.post("/api/v1/auth/register", headers=headers, json=payload)
        assert resp.status_code == 409


# ---------------------------------------------------------------------------
# POST /api/v1/auth/login
# ---------------------------------------------------------------------------

class TestLogin:
    @pytest.fixture(autouse=True)
    def _seed_user(self, client: TestClient) -> None:
        _bootstrap_user(client, "rachel@test.co.za", "reception")

    def test_valid_credentials_return_tokens(self, client: TestClient) -> None:
        resp = client.post("/api/v1/auth/login", json={
            "email": "rachel@test.co.za",
            "password": "TestPass123!",
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

    def test_used_refresh_token_cannot_be_replayed(
        self, client: TestClient, reception_tokens: dict
    ) -> None:
        first = client.post("/api/v1/auth/refresh", json={
            "refresh_token": reception_tokens["refresh_token"],
        })
        assert first.status_code == 200

        replay = client.post("/api/v1/auth/refresh", json={
            "refresh_token": reception_tokens["refresh_token"],
        })
        assert replay.status_code == 401

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
        resp = client.post("/api/v1/auth/logout", headers=auth_headers, json={})
        assert resp.status_code == 204

    def test_logout_revokes_refresh_token(
        self, client: TestClient, reception_tokens: dict
    ) -> None:
        headers = {"Authorization": f"Bearer {reception_tokens['access_token']}"}
        resp = client.post(
            "/api/v1/auth/logout",
            headers=headers,
            json={"refresh_token": reception_tokens["refresh_token"]},
        )
        assert resp.status_code == 204

        replay = client.post("/api/v1/auth/refresh", json={
            "refresh_token": reception_tokens["refresh_token"],
        })
        assert replay.status_code == 401

    def test_no_token_returns_401(self, client: TestClient) -> None:
        resp = client.post("/api/v1/auth/logout", json={})
        assert resp.status_code == 401
