import enum
from datetime import datetime

from sqlalchemy import DateTime, Enum, ForeignKey, String, Text, func
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base


class AccountType(str, enum.Enum):
    staff = "staff"
    patient = "patient"


class StaffRole(str, enum.Enum):
    admin = "admin"
    reception = "reception"
    nurse = "nurse"
    doctor = "doctor"
    pharmacist = "pharmacist"


class User(Base):
    __tablename__ = "users"

    id: Mapped[int] = mapped_column(primary_key=True, index=True)
    account_type: Mapped[AccountType] = mapped_column(Enum(AccountType), nullable=False)
    first_name: Mapped[str] = mapped_column(String(100), nullable=False)
    surname: Mapped[str] = mapped_column(String(100), nullable=False)
    id_number: Mapped[str | None] = mapped_column(String(50))
    phone: Mapped[str | None] = mapped_column(String(20))
    email: Mapped[str] = mapped_column(String(150), unique=True, index=True, nullable=False)
    hashed_password: Mapped[str] = mapped_column(String(255), nullable=False)
    employee_number: Mapped[str | None] = mapped_column(String(50), unique=True)
    role: Mapped[StaffRole | None] = mapped_column(Enum(StaffRole))
    status: Mapped[str] = mapped_column(String(50), default="Waiting", nullable=False)
    department: Mapped[str] = mapped_column(String(50), default="Reception", nullable=False)
    notes: Mapped[str | None] = mapped_column(Text)
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now(), nullable=False
    )

    facility_id: Mapped[int | None] = mapped_column(ForeignKey("facilities.id"))
    facility: Mapped["Facility | None"] = relationship(back_populates="users")

    @property
    def full_name(self) -> str:
        return f"{self.first_name} {self.surname}"
