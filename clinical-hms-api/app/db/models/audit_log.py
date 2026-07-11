"""
Audit log model for POPIA-compliant action tracking.

The Protection of Personal Information Act (POPIA) requires that systems
handling health data maintain a record of who accessed or modified patient
information and when. Every clinical action (registration, triage, dispensing,
etc.) writes an immutable row to this table via audit_service.log_action().

Records are append-only by convention — the application never deletes or
updates audit rows. Retention policy and archiving are out of scope for this
version but the indexed timestamp column makes range-based queries efficient.
"""

from datetime import datetime

from sqlalchemy import DateTime, ForeignKey, Integer, String, Text, func
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base


class AuditLog(Base):
    __tablename__ = "audit_log"

    id: Mapped[int] = mapped_column(primary_key=True, index=True)
    action: Mapped[str] = mapped_column(String(100), nullable=False, index=True)
    actor_id: Mapped[int | None] = mapped_column(ForeignKey("users.id"))
    actor_role: Mapped[str | None] = mapped_column(String(50))
    entity_type: Mapped[str | None] = mapped_column(String(50))
    entity_id: Mapped[int | None] = mapped_column(Integer)
    details: Mapped[str | None] = mapped_column(Text)
    timestamp: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now(), nullable=False, index=True
    )

    actor: Mapped["User | None"] = relationship()  # type: ignore[name-defined]
