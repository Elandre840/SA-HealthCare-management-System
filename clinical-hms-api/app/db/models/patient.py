from datetime import date, datetime

from sqlalchemy import Date, DateTime, ForeignKey, String, func
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base


class Patient(Base):
    __tablename__ = "patients"

    id: Mapped[int] = mapped_column(primary_key=True, index=True)
    first_name: Mapped[str] = mapped_column(String(100), nullable=False)
    surname: Mapped[str] = mapped_column(String(100), nullable=False)
    id_number: Mapped[str | None] = mapped_column(String(13), unique=True)
    date_of_birth: Mapped[date | None] = mapped_column(Date)
    gender: Mapped[str | None] = mapped_column(String(20))
    contact_number: Mapped[str | None] = mapped_column(String(20))
    next_of_kin_name: Mapped[str | None] = mapped_column(String(200))
    next_of_kin_contact: Mapped[str | None] = mapped_column(String(20))
    folder_number: Mapped[str] = mapped_column(String(50), unique=True, index=True, nullable=False)
    facility_id: Mapped[int] = mapped_column(ForeignKey("facilities.id"), nullable=False)
    created_by_id: Mapped[int | None] = mapped_column(ForeignKey("users.id"))
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now(), nullable=False
    )

    facility: Mapped["Facility"] = relationship()  # type: ignore[name-defined]
    created_by: Mapped["User | None"] = relationship(foreign_keys=[created_by_id])  # type: ignore[name-defined]
    visits: Mapped[list["Visit"]] = relationship(back_populates="patient")  # type: ignore[name-defined]

    @property
    def full_name(self) -> str:
        return f"{self.first_name} {self.surname}"
