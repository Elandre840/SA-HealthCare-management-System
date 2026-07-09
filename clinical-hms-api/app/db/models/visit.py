import enum
from datetime import datetime

from sqlalchemy import DateTime, Enum, ForeignKey, Text, func
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base


class VisitStatus(str, enum.Enum):
    awaiting_triage = "awaiting_triage"
    awaiting_consultation = "awaiting_consultation"
    in_consultation = "in_consultation"
    awaiting_pharmacy = "awaiting_pharmacy"
    completed = "completed"


class TriagePriority(str, enum.Enum):
    green = "green"    # non-urgent
    yellow = "yellow"  # semi-urgent
    orange = "orange"  # urgent
    red = "red"        # critical — triggers MediAlert


class Visit(Base):
    __tablename__ = "visits"

    id: Mapped[int] = mapped_column(primary_key=True, index=True)
    patient_id: Mapped[int] = mapped_column(ForeignKey("patients.id"), nullable=False)
    facility_id: Mapped[int] = mapped_column(ForeignKey("facilities.id"), nullable=False)
    status: Mapped[VisitStatus] = mapped_column(
        Enum(VisitStatus), nullable=False, default=VisitStatus.awaiting_triage
    )
    reason_for_visit: Mapped[str] = mapped_column(Text, nullable=False)
    triage_priority: Mapped[TriagePriority | None] = mapped_column(Enum(TriagePriority))
    checked_in_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now(), nullable=False
    )
    checked_in_by_id: Mapped[int | None] = mapped_column(ForeignKey("users.id"))
    triaged_by_id: Mapped[int | None] = mapped_column(ForeignKey("users.id"))
    triaged_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    doctor_id: Mapped[int | None] = mapped_column(ForeignKey("users.id"))
    completed_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))

    patient: Mapped["Patient"] = relationship(back_populates="visits")  # type: ignore[name-defined]
    facility: Mapped["Facility"] = relationship()  # type: ignore[name-defined]
    checked_in_by: Mapped["User | None"] = relationship(foreign_keys=[checked_in_by_id])  # type: ignore[name-defined]
    triaged_by: Mapped["User | None"] = relationship(foreign_keys=[triaged_by_id])  # type: ignore[name-defined]
    doctor: Mapped["User | None"] = relationship(foreign_keys=[doctor_id])  # type: ignore[name-defined]
    vitals: Mapped["Vitals | None"] = relationship(back_populates="visit", uselist=False)  # type: ignore[name-defined]
    consultation: Mapped["Consultation | None"] = relationship(back_populates="visit", uselist=False)  # type: ignore[name-defined]
    prescriptions: Mapped[list["Prescription"]] = relationship(back_populates="visit")  # type: ignore[name-defined]
