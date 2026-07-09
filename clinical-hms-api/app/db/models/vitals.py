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
