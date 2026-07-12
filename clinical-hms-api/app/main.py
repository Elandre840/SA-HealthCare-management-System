"""
Application entry point for the Clinical HMS FastAPI backend.

This module creates the FastAPI application instance, configures CORS so the
React frontend (running on port 5173) can reach the API, and mounts every
clinical route module under the /api/v1 version prefix.

Visit http://localhost:8000/docs (Swagger UI) or /redoc for interactive API
documentation once the server is running.
"""

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.api.routes import audit_log, auth, consultations, facilities, health, patients, pharmacy, triage
from app.core.config import settings

app = FastAPI(
    title=settings.project_name,
    description=(
        "REST API for the Clinical HMS — a South African clinic workflow platform "
        "covering patient registration, triage, consultation, and pharmacy dispensing."
    ),
    version="1.0.0",
)

# Allow the Vite dev server on port 5173 to make cross-origin requests.
# In production this list should be restricted to the actual frontend domain.
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:5173", "http://127.0.0.1:5173"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Health check lives at /health (no version prefix) so load-balancers and
# Docker health checks can reach it without knowing the API version.
app.include_router(health.router, tags=["health"])
app.include_router(auth.router, prefix="/api/v1/auth", tags=["auth"])
app.include_router(facilities.router, prefix="/api/v1/facilities", tags=["facilities"])
app.include_router(patients.router, prefix="/api/v1/patients", tags=["patients"])
app.include_router(triage.router, prefix="/api/v1/triage", tags=["triage"])
app.include_router(consultations.router, prefix="/api/v1/consultations", tags=["consultations"])
app.include_router(pharmacy.router, prefix="/api/v1/pharmacy", tags=["pharmacy"])
app.include_router(audit_log.router, prefix="/api/v1/audit-logs", tags=["audit-logs"])
