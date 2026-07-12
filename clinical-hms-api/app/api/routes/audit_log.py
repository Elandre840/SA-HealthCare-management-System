"""
Audit log routes — /api/v1/audit-logs/*

GET / — list audit log entries ordered newest-first (admin only).
        Supports ?skip, ?limit, ?action, and ?entity_type query params.
        Returns at most 500 entries per request to keep responses manageable.
"""

from fastapi import APIRouter, Query
from sqlalchemy import select

from app.api.deps import AdminUser, DbSession
from app.db.models.audit_log import AuditLog
from app.schemas.audit_log import AuditLogEntry

router = APIRouter(tags=["audit-logs"])


@router.get("/", response_model=list[AuditLogEntry])
def list_audit_logs(
    _: AdminUser,
    db: DbSession,
    skip: int = Query(default=0, ge=0),
    limit: int = Query(default=200, ge=1, le=500),
    action: str | None = Query(default=None, description="Filter by exact action name"),
    entity_type: str | None = Query(default=None, description="Filter by entity type"),
) -> list[AuditLog]:
    stmt = select(AuditLog)
    if action:
        stmt = stmt.where(AuditLog.action == action)
    if entity_type:
        stmt = stmt.where(AuditLog.entity_type == entity_type)
    stmt = stmt.order_by(AuditLog.timestamp.desc()).offset(skip).limit(limit)
    return list(db.scalars(stmt))
