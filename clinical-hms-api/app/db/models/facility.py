"""
Facility model — the multi-tenancy anchor for the entire system.

Every patient, visit, vitals record, and staff account belongs to exactly one
facility. Route handlers enforce this by filtering all queries on
`current_user.facility_id` rather than accepting a facility_id from the
request body, which would allow a user to read or write data from another clinic.
"""

from sqlalchemy import String
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base


class Facility(Base):
    __tablename__ = "facilities"

    # Province and city allow multiple facilities per city without name collisions.
    id: Mapped[int] = mapped_column(primary_key=True, index=True)
    province: Mapped[str] = mapped_column(String(100), nullable=False)
    city: Mapped[str] = mapped_column(String(100), nullable=False)
    name: Mapped[str] = mapped_column(String(150), nullable=False)

    users: Mapped[list["User"]] = relationship(back_populates="facility")
    patients: Mapped[list["Patient"]] = relationship(back_populates="facility")
    visits: Mapped[list["Visit"]] = relationship(back_populates="facility")
