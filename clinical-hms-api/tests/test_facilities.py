from fastapi.testclient import TestClient


def _admin_headers(admin_tokens: dict) -> dict:
    return {"Authorization": f"Bearer {admin_tokens['access_token']}"}


class TestCreateFacility:
    def test_creates_facility(self, client: TestClient, admin_tokens: dict) -> None:
        resp = client.post(
            "/api/v1/facilities/",
            headers=_admin_headers(admin_tokens),
            json={
                "province": "Western Cape",
                "city": "Cape Town",
                "name": "Cape Town Clinic",
            },
        )
        assert resp.status_code == 201
        body = resp.json()
        assert body["province"] == "Western Cape"
        assert body["city"] == "Cape Town"
        assert body["name"] == "Cape Town Clinic"
        assert "id" in body

    def test_missing_required_field_returns_422(
        self, client: TestClient, admin_tokens: dict
    ) -> None:
        resp = client.post(
            "/api/v1/facilities/",
            headers=_admin_headers(admin_tokens),
            json={"province": "Gauteng"},
        )
        assert resp.status_code == 422

    def test_unauthenticated_returns_401(self, client: TestClient) -> None:
        resp = client.post(
            "/api/v1/facilities/",
            json={
                "province": "Gauteng",
                "city": "Johannesburg",
                "name": "Open Clinic",
            },
        )
        assert resp.status_code == 401

    def test_non_admin_returns_403(
        self, client: TestClient, auth_headers: dict
    ) -> None:
        resp = client.post(
            "/api/v1/facilities/",
            headers=auth_headers,
            json={
                "province": "Gauteng",
                "city": "Johannesburg",
                "name": "Forbidden Clinic",
            },
        )
        assert resp.status_code == 403


class TestListFacilities:
    def test_unauthenticated_returns_401(self, client: TestClient) -> None:
        resp = client.get("/api/v1/facilities/")
        assert resp.status_code == 401

    def test_empty_list(self, client: TestClient, admin_tokens: dict) -> None:
        resp = client.get("/api/v1/facilities/", headers=_admin_headers(admin_tokens))
        assert resp.status_code == 200
        assert resp.json() == []

    def test_returns_seeded_facility(
        self, client: TestClient, facility: dict, auth_headers: dict
    ) -> None:
        resp = client.get("/api/v1/facilities/", headers=auth_headers)
        assert resp.status_code == 200
        data = resp.json()
        assert len(data) == 1
        assert data[0]["id"] == facility["id"]

    def test_ordered_by_province_then_city(
        self, client: TestClient, admin_tokens: dict
    ) -> None:
        headers = _admin_headers(admin_tokens)
        client.post(
            "/api/v1/facilities/",
            headers=headers,
            json={"province": "KwaZulu-Natal", "city": "Durban", "name": "Durban Clinic"},
        )
        client.post(
            "/api/v1/facilities/",
            headers=headers,
            json={"province": "Gauteng", "city": "Pretoria", "name": "Pretoria Clinic"},
        )
        client.post(
            "/api/v1/facilities/",
            headers=headers,
            json={"province": "Gauteng", "city": "Johannesburg", "name": "JHB Clinic"},
        )

        resp = client.get("/api/v1/facilities/", headers=headers)
        assert resp.status_code == 200
        names = [f["name"] for f in resp.json()]
        assert names == ["JHB Clinic", "Pretoria Clinic", "Durban Clinic"]
