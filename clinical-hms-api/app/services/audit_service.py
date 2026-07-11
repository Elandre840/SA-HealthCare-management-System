"""
POPIA-compliant audit logging service.

Every write operation on patient data calls log_action() before the enclosing
db.commit() so that the audit entry and the data change are committed atomically.
If the transaction rolls back, the audit entry rolls back with it — there are
never phantom audit entries for changes that did not actually happen.

Call pattern in route handlers:
  log_action(db, "patient_registered", current_user, "patient", patient.id,
             f"folder={patient.folder_number}")
  db.commit()

The 'details' string is free-form but should be short and machine-readable so
it can be parsed for compliance reports.
"""

from sqlalchemy.orm import Session

from app.db.models.audit_log import AuditLog
from app.db.models.user import User


def log_action(
    db: Session,
    action: str,
    actor: User,
    entity_type: str,
    entity_id: int,
    details: str | None = None,
) -> None:
    db.add(
        AuditLog(
            action=action,
            actor_id=actor.id,
            actor_role=actor.role.value if actor.role else None,
            entity_type=entity_type,
            entity_id=entity_id,
            details=details,
        )
    )
