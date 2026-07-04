from sqlalchemy import ForeignKey, String
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base


class Patient(Base):
    __tablename__ = "patients"

    id: Mapped[int] = mapped_column(primary_key=True, index=True)
    first_name: Mapped[str] = mapped_column(String(100), nullable=False)
    surname: Mapped[str] = mapped_column(String(100), nullable=False)
    # folder_number is the clinic-issued paper-folder identifier — it is not a
    # national ID number. It is unique within a facility but not globally, so
    # always filter by facility_id when looking up by folder number.
    folder_number: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    facility_id: Mapped[int] = mapped_column(ForeignKey("facilities.id"), nullable=False)

    facility: Mapped["Facility"] = relationship(back_populates="patients")
    visits: Mapped[list["Visit"]] = relationship(back_populates="patient")

    @property
    def full_name(self) -> str:
        return f"{self.first_name} {self.surname}"
