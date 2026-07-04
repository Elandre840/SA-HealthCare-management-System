from fastapi import APIRouter, status
from sqlalchemy import select

from app.api.deps import DbSession
from app.db.models.facility import Facility
from app.schemas.facility import FacilityCreate, FacilityOut


router = APIRouter()


# TODO: Both endpoints are currently open — no authentication required.
# Before this goes beyond localhost, add CurrentUser to create_facility so only
# admins can register new facilities, and decide whether list_facilities should
# be restricted to authenticated users only.
@router.post("/", response_model=FacilityOut, status_code=status.HTTP_201_CREATED)
def create_facility(facility_in: FacilityCreate, db: DbSession) -> FacilityOut:
    facility = Facility(**facility_in.model_dump())
    db.add(facility)
    db.commit()
    db.refresh(facility)
    return facility


@router.get("/", response_model=list[FacilityOut])
def list_facilities(db: DbSession) -> list[FacilityOut]:
    return list(db.scalars(select(Facility).order_by(Facility.province, Facility.city)))
