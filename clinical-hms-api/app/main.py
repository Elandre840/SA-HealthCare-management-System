from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.api.routes import auth, consultations, facilities, health, patients, pharmacy, triage
from app.core.config import settings

app = FastAPI(title=settings.project_name)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:5173", "http://127.0.0.1:5173"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(health.router, tags=["health"])
app.include_router(auth.router, prefix="/api/v1/auth", tags=["auth"])
app.include_router(facilities.router, prefix="/api/v1/facilities", tags=["facilities"])
app.include_router(patients.router, prefix="/api/v1/patients", tags=["patients"])
app.include_router(triage.router, prefix="/api/v1/triage", tags=["triage"])
app.include_router(consultations.router, prefix="/api/v1/consultations", tags=["consultations"])
app.include_router(pharmacy.router, prefix="/api/v1/pharmacy", tags=["pharmacy"])
