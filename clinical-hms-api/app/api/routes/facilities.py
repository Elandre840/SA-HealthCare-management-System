"""
Facility routes — /api/v1/facilities/*

POST /  — create a facility (admin only)
GET  /  — list facilities ordered by province then city (any authenticated user)
"""

from fastapi import APIRouter, status
from sqlalchemy import select

from app.api.deps import AdminUser, CurrentUser, DbSession
from app.db.models.facility import Facility
from app.schemas.facility import FacilityCreate, FacilityOut


router = APIRouter()


@router.post("/", response_model=FacilityOut, status_code=status.HTTP_201_CREATED)
def create_facility(
    facility_in: FacilityCreate,
    _: AdminUser,
    db: DbSession,
) -> FacilityOut:
    # AdminUser dependency enforces role before the body is persisted.
    facility = Facility(**facility_in.model_dump())
    db.add(facility)
    db.commit()
    db.refresh(facility)
    return facility


@router.get("/", response_model=list[FacilityOut])
def list_facilities(_: CurrentUser, db: DbSession) -> list[FacilityOut]:
    # Any signed-in staff can list clinics (needed for assignment / display).
    # Creating clinics remains admin-only via create_facility above.
    return list(db.scalars(select(Facility).order_by(Facility.province, Facility.city)))
