import enum
from datetime import datetime

from sqlalchemy import DateTime, Enum, Float, ForeignKey, Integer, String, Text, func
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base


class TriagePriority(str, enum.Enum):
    # South African Triage Scale (SATS) colour codes:
    #   green  — minor / non-urgent  (can wait > 4 hours)
    #   yellow — urgent              (seen within 1 hour)
    #   orange — very urgent         (seen within 10 minutes)
    #   red    — immediate / critical (resuscitation needed)
    # A red priority triggers a MediAlert notification to alert senior staff.
    green = "green"
    yellow = "yellow"
    orange = "orange"
    red = "red"


class Vitals(Base):
    __tablename__ = "vitals"

    id: Mapped[int] = mapped_column(primary_key=True, index=True)
    # unique=True enforces one vitals record per visit at the database level,
    # matching the ORM-level uselist=False on Visit.vitals.
    visit_id: Mapped[int] = mapped_column(
        ForeignKey("visits.id"),
        unique=True,
        nullable=False,
    )
    blood_pressure_systolic: Mapped[int] = mapped_column(Integer, nullable=False)
    blood_pressure_diastolic: Mapped[int] = mapped_column(Integer, nullable=False)
    pulse_rate: Mapped[int] = mapped_column(Integer, nullable=False)
    temperature: Mapped[float] = mapped_column(Float, nullable=False)
    respiratory_rate: Mapped[int] = mapped_column(Integer, nullable=False)
    oxygen_saturation: Mapped[int] = mapped_column(Integer, nullable=False)
    weight_kg: Mapped[float] = mapped_column(Float, nullable=False)
    height_cm: Mapped[float] = mapped_column(Float, nullable=False)
    triage_notes: Mapped[str | None] = mapped_column(Text)
    triage_priority: Mapped[TriagePriority] = mapped_column(Enum(TriagePriority), nullable=False)
    recorded_by_id: Mapped[int] = mapped_column(ForeignKey("users.id"), nullable=False)
    recorded_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True),
        server_default=func.now(),
        nullable=False,
    )

    visit: Mapped["Visit"] = relationship(back_populates="vitals")
    recorded_by: Mapped["User"] = relationship()
