"""
Pydantic schema for audit log read responses.

AuditLogEntry mirrors the AuditLog ORM model and is the response shape for
GET /api/v1/audit-logs/. It is read-only — audit entries are written only by
audit_service.log_action() inside route handlers, never via the API.
"""

from datetime import datetime

from pydantic import BaseModel


class AuditLogEntry(BaseModel):
    id: int
    action: str
    actor_id: int | None
    actor_role: str | None
    entity_type: str | None
    entity_id: int | None
    details: str | None
    timestamp: datetime

    model_config = {"from_attributes": True}
