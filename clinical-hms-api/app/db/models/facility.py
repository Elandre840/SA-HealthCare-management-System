from sqlalchemy import String
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base


class Facility(Base):
    __tablename__ = "facilities"

    id: Mapped[int] = mapped_column(primary_key=True, index=True)
    province: Mapped[str] = mapped_column(String(100), nullable=False)
    city: Mapped[str] = mapped_column(String(100), nullable=False)
    name: Mapped[str] = mapped_column(String(150), nullable=False)

    users: Mapped[list["User"]] = relationship(back_populates="facility")
    patients: Mapped[list["Patient"]] = relationship(back_populates="facility")
    visits: Mapped[list["Visit"]] = relationship(back_populates="facility")
