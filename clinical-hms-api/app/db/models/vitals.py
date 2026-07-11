"""
Vitals model — one record per visit.

The database-level unique constraint on visit_id guarantees that a visit can
never have more than one set of vitals even under concurrent requests. The
triage route returns HTTP 409 if a second POST is attempted, allowing the
frontend to show a clear "already recorded" message rather than a 500 error.

All measurements are stored as nullable so the nurse can record partial vitals
(e.g. weight is optional) without the insert failing. Clinical validation of
acceptable ranges is enforced in the Pydantic schema (app/schemas/triage.py).
"""

from datetime import datetime

from sqlalchemy import DateTime, Float, ForeignKey, Integer, func
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base


class Vitals(Base):
    __tablename__ = "vitals"

    id: Mapped[int] = mapped_column(primary_key=True, index=True)
    # unique=True enforces one vitals record per visit at the database level
    visit_id: Mapped[int] = mapped_column(ForeignKey("visits.id"), nullable=False, unique=True)
    temperature: Mapped[float | None] = mapped_column(Float)      # degrees Celsius
    bp_systolic: Mapped[int | None] = mapped_column(Integer)      # mmHg
    bp_diastolic: Mapped[int | None] = mapped_column(Integer)     # mmHg
    heart_rate: Mapped[int | None] = mapped_column(Integer)       # bpm
    oxygen_saturation: Mapped[int | None] = mapped_column(Integer) # %
    respiratory_rate: Mapped[int | None] = mapped_column(Integer)  # breaths/min
    weight_kg: Mapped[float | None] = mapped_column(Float)
    recorded_by_id: Mapped[int] = mapped_column(ForeignKey("users.id"), nullable=False)
    recorded_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now(), nullable=False
    )

    visit: Mapped["Visit"] = relationship(back_populates="vitals")  # type: ignore[name-defined]
    recorded_by: Mapped["User"] = relationship()  # type: ignore[name-defined]
