from datetime import UTC, datetime
from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select
from sqlalchemy.orm import Session, joinedload

from app.api.deps import CurrentUser, DbSession, require_roles
from app.db.models.patient import Patient
from app.db.models.user import StaffRole
from app.db.models.visit import TriagePriority, Visit, VisitStatus
from app.db.models.vitals import Vitals
from app.schemas.triage import TriagePrioritySet, TriageQueueItem, VitalsCreate, VitalsResponse
from app.services.audit_service import log_action

router = APIRouter(tags=["triage"])

NurseUser = Annotated[CurrentUser, Depends(require_roles(StaffRole.nurse, StaffRole.admin))]


@router.get("/queue", response_model=list[TriageQueueItem])
def get_triage_queue(db: DbSession, current_user: NurseUser):
    stmt = (
        select(Visit)
        .options(joinedload(Visit.patient), joinedload(Visit.vitals))
        .where(
            Visit.facility_id == current_user.facility_id,
            Visit.status == VisitStatus.awaiting_triage,
        )
        .order_by(Visit.checked_in_at)
    )
    visits = list(db.scalars(stmt))
    now = datetime.now(UTC)

    return [
        TriageQueueItem(
            visit_id=v.id,
            patient_id=v.patient_id,
            folder_number=v.patient.folder_number,
            full_name=v.patient.full_name,
            reason_for_visit=v.reason_for_visit,
            triage_priority=v.triage_priority,
            checked_in_at=v.checked_in_at,
            wait_minutes=int((now - v.checked_in_at).total_seconds() / 60),
            has_vitals=v.vitals is not None,
        )
        for v in visits
    ]


@router.post("/{visit_id}/vitals", response_model=VitalsResponse, status_code=status.HTTP_201_CREATED)
def record_vitals(visit_id: int, body: VitalsCreate, db: DbSession, current_user: NurseUser):
    visit = db.scalar(
        select(Visit)
        .options(joinedload(Visit.vitals))
        .where(Visit.id == visit_id, Visit.facility_id == current_user.facility_id)
    )
    if visit is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Visit not found.")

    if visit.status not in (VisitStatus.awaiting_triage, VisitStatus.awaiting_consultation):
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail=f"Cannot record vitals for a visit in status '{visit.status.value}'.",
        )

    if visit.vitals is not None:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="Vitals have already been recorded for this visit. Only one set of vitals per visit is allowed.",
        )

    vitals = Vitals(
        visit_id=visit.id,
        recorded_by_id=current_user.id,
        **body.model_dump(),
    )
    db.add(vitals)
    db.flush()

    log_action(db, "vitals_recorded", current_user, "visit", visit.id,
               f"vitals_id={vitals.id}")

    db.commit()
    db.refresh(vitals)
    return vitals


@router.patch("/{visit_id}/priority", response_model=dict)
def set_triage_priority(visit_id: int, body: TriagePrioritySet, db: DbSession, current_user: NurseUser):
    visit = db.scalar(
        select(Visit).where(Visit.id == visit_id, Visit.facility_id == current_user.facility_id)
    )
    if visit is None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Visit not found.")

    if visit.status not in (VisitStatus.awaiting_triage, VisitStatus.awaiting_consultation):
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail=f"Cannot change priority for a visit in status '{visit.status.value}'.",
        )

    visit.triage_priority = body.priority
    visit.triaged_by_id = current_user.id
    visit.triaged_at = datetime.now(UTC)

    # Move to awaiting_consultation once priority is set (idempotent)
    if visit.status == VisitStatus.awaiting_triage:
        visit.status = VisitStatus.awaiting_consultation

    log_action(db, "triage_priority_set", current_user, "visit", visit.id,
               f"priority={body.priority.value}")

    if body.priority == TriagePriority.red:
        log_action(db, "medi_alert", current_user, "visit", visit.id,
                   f"CRITICAL: patient assigned RED priority — immediate attention required")

    db.commit()
    return {"visit_id": visit_id, "priority": body.priority.value, "status": visit.status.value}
