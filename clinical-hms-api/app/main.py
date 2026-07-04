from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.api.routes import auth, facilities, health, triage
from app.core.config import settings

# Application entry point. Routes are grouped by domain (health, auth, facilities).
# New clinical modules (patients, queue, vitals, etc.) will be registered here.
app = FastAPI(title=settings.project_name)

# Allow the React dev server to call the API from a different local port.
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:5173", "http://127.0.0.1:5173"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(health.router, tags=["health"])  # /health stays at root for Docker/infra
app.include_router(auth.router, prefix="/api/v1/auth", tags=["auth"])
app.include_router(facilities.router, prefix="/api/v1/facilities", tags=["facilities"])
app.include_router(triage.router)
