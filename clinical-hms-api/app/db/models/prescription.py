"""
Prescription model — medications prescribed during a consultation.

A doctor can add multiple prescriptions to a single consultation while the
visit is in_consultation status. Each prescription starts in PENDING state.

Dispense flow (pharmacy module):
  1. Pharmacist views all PENDING prescriptions for the visit.
  2. Pharmacist marks each one DISPENSED via PATCH /pharmacy/prescriptions/{id}/dispense.
  3. Once all prescriptions are DISPENSED, the pharmacist posts to
     /pharmacy/visits/{id}/complete, which moves the visit to COMPLETED.

The "complete visit" endpoint rejects the request if any prescription is still
PENDING, preventing accidental early completion.
"""

import enum
from datetime import datetime

from sqlalchemy import DateTime, Enum, ForeignKey, Integer, String, func
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base


class DispenseStatus(str, enum.Enum):
    pending = "pending"
    dispensed = "dispensed"


class Prescription(Base):
    __tablename__ = "prescriptions"

    id: Mapped[int] = mapped_column(primary_key=True, index=True)
    consultation_id: Mapped[int] = mapped_column(ForeignKey("consultations.id"), nullable=False)
    visit_id: Mapped[int] = mapped_column(ForeignKey("visits.id"), nullable=False)
    medication_name: Mapped[str] = mapped_column(String(200), nullable=False)
    dosage: Mapped[str] = mapped_column(String(100), nullable=False)    # e.g. "500mg"
    frequency: Mapped[str] = mapped_column(String(100), nullable=False) # e.g. "twice daily"
    duration: Mapped[str] = mapped_column(String(100), nullable=False)  # e.g. "7 days"
    quantity: Mapped[int] = mapped_column(Integer, nullable=False)
    dispense_status: Mapped[DispenseStatus] = mapped_column(
        Enum(DispenseStatus), default=DispenseStatus.pending, nullable=False
    )
    dispensed_by_id: Mapped[int | None] = mapped_column(ForeignKey("users.id"))
    dispensed_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now(), nullable=False
    )

    consultation: Mapped["Consultation"] = relationship(back_populates="prescriptions")  # type: ignore[name-defined]
    visit: Mapped["Visit"] = relationship(back_populates="prescriptions")  # type: ignore[name-defined]
    dispensed_by: Mapped["User | None"] = relationship()  # type: ignore[name-defined]
