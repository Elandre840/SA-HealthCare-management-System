from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.api.deps import CurrentUser, DbSession, require_roles
from app.db.models.facility import Facility
from app.db.models.patient import Patient
from app.db.models.user import StaffRole
from app.db.models.visit import Visit, VisitStatus
from app.schemas.patient import PatientCreate, PatientResponse, PatientVisitResponse
from app.services.audit_service import log_action

router = APIRouter(tags=["patients"])

ReceptionUser = Annotated[
    CurrentUser, Depends(require_roles(StaffRole.reception, StaffRole.admin))
]


@router.post("/", response_model=PatientVisitResponse, status_code=status.HTTP_201_CREATED)
def register_patient(body: PatientCreate, db: DbSession, current_user: ReceptionUser):
    facility = db.get(Facility, current_user.facility_id)
    if facility is None:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Your account is not linked to a facility.",
        )

    if body.id_number:
        existing = db.scalar(select(Patient).where(Patient.id_number == body.id_number))
        if existing is not None:
            raise HTTPException(
                status_code=status.HTTP_409_CONFLICT,
                detail=f"A patient with ID number {body.id_number} is already registered.",
            )

    patient = Patient(
        first_name=body.first_name,
        surname=body.surname,
        id_number=body.id_number,
        date_of_birth=body.date_of_birth,
        gender=body.gender,
        contact_number=body.contact_number,
        next_of_kin_name=body.next_of_kin_name,
        next_of_kin_contact=body.next_of_kin_contact,
        folder_number=body.folder_number or "PENDING",
        facility_id=facility.id,
        created_by_id=current_user.id,
    )
    db.add(patient)
    db.flush()

    if body.folder_number is None:
        patient.folder_number = f"F-{patient.id:04d}"

    visit = Visit(
        patient_id=patient.id,
        facility_id=facility.id,
        reason_for_visit=body.reason_for_visit,
        status=VisitStatus.awaiting_triage,
        checked_in_by_id=current_user.id,
    )
    db.add(visit)
    db.flush()

    log_action(db, "patient_registered", current_user, "patient", patient.id,
               f"folder={patient.folder_number} visit={visit.id}")
    log_action(db, "visit_checked_in", current_user, "visit", visit.id,
               f"patient={patient.id} reason={body.reason_for_visit[:80]}")

    db.commit()
    db.refresh(patient)
    db.refresh(visit)

    return PatientVisitResponse(
        **PatientResponse.model_validate(patient).model_dump(),
        visit_id=visit.id,
        reason_for_visit=visit.reason_for_visit,
    )


@router.get("/", response_model=list[PatientResponse])
def list_patients(
    db: DbSession,
    current_user: CurrentUser,
    search: str | None = Query(default=None, description="Search by name or folder number"),
):
    stmt = select(Patient).where(Patient.facility_id == current_user.facility_id)

    if search:
        like = f"%{search}%"
        stmt = stmt.where(
            Patient.first_name.ilike(like)
            | Patient.surname.ilike(like)
            | Patient.folder_number.ilike(like)
        )

    return list(db.scalars(stmt.order_by(Patient.created_at.desc())))
