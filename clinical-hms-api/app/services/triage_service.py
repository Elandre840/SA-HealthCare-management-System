from datetime import UTC, datetime

from fastapi import HTTPException, status
from sqlalchemy import select
from sqlalchemy.orm import Session, joinedload

from app.db.models.patient import Patient
from app.db.models.user import User
from app.db.models.visit import Visit, VisitStatus
from app.db.models.vitals import TriagePriority, Vitals
from app.schemas.triage import TriageQueueItem, VitalsCreate, VitalsOut
from app.services.medi_alert_service import trigger_medi_alert

DUPLICATE_VITALS_MESSAGE = "Vitals have already been recorded for this visit."


def _require_facility(user: User) -> int:
    # Every clinical operation is scoped to the nurse/doctor's own facility.
    # A user without a facility_id cannot access any clinical data — this guard
    # must be called at the top of every service function that touches patients,
    # visits, or vitals.
    if user.facility_id is None:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Your account is not assigned to a facility.",
        )
    return user.facility_id


def list_triage_queue(db: Session, user: User) -> list[TriageQueueItem]:
    facility_id = _require_facility(user)
    now = datetime.now(UTC)

    visits = db.scalars(
        select(Visit)
        .options(joinedload(Visit.patient))
        .where(
            Visit.facility_id == facility_id,
            Visit.status == VisitStatus.awaiting_triage,
        )
        # Oldest check-in first so the nurse sees the longest-waiting patient at the top.
        .order_by(Visit.checked_in_at.asc())
    ).all()

    queue: list[TriageQueueItem] = []

    for visit in visits:
        checked_in_at = visit.checked_in_at
        if checked_in_at.tzinfo is None:
            checked_in_at = checked_in_at.replace(tzinfo=UTC)

        wait_minutes = max(0, int((now - checked_in_at).total_seconds() // 60))

        queue.append(
            TriageQueueItem(
                visit_id=visit.id,
                patient_name=visit.patient.full_name,
                folder_number=visit.patient.folder_number,
                reason_for_visit=visit.reason_for_visit,
                wait_time_minutes=wait_minutes,
            )
        )

    return queue


def record_vitals(
    db: Session,
    *,
    visit_id: int,
    vitals_in: VitalsCreate,
    nurse: User,
) -> VitalsOut:
    facility_id = _require_facility(nurse)

    visit = db.scalar(
        select(Visit)
        .options(joinedload(Visit.patient), joinedload(Visit.vitals))
        .where(Visit.id == visit_id)
    )

    if visit is None or visit.facility_id != facility_id:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Visit not found.",
        )

    # Two separate 409 guards protect against duplicate vitals:
    #   1. visit.status != awaiting_triage — catches the case where the visit
    #      moved forward (e.g. another nurse triaged it) between page load and submit.
    #   2. visit.vitals is not None — catches a direct double-POST to this endpoint
    #      where the status update and the vitals insert could theoretically race.
    if visit.status != VisitStatus.awaiting_triage:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail=DUPLICATE_VITALS_MESSAGE,
        )

    if visit.vitals is not None:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail=DUPLICATE_VITALS_MESSAGE,
        )

    vitals = Vitals(
        visit_id=visit.id,
        blood_pressure_systolic=vitals_in.blood_pressure_systolic,
        blood_pressure_diastolic=vitals_in.blood_pressure_diastolic,
        pulse_rate=vitals_in.pulse_rate,
        temperature=vitals_in.temperature,
        respiratory_rate=vitals_in.respiratory_rate,
        oxygen_saturation=vitals_in.oxygen_saturation,
        weight_kg=vitals_in.weight_kg,
        height_cm=vitals_in.height_cm,
        triage_notes=vitals_in.triage_notes,
        triage_priority=TriagePriority(vitals_in.triage_priority),
        recorded_by_id=nurse.id,
    )

    visit.status = VisitStatus.triaged
    db.add(vitals)

    if vitals.triage_priority == TriagePriority.red:
        trigger_medi_alert(
            patient=visit.patient,
            visit_id=visit.id,
            vitals=vitals_in,
            nurse=nurse,
        )

    db.commit()
    db.refresh(vitals)

    return VitalsOut.model_validate(vitals)
