from datetime import UTC, datetime
from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select
from sqlalchemy.orm import joinedload

from app.api.deps import CurrentUser, DbSession, require_roles
from app.db.models.consultation import Consultation
from app.db.models.prescription import DispenseStatus, Prescription
from app.db.models.user import StaffRole
from app.db.models.visit import Visit, VisitStatus
from app.schemas.consultation import (
    ConsultationAmend,
    ConsultationClose,
    ConsultationCreate,
    ConsultationResponse,
)
from app.schemas.prescription import PrescriptionCreate, PrescriptionResponse
from app.services.audit_service import log_action

router = APIRouter(tags=["consultations"])

DoctorUser = Annotated[CurrentUser, Depends(require_roles(StaffRole.doctor, StaffRole.admin))]


@router.get("/queue", response_model=list[dict])
def get_consultation_queue(db: DbSession, current_user: DoctorUser):
    stmt = (
        select(Visit)
        .options(joinedload(Visit.patient))
        .where(
            Visit.facility_id == current_user.facility_id,
            Visit.status == VisitStatus.awaiting_consultation,
        )
        .order_by(Visit.triaged_at, Visit.checked_in_at)
    )
    visits = list(db.scalars(stmt))
    return [
        {
            "visit_id": v.id,
            "patient_id": v.patient_id,
            "folder_number": v.patient.folder_number,
            "full_name": v.patient.full_name,
            "reason_for_visit": v.reason_for_visit,
            "triage_priority": v.triage_priority.value if v.triage_priority else None,
            "triaged_at": v.triaged_at,
        }
        for v in visits
    ]


@router.post("/", response_model=ConsultationResponse, status_code=status.HTTP_201_CREATED)
def open_consultation(body: ConsultationCreate, db: DbSession, current_user: DoctorUser):
    visit = db.scalar(
        select(Visit)
        .options(joinedload(Visit.consultation))
        .where(Visit.id == body.visit_id, Visit.facility_id == current_user.facility_id)
    )
    if visit is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Visit not found.")

    if visit.status != VisitStatus.awaiting_consultation:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail=f"Visit is in status '{visit.status.value}', expected 'awaiting_consultation'.",
        )

    if visit.consultation is not None:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="A consultation already exists for this visit.",
        )

    consultation = Consultation(
        visit_id=visit.id,
        doctor_id=current_user.id,
        chief_complaint=body.chief_complaint,
        diagnosis_text=body.diagnosis_text,
        icd10_code=body.icd10_code,
        notes=body.notes,
    )
    db.add(consultation)
    visit.status = VisitStatus.in_consultation
    visit.doctor_id = current_user.id
    db.flush()

    log_action(db, "consultation_opened", current_user, "consultation", consultation.id,
               f"visit={visit.id}")

    db.commit()
    db.refresh(consultation)
    return consultation


@router.get("/{consultation_id}", response_model=ConsultationResponse)
def get_consultation(consultation_id: int, db: DbSession, current_user: CurrentUser):
    consultation = db.scalar(
        select(Consultation)
        .options(joinedload(Consultation.prescriptions))
        .where(Consultation.id == consultation_id)
    )
    if consultation is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Consultation not found.")
    return consultation


@router.patch("/{consultation_id}", response_model=ConsultationResponse)
def amend_consultation(
    consultation_id: int, body: ConsultationAmend, db: DbSession, current_user: DoctorUser
):
    consultation = db.scalar(
        select(Consultation)
        .options(joinedload(Consultation.prescriptions))
        .where(Consultation.id == consultation_id)
    )
    if consultation is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Consultation not found.")

    if body.chief_complaint is not None:
        consultation.chief_complaint = body.chief_complaint
    if body.diagnosis_text is not None:
        consultation.diagnosis_text = body.diagnosis_text
    if body.icd10_code is not None:
        consultation.icd10_code = body.icd10_code
    if body.notes is not None:
        consultation.notes = body.notes

    consultation.amendment_reason = body.amendment_reason
    consultation.updated_at = datetime.now(UTC)

    log_action(db, "consultation_amended", current_user, "consultation", consultation.id,
               f"reason={body.amendment_reason[:100]}")

    db.commit()
    db.refresh(consultation)
    return consultation


@router.post("/{consultation_id}/prescriptions", response_model=PrescriptionResponse, status_code=status.HTTP_201_CREATED)
def add_prescription(
    consultation_id: int, body: PrescriptionCreate, db: DbSession, current_user: DoctorUser
):
    consultation = db.get(Consultation, consultation_id)
    if consultation is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Consultation not found.")

    visit = db.get(Visit, consultation.visit_id)
    if visit is None or visit.facility_id != current_user.facility_id:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Visit not found.")

    if visit.status != VisitStatus.in_consultation:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="Prescriptions can only be added while the visit is in_consultation.",
        )

    prescription = Prescription(
        consultation_id=consultation.id,
        visit_id=visit.id,
        **body.model_dump(),
    )
    db.add(prescription)
    db.flush()

    log_action(db, "prescription_added", current_user, "prescription", prescription.id,
               f"medication={body.medication_name} visit={visit.id}")

    db.commit()
    db.refresh(prescription)
    return prescription


@router.post("/{consultation_id}/close", response_model=dict)
def close_consultation(
    consultation_id: int, body: ConsultationClose, db: DbSession, current_user: DoctorUser
):
    consultation = db.scalar(
        select(Consultation)
        .options(joinedload(Consultation.prescriptions), joinedload(Consultation.visit))
        .where(Consultation.id == consultation_id)
    )
    if consultation is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Consultation not found.")

    visit = consultation.visit
    if visit.facility_id != current_user.facility_id:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Visit not found.")

    if visit.status != VisitStatus.in_consultation:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail=f"Visit is in status '{visit.status.value}', expected 'in_consultation'.",
        )

    consultation.diagnosis_text = body.diagnosis_text
    if body.icd10_code is not None:
        consultation.icd10_code = body.icd10_code
    if body.notes is not None:
        consultation.notes = body.notes
    consultation.updated_at = datetime.now(UTC)

    pending = [p for p in consultation.prescriptions if p.dispense_status == DispenseStatus.pending]
    visit.status = VisitStatus.awaiting_pharmacy if pending else VisitStatus.completed
    if not pending:
        visit.completed_at = datetime.now(UTC)

    log_action(db, "consultation_closed", current_user, "consultation", consultation.id,
               f"next_status={visit.status.value}")

    db.commit()
    return {
        "consultation_id": consultation_id,
        "visit_status": visit.status.value,
        "pending_prescriptions": len(pending),
    }
