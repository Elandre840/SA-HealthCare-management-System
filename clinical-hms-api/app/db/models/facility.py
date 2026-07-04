from sqlalchemy import String
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base


class Facility(Base):
    __tablename__ = "facilities"

    # Facility is the multi-tenancy anchor for the entire system. Every patient,
    # visit, and vitals record is scoped to a facility. Service functions must
    # always filter by facility_id using the logged-in user's facility_id —
    # never trust a facility_id sent from the client.
    id: Mapped[int] = mapped_column(primary_key=True, index=True)
    province: Mapped[str] = mapped_column(String(100), nullable=False)
    city: Mapped[str] = mapped_column(String(100), nullable=False)
    name: Mapped[str] = mapped_column(String(150), nullable=False)

    users: Mapped[list["User"]] = relationship(back_populates="facility")
    patients: Mapped[list["Patient"]] = relationship(back_populates="facility")
    visits: Mapped[list["Visit"]] = relationship(back_populates="facility")
