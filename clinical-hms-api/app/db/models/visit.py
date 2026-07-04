import enum
from datetime import datetime

from sqlalchemy import DateTime, Enum, ForeignKey, String, func
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base


class VisitStatus(str, enum.Enum):
    # Linear lifecycle for a single visit:
    #   awaiting_triage → triaged (nurse records vitals)
    #                   → with_doctor (doctor claims the visit)
    #                   → completed (consultation finished)
    # A visit should never move backwards. Service code should enforce forward-only
    # transitions and reject unexpected status values with a 409.
    awaiting_triage = "awaiting_triage"
    triaged = "triaged"
    with_doctor = "with_doctor"
    completed = "completed"


class Visit(Base):
    __tablename__ = "visits"

    id: Mapped[int] = mapped_column(primary_key=True, index=True)
    patient_id: Mapped[int] = mapped_column(ForeignKey("patients.id"), nullable=False)
    facility_id: Mapped[int] = mapped_column(ForeignKey("facilities.id"), nullable=False)
    reason_for_visit: Mapped[str] = mapped_column(String(255), nullable=False)
    status: Mapped[VisitStatus] = mapped_column(
        Enum(VisitStatus),
        default=VisitStatus.awaiting_triage,
        nullable=False,
    )
    checked_in_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True),
        server_default=func.now(),
        nullable=False,
    )

    patient: Mapped["Patient"] = relationship(back_populates="visits")
    facility: Mapped["Facility"] = relationship(back_populates="visits")
    # uselist=False enforces the one-vitals-per-visit rule at the ORM level.
    # The database unique constraint on vitals.visit_id is the hard guarantee;
    # the service layer checks visit.vitals is None before inserting.
    vitals: Mapped["Vitals | None"] = relationship(
        back_populates="visit",
        uselist=False,
    )
