from pydantic import BaseModel, ConfigDict, EmailStr

from app.db.models.user import AccountType, StaffRole


class UserCreate(BaseModel):
    account_type: AccountType
    first_name: str
    surname: str
    email: EmailStr
    password: str
    facility_id: int | None = None
    id_number: str | None = None
    phone: str | None = None
    employee_number: str | None = None
    role: StaffRole | None = None
    department: str = "Reception"


class UserOut(BaseModel):
    id: int
    account_type: AccountType
    first_name: str
    surname: str
    full_name: str
    email: EmailStr
    role: StaffRole | None
    facility_id: int | None
    status: str
    department: str

    model_config = ConfigDict(from_attributes=True)
