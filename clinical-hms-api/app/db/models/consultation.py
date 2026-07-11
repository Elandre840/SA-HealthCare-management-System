"""
Consultation model — the doctor's clinical record for a visit.

Like Vitals, the unique constraint on visit_id enforces one consultation per
visit at the database level. Doctors can amend an open consultation (PATCH)
to correct errors; every amendment requires a reason, which is stored
alongside the updated fields to maintain a clear audit trail.

icd10_code stores the International Classification of Diseases (ICD-10) code
assigned by the doctor, e.g. "J06.9" (acute upper respiratory infection).
This is optional at open time but should be completed before closing.
"""

from datetime import datetime

from sqlalchemy import DateTime, ForeignKey, String, Text, func
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base


class Consultation(Base):
    __tablename__ = "consultations"

    id: Mapped[int] = mapped_column(primary_key=True, index=True)
    # unique=True — one consultation per visit; amend in place rather than creating a second
    visit_id: Mapped[int] = mapped_column(ForeignKey("visits.id"), nullable=False, unique=True)
    doctor_id: Mapped[int] = mapped_column(ForeignKey("users.id"), nullable=False)
    chief_complaint: Mapped[str] = mapped_column(Text, nullable=False)
    diagnosis_text: Mapped[str | None] = mapped_column(Text)
    icd10_code: Mapped[str | None] = mapped_column(String(20))
    notes: Mapped[str | None] = mapped_column(Text)
    amendment_reason: Mapped[str | None] = mapped_column(Text)
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now(), nullable=False
    )
    updated_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now(), nullable=False
    )

    visit: Mapped["Visit"] = relationship(back_populates="consultation")  # type: ignore[name-defined]
    doctor: Mapped["User"] = relationship()  # type: ignore[name-defined]
    prescriptions: Mapped[list["Prescription"]] = relationship(back_populates="consultation")  # type: ignore[name-defined]
