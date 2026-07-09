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
