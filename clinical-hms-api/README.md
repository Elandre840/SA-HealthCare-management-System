# Clinical HMS API

FastAPI rebuild of the SA Healthcare Management System prototype.

This API is being built beside the existing PHP demo so the portfolio project can keep working while the backend is redesigned with a production-style Python stack.

**Project owner:** Mj Technologies  
**Developer:** Elandre Booth

## Current Status

This backend currently covers the foundation layer:

- app startup and routing
- database models for facilities and users
- password hashing
- JWT login, refresh, current-user lookup, and logout endpoint
- demo staff seeding
- SQLite quick-start for local testing
- Docker Compose setup for PostgreSQL and Redis once Docker Desktop is available

The next backend module is the clinical patient workflow:

```text
Reception -> Nurse -> Doctor -> Pharmacist -> Completed
```

For now, the existing PHP app remains the working clinical-flow reference while this API is built out safely.

## Step 1: What We Built First

The first slice contains:

- FastAPI app startup
- PostgreSQL connection with SQLAlchemy
- Alembic database migrations
- Facilities table
- Users table for staff and patients
- Password hashing with bcrypt
- JWT login token creation
- Docker Compose for API, PostgreSQL, and Redis

## Why These Pieces Matter

`FastAPI` is the web framework. It receives HTTP requests and returns JSON responses.

`SQLAlchemy` is the ORM. It lets us work with Python classes instead of writing raw SQL everywhere.

`Alembic` tracks database changes. Every schema change becomes a migration file that can be applied consistently.

`Pydantic` validates input. If a request has bad data, the API rejects it before it reaches the database.

`PostgreSQL` is the main database. It is a stronger long-term choice than MySQL/MariaDB for this kind of system because it has excellent constraints, indexing, JSON support, and row-level security options.

`Redis` is included for later. We will use it for caching, rate limiting, sessions, and background job coordination.

## Quick Local Setup With SQLite

Use this path when Docker/PostgreSQL is not available yet. It creates a local
`dev.db` file and seeds the five demo staff accounts.

1. Copy the environment example:

```powershell
Copy-Item .env.sqlite.example .env
```

2. Create a virtual environment and install dependencies:

```powershell
python -m venv .venv
.\.venv\Scripts\python -m pip install -r requirements.txt
```

3. Seed the local demo database:

```powershell
.\.venv\Scripts\python .\scripts\seed_demo.py
```

4. Start the API:

```powershell
.\.venv\Scripts\python -m uvicorn app.main:app --host 127.0.0.1 --port 8000 --reload
```

5. Open the API docs:

```text
http://127.0.0.1:8000/docs
```

## Architecture Overview

The backend follows a modular layered structure:

```text
app/
├── api/        → HTTP routes and request dependencies
├── services/   → Business logic (auth, future patient workflow)
├── schemas/    → Pydantic validation and API response contracts
├── db/         → SQLAlchemy models, sessions, and migrations
└── core/       → Configuration, security, and shared utilities
```

| Layer | Responsibility |
|-------|----------------|
| **API** | Receives HTTP requests and returns JSON responses |
| **Services** | Contains business rules separate from routing |
| **Schemas** | Validates input/output before it reaches the database |
| **DB** | ORM models, database sessions, Alembic migrations |
| **Core** | Environment config, JWT security, shared settings |

This separation improves maintainability, scalability, and testability as the clinical workflow modules are added.

## Docker Setup (Local Development)

This project uses Docker to simulate a production-like environment locally.

**Note:** Docker Desktop must be installed and running before using these commands. On Windows, enable the WSL 2 backend during installation.

### Services

| Service | Purpose |
|---------|---------|
| **api** | FastAPI backend (`clinical_hms_api`) |
| **postgres** | Primary database (`clinical_hms_postgres`) |
| **redis** | Caching and future background job support (`clinical_hms_redis`) |

### First-Time Setup

1. Copy the PostgreSQL environment example:

```powershell
Copy-Item .env.example .env
```

2. Verify Docker is available:

```powershell
docker --version
docker compose version
```

3. Start all services in the background:

```powershell
docker compose up -d --build
```

4. Check running containers:

```powershell
docker ps
```

5. Run database migrations:

```powershell
docker compose exec api alembic upgrade head
```

6. Seed demo staff accounts:

```powershell
docker compose exec api python scripts/seed_demo.py
```

7. Open the API docs:

```text
http://localhost:8000/docs
```

### Stop Services

```powershell
docker compose down
```

To also remove the PostgreSQL volume (deletes all database data):

```powershell
docker compose down -v
```

## Demo Accounts

All demo accounts use this password:

```text
Password123!
```

```text
admin@clinicdemo.co.za
reception@clinicdemo.co.za
nurse@clinicdemo.co.za
doctor@clinicdemo.co.za
pharmacist@clinicdemo.co.za
```

## First Endpoints

```text
GET  /health
POST /facilities/
GET  /facilities/
POST /auth/register   (admin Bearer token required)
POST /auth/login
GET  /auth/me
POST /auth/refresh
POST /auth/logout
```

## Suggested First Test Flow

1. Run `python scripts/seed_demo.py` or the Docker equivalent to seed demo staff.
2. Call `POST /auth/login` with one of the demo accounts to receive access and refresh tokens.
3. Call `GET /auth/me` with `Authorization: Bearer <access_token>` to confirm the protected route.
4. (Optional) As admin, call `POST /auth/register` to create another staff account.
5. Call `POST /auth/refresh` with the refresh token to confirm token renewal.
6. Call `POST /auth/logout` to confirm the frontend can end the session cleanly.

## Local Sharing / Deployment Notes

For early collaboration, tools like `ngrok` or Cloudflare Tunnel can expose the local frontend/API while the app still runs on a developer machine.

For a more stable demo later, the likely path is:

```text
React frontend -> hosted static site
FastAPI backend -> cloud server/container
PostgreSQL -> managed database
```

Final deployment choice is still open and should be decided after the clinical workflow screens are implemented.

## Next Build Step

Next we add the patient workflow API:

- patient registration
- patient queue status
- nurse vitals
- doctor consultation
- pharmacy completion

That maps directly to the current PHP flow:

```text
Reception -> Nurse -> Doctor -> Pharmacist -> Completed
```
