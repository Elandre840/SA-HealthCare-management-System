"""
Pharmacy dispensing routes — /api/v1/pharmacy/*

Endpoints
---------
GET  /queue                               — list visits awaiting_pharmacy, with
                                            pending/total prescription counts.
GET  /visits/{visit_id}/prescriptions     — list all prescriptions for a visit.
PATCH /prescriptions/{id}/dispense        — mark one prescription as DISPENSED.
POST /visits/{visit_id}/complete          — mark the visit COMPLETED.
                                            Rejected if any prescription is still PENDING
                                            (prevents accidentally completing an incomplete dispense).

The pharmacist must dispense every prescription individually before the "complete
visit" button becomes available. This intentional step prevents partial dispensing
and ensures each medication is physically handed to the patient before the visit
is closed.
"""

from datetime import UTC, datetime
from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select
from sqlalchemy.orm import joinedload

from app.api.deps import CurrentUser, DbSession, require_roles
from app.db.models.prescription import DispenseStatus, Prescription
from app.db.models.user import StaffRole
from app.db.models.visit import Visit, VisitStatus
from app.schemas.prescription import PrescriptionResponse
from app.services.audit_service import log_action

router = APIRouter(tags=["pharmacy"])

PharmacistUser = Annotated[CurrentUser, Depends(require_roles(StaffRole.pharmacist, StaffRole.admin))]


@router.get("/queue", response_model=list[dict])
def get_pharmacy_queue(db: DbSession, current_user: PharmacistUser):
    stmt = (
        select(Visit)
        .options(joinedload(Visit.patient), joinedload(Visit.prescriptions))
        .where(
            Visit.facility_id == current_user.facility_id,
            Visit.status == VisitStatus.awaiting_pharmacy,
        )
        .order_by(Visit.checked_in_at)
    )
    visits = list(db.scalars(stmt).unique())
    return [
        {
            "visit_id": v.id,
            "patient_id": v.patient_id,
            "folder_number": v.patient.folder_number,
            "full_name": v.patient.full_name,
            "pending_prescriptions": sum(
                1 for p in v.prescriptions if p.dispense_status == DispenseStatus.pending
            ),
            "total_prescriptions": len(v.prescriptions),
        }
        for v in visits
    ]


@router.get("/visits/{visit_id}/prescriptions", response_model=list[PrescriptionResponse])
def get_visit_prescriptions(visit_id: int, db: DbSession, current_user: PharmacistUser):
    visit = db.scalar(
        select(Visit)
        .options(joinedload(Visit.prescriptions))
        .where(Visit.id == visit_id, Visit.facility_id == current_user.facility_id)
    )
    if visit is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Visit not found.")
    return visit.prescriptions


@router.patch("/prescriptions/{prescription_id}/dispense", response_model=PrescriptionResponse)
def dispense_prescription(prescription_id: int, db: DbSession, current_user: PharmacistUser):
    prescription = db.scalar(
        select(Prescription)
        .options(joinedload(Prescription.visit))
        .where(Prescription.id == prescription_id)
    )
    if prescription is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Prescription not found.")

    if prescription.visit.facility_id != current_user.facility_id:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Prescription not found.")

    if prescription.dispense_status == DispenseStatus.dispensed:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="This prescription has already been dispensed.",
        )

    prescription.dispense_status = DispenseStatus.dispensed
    prescription.dispensed_by_id = current_user.id
    prescription.dispensed_at = datetime.now(UTC)

    log_action(db, "prescription_dispensed", current_user, "prescription", prescription.id,
               f"medication={prescription.medication_name} visit={prescription.visit_id}")

    db.commit()
    db.refresh(prescription)
    return prescription


@router.post("/visits/{visit_id}/complete", response_model=dict)
def complete_visit(visit_id: int, db: DbSession, current_user: PharmacistUser):
    visit = db.scalar(
        select(Visit)
        .options(joinedload(Visit.prescriptions))
        .where(Visit.id == visit_id, Visit.facility_id == current_user.facility_id)
    )
    if visit is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Visit not found.")

    if visit.status != VisitStatus.awaiting_pharmacy:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail=f"Visit is in status '{visit.status.value}', expected 'awaiting_pharmacy'.",
        )

    pending = [p for p in visit.prescriptions if p.dispense_status == DispenseStatus.pending]
    if pending:
        names = ", ".join(p.medication_name for p in pending)
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail=f"Cannot complete visit — {len(pending)} prescription(s) still pending: {names}.",
        )

    visit.status = VisitStatus.completed
    visit.completed_at = datetime.now(UTC)

    log_action(db, "visit_completed", current_user, "visit", visit.id)

    db.commit()
    return {"visit_id": visit_id, "status": visit.status.value}
