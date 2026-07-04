# SA Healthcare Management System

End-to-end healthcare workflow platform for South African clinics — patient registration, triage, consultations, dispensing, and reporting. Built as a portfolio project demonstrating full-stack system design and progressive modernisation from a PHP prototype to a production-style API + React SPA.

**Repository:** [github.com/Elandre840/SA-HealthCare-management-System](https://github.com/Elandre840/SA-HealthCare-management-System)  
**Project owner:** Mj Technologies  
**Developer:** Elandre Booth

---

## Project Status

The PHP prototype has been retired. The repo now contains only the production-style rebuild:

| Layer | Directory | Status |
|---|---|---|
| FastAPI backend | `clinical-hms-api/` | Auth shell complete, clinical layer in progress |
| React/TypeScript frontend | `clinical-hms-web/` | Auth shell complete, clinical screens in progress |
| Reference assets | `assets/` | Province emblems + screenshots kept for the frontend build |

---

## Architecture

```
clinical-hms-web/          clinical-hms-api/
React + TypeScript   ──►   FastAPI + SQLAlchemy   ──►   PostgreSQL
Vite + Tailwind CSS        JWT auth (HS256)               (Docker)
                           Alembic migrations
                           Redis (reserved)
```

All API routes are versioned under `/api/v1`. The health check lives at `/health` (no prefix) for Docker/infra probes.

---

## What is built

### Backend — `clinical-hms-api/`

| Area | Detail |
|---|---|
| Auth | `POST /api/v1/auth/register`, `login`, `refresh`, `logout`, `GET /me` |
| Facilities | `POST /api/v1/facilities/`, `GET /api/v1/facilities/` |
| Tokens | JWT access token (60 min) + refresh token (7 days), HS256 |
| Security | `HTTPBearer` scheme — Swagger `Authorize` takes a raw token, not a form |
| DB | PostgreSQL via Docker; SQLite for tests (no Postgres needed to run the test suite) |
| Migrations | Alembic — run `alembic upgrade head` before first start |
| Tests | `pytest` — 20 tests covering auth and facilities, all passing |

### Frontend — `clinical-hms-web/`

| Area | Detail |
|---|---|
| Login | Email + password form, calls `POST /api/v1/auth/login` |
| Token storage | `sessionStorage` — survives page refresh, clears on tab close |
| Auto-refresh | 401 on any authenticated request silently calls `/api/v1/auth/refresh` and retries |
| Route guard | `ProtectedRoute` reads `/api/v1/auth/me` on mount; redirects to `/login` if unauthenticated |
| Shell | Top bar shows `full_name` + role; logout button calls `/api/v1/auth/logout` |
| Dashboards | Role-appropriate placeholder pages for all 5 roles |
| Tests | Vitest — form validation + token refresh retry, both passing |

---

## Quick start (Docker)

All services run in Docker — no local Python or Node installation required.

### 1. Clone and configure

```bash
git clone https://github.com/Elandre840/SA-HealthCare-management-System.git
cd SA-HealthCare-management-System
```

Copy the environment files:

```bash
cp clinical-hms-api/.env.example clinical-hms-api/.env
cp clinical-hms-web/.env.example clinical-hms-web/.env
```

### 2. Start the stack

```bash
cd clinical-hms-api
docker compose up -d
```

This starts three containers: `clinical_hms_api` (FastAPI on `:8000`), `clinical_hms_postgres`, and `clinical_hms_redis`.

### 3. Run migrations and seed demo data

```bash
docker compose exec api alembic upgrade head
docker compose exec api python -m scripts.seed_demo
```

The seed script creates one facility (Demo Community Clinic, Johannesburg) and five staff accounts — one per role. All share the password `Password123!`.

| Role | Email |
|---|---|
| Admin | `admin@clinicdemo.co.za` |
| Reception | `reception@clinicdemo.co.za` |
| Nurse | `nurse@clinicdemo.co.za` |
| Doctor | `doctor@clinicdemo.co.za` |
| Pharmacist | `pharmacist@clinicdemo.co.za` |

### 4. Start the frontend

```bash
docker compose up -d web
```

The React dev server is available at [http://localhost:5173](http://localhost:5173).

### 5. Explore the API

Swagger UI: [http://localhost:8000/docs](http://localhost:8000/docs)

To authorize in Swagger:
1. Call `POST /api/v1/auth/login` with your credentials (JSON body).
2. Copy the `access_token` from the response.
3. Click **Authorize** and paste the token into the `HTTPBearer` field.

---

## Running tests

### Backend (pytest)

```bash
docker compose exec api python -m pytest tests/ -v
```

No running Postgres needed — the test suite uses SQLite in-memory via `StaticPool`.

### Frontend (Vitest)

```bash
docker compose run --rm web npm test
```

---

## Project structure

```
SA-HealthCare-management-System/
├── assets/
│   ├── backgrounds/          # SA flag (used for login UI)
│   ├── emblems/              # 9 province crests (used for role dashboards)
│   └── screenshots/          # Portfolio screenshots of the PHP prototype
│
├── clinical-hms-api/         # FastAPI backend
│   ├── alembic/              # Database migrations
│   ├── app/
│   │   ├── api/routes/       # auth.py, facilities.py, health.py
│   │   ├── core/             # config.py, security.py (JWT)
│   │   ├── db/               # models/, session.py, base.py
│   │   ├── schemas/          # Pydantic I/O schemas
│   │   └── services/         # auth_service.py
│   ├── scripts/seed_demo.py  # Demo data seeder
│   ├── tests/                # pytest suite
│   ├── docker-compose.yml    # API + Postgres + Redis + Web
│   ├── Dockerfile            # python:3.12 image
│   └── requirements.txt
│
├── clinical-hms-web/         # React frontend
│   ├── src/
│   │   ├── auth/             # AuthContext, ProtectedRoute, useAuth
│   │   ├── components/       # AppShell (top bar + logout)
│   │   ├── lib/              # api.ts (typed fetch client), session.ts
│   │   ├── pages/            # LoginPage, DashboardPage
│   │   └── types/            # auth.ts (User, TokenResponse, etc.)
│   ├── Dockerfile            # node:22-alpine image
│   └── package.json
│
└── README.md
```

---

## What's next

The clinical layer (patients → triage → vitals → consultation → prescriptions → pharmacy) is the next build phase. This will add:

- `Patient` registration endpoint (reception)
- `Visit` check-in and triage queue (nurse)
- `Vitals` recording + MediAlert on RED triage
- `Consultation` with ICD-10 diagnosis and prescriptions (doctor)
- Prescription dispensing and visit completion (pharmacist)
- `AuditLog` for POPIA compliance

---

## Tech stack

| Layer | Technology |
|---|---|
| API framework | FastAPI 0.x, Pydantic v2 |
| ORM / migrations | SQLAlchemy 2.0, Alembic |
| Auth | JWT (python-jose), bcrypt (passlib) |
| Database | PostgreSQL 16 (production), SQLite (tests) |
| Cache / queue | Redis 7 (reserved for future use) |
| Frontend | React 19, TypeScript 6, Vite 8, Tailwind CSS 4 |
| Containerisation | Docker, Docker Compose |
| Backend tests | pytest, httpx2, SQLite + StaticPool |
| Frontend tests | Vitest, React Testing Library |

---

## Notes

- Portfolio / demo use only — not intended for real clinical deployment without full security hardening.
- Demo credentials are intentionally simple (`Password123!`) for local testing.
- Do not commit real patient data or production secrets.

---

## Ownership

**Owner:** Mj Technologies  
**Developer:** Elandre Booth

Feel free to connect via GitHub for collaboration or feedback.

---

## License

Shared for portfolio and educational purposes. Contact the author for other uses.
