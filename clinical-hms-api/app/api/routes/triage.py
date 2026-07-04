from fastapi import APIRouter, status

from app.api.deps import DbSession, NurseUser
from app.schemas.triage import TriageQueueItem, VitalsCreate, VitalsOut
from app.services.triage_service import list_triage_queue, record_vitals


router = APIRouter(prefix="/api/v1/triage", tags=["triage"])

# Both endpoints use NurseUser, which resolves to require_roles(StaffRole.nurse).
# Only a logged-in nurse can view or update the triage queue — a 403 is returned
# for any other role. Extend this with doctor access when the consultation
# module is added.
@router.get("/queue", response_model=list[TriageQueueItem])
def get_triage_queue(db: DbSession, current_user: NurseUser) -> list[TriageQueueItem]:
    return list_triage_queue(db, current_user)


@router.post("/{visit_id}/vitals", response_model=VitalsOut, status_code=status.HTTP_201_CREATED)
def post_visit_vitals(
    visit_id: int,
    vitals_in: VitalsCreate,
    db: DbSession,
    current_user: NurseUser,
) -> VitalsOut:
    return record_vitals(db, visit_id=visit_id, vitals_in=vitals_in, nurse=current_user)
