# SA Healthcare Management System — Clinical HMS

End-to-end healthcare workflow platform for South African clinics — patient
registration, nurse triage with vitals capture, doctor consultations with
ICD-10 coding and prescriptions, and pharmacy dispensing. Built as a portfolio
project demonstrating full-stack system design and a complete end-to-end
clinical workflow.

**Repository:** [github.com/Elandre840/SA-HealthCare-management-System](https://github.com/Elandre840/SA-HealthCare-management-System)  
**Project owner:** Mj Technologies  
**Developer:** Elandre Booth

---

## Project Status

The PHP prototype has been retired. The repository contains only the
production-style rebuild. **The full clinical workflow is now implemented
end-to-end and working.**

| Layer | Directory | Status |
|---|---|---|
| FastAPI backend | `clinical-hms-api/` | ✅ Complete |
| React/TypeScript frontend | `clinical-hms-web/` | ✅ Complete |
| Docker orchestration | `docker-compose.yml` | ✅ Root-level, single command start |

---

## Architecture

```
clinical-hms-web/          clinical-hms-api/
React + TypeScript   ──►   FastAPI + SQLAlchemy   ──►   PostgreSQL 16
Vite + Tailwind CSS        JWT auth (HS256)               (Docker volume)
sessionStorage tokens      Alembic migrations
                           Redis 7 (reserved)
```

All API routes are versioned under `/api/v1`. The health check lives at
`/health` (no prefix) for Docker/load-balancer probes.

### Clinical workflow (visit pipeline)

```
Reception registers patient
        │
        ▼  Visit created: awaiting_triage
Nurse captures vitals + sets priority
        │
        ▼  Visit advances: awaiting_consultation
Doctor opens consultation, adds prescriptions, closes with diagnosis
        │
        ├──► (prescriptions exist) awaiting_pharmacy
        │           │
        │           ▼  Pharmacist dispenses each medication
        │           │
        └──► completed
```

---

## What is built

### Backend — `clinical-hms-api/`

| Area | Detail |
|---|---|
| Auth | register (admin only), login, refresh (rotating JTIs), logout (revokes refresh), /me — JWT HS256, bcrypt passwords |
| Facilities | create (admin) and list (authenticated) clinics; admin UI at `/facilities` |
| Patients | register patient + auto check-in (POST /patients/) with audit log |
| Triage | queue, vitals capture (POST), priority assignment (PATCH) with MediAlert on RED |
| Consultations | queue, open, amend, add prescriptions, close with ICD-10 |
| Pharmacy | queue, per-prescription dispense, complete visit |
| Audit log | POPIA-compliant — every write logs actor, entity, and details |
| DB | PostgreSQL via Docker; SQLite in-memory for the test suite |
| Migrations | Alembic — two migration files covering all tables |
| Tests | pytest — 20 tests covering auth, facilities, and health |

### Frontend — `clinical-hms-web/`

| Area | Detail |
|---|---|
| Login | Email/password → JWT stored in sessionStorage |
| Auto-refresh | 401 silently calls /auth/refresh and retries the original request |
| Role routing | Dashboard redirects each role to its clinical module |
| Patients | Search, register form with success card showing visit ID |
| Facilities | Admin list + create form (SA province select) |
| Triage | Queue list, VitalsCaptureForm, RED priority confirmation dialog |
| Consultations | Queue → open → add Rx → close with diagnosis and ICD-10 |
| Pharmacy | Queue → dispense each Rx individually → complete visit |
| AppShell | Role-based nav links, user name/role display, logout |

---

## Quick start — Docker (recommended)

All services run in Docker. **No local Python or Node installation required.**

### Four commands (from the repo root)

```bash
# 0. Once only — if clinical-hms-api/.env does not exist yet:
#    copy clinical-hms-api\.env.example clinical-hms-api\.env   (Windows)
#    cp clinical-hms-api/.env.example clinical-hms-api/.env     (macOS/Linux)

docker compose up -d --build
docker compose exec api alembic upgrade head
docker compose exec api python -m scripts.seed_demo
```

Then open **http://localhost:5173** (API docs: http://localhost:8000/docs).

> The default `.env.example` values work out-of-the-box with Docker Compose.
> Change `SECRET_KEY` before any internet-facing deployment.

### Demo credentials

All accounts use the password `Password123!`

| Role | Email | Module |
|---|---|---|
| Admin | `admin@clinicdemo.co.za` | All modules |
| Reception | `reception@clinicdemo.co.za` | `/patients` |
| Nurse | `nurse@clinicdemo.co.za` | `/triage` |
| Doctor | `doctor@clinicdemo.co.za` | `/consultations` |
| Pharmacist | `pharmacist@clinicdemo.co.za` | `/pharmacy` |

### Authorising requests in Swagger UI

1. Call `POST /api/v1/auth/login` with your credentials (JSON body).
2. Copy the `access_token` from the response.
3. Click **Authorize** (top right) and paste the token.

### Stopping / resetting

```bash
# Stop containers but keep the database volume
docker compose stop

# Remove containers (database volume is kept)
docker compose down

# Remove everything including the PostgreSQL data volume
docker compose down -v
```

---

## Running tests

### Backend (pytest)

```bash
docker compose exec api python -m pytest tests/ -v
```

The test suite uses SQLite in-memory via `StaticPool` — no running Postgres
required and no test data ever touches the development database.

### Frontend (Vitest)

```bash
docker compose run --rm web npm test
```

---

## Project structure

```
clinic_system/
├── docker-compose.yml          ← Start everything from here
├── README.md
│
├── clinical-hms-api/           FastAPI backend
│   ├── .env.example            Copy to .env before running
│   ├── .env.sqlite.example     Alternative: SQLite local dev (no Docker needed)
│   ├── Dockerfile
│   ├── requirements.txt
│   ├── alembic/
│   │   ├── env.py              Model imports for migration autogenerate
│   │   └── versions/           Migration files
│   ├── app/
│   │   ├── main.py             FastAPI app, CORS, router registration
│   │   ├── api/
│   │   │   ├── deps.py         Auth dependencies + role guards
│   │   │   └── routes/         auth, patients, triage, consultations, pharmacy
│   │   ├── core/
│   │   │   ├── config.py       Pydantic Settings (env vars)
│   │   │   └── security.py     bcrypt hashing, JWT create/decode
│   │   ├── db/
│   │   │   ├── base.py         DeclarativeBase
│   │   │   ├── session.py      Engine + get_db() dependency
│   │   │   └── models/         Facility, User, Patient, Visit, Vitals,
│   │   │                       Consultation, Prescription, AuditLog
│   │   ├── schemas/            Pydantic I/O schemas for each route module
│   │   └── services/           auth_service, audit_service
│   └── scripts/seed_demo.py    Demo data seeder (development only)
│
├── clinical-hms-web/           React + TypeScript SPA
│   ├── .env.example            Copy to .env if running outside Docker
│   ├── Dockerfile
│   ├── package.json
│   └── src/
│       ├── main.tsx            App bootstrap + provider hierarchy
│       ├── App.tsx             Route tree with role guards
│       ├── auth/               AuthContext, ProtectedRoute, useAuth
│       ├── components/         AppShell (nav), VitalsCaptureForm
│       ├── lib/                api.ts (typed API client), session.ts, triageValidation.ts
│       ├── pages/              Login, Dashboard, Patients, Triage, Consultation, Pharmacy
│       └── types/              auth.ts, patient.ts, triage.ts, consultation.ts
│
└── assets/
    └── screenshots/            Portfolio screenshots
```

---

## Tech stack

| Layer | Technology |
|---|---|
| API framework | FastAPI, Pydantic v2 |
| ORM / migrations | SQLAlchemy 2.0, Alembic |
| Auth | JWT HS256 (python-jose), bcrypt |
| Database | PostgreSQL 16 (Docker), SQLite in-memory (tests) |
| Cache / queue | Redis 7 (refresh-token revocation; reserved for queues/caching) |
| Frontend | React 19, TypeScript 6, Vite 8, Tailwind CSS 4 |
| Containerisation | Docker, Docker Compose |
| Backend tests | pytest, httpx2, SQLite + StaticPool |
| Frontend tests | Vitest, React Testing Library |

---

## Known limitations and future improvements

| Area | Description |
|---|---|
| Production API process | Compose still runs uvicorn with `--reload` for local development. Remove `--reload` (and prefer gunicorn/uvicorn workers) for a hardened deploy. |

---

## Notes

- Portfolio / demo project — not for real clinical use without full security hardening.
- Demo credentials are intentionally simple for local testing. Change `SECRET_KEY`
  and all passwords before any internet-facing deployment.
- Do not commit real patient data or production secrets.

---

## Ownership

**Owner:** Mj Technologies  
**Developer:** Elandre Booth

---

## License

Shared for portfolio and educational purposes. Contact the author for other uses.
