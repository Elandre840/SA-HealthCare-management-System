from datetime import date, datetime

from pydantic import BaseModel, Field


class PatientCreate(BaseModel):
    first_name: str = Field(min_length=1, max_length=100)
    surname: str = Field(min_length=1, max_length=100)
    id_number: str | None = Field(default=None, min_length=13, max_length=13, pattern=r"^\d{13}$")
    date_of_birth: date | None = None
    gender: str | None = Field(default=None, max_length=20)
    contact_number: str | None = Field(default=None, max_length=20)
    next_of_kin_name: str | None = Field(default=None, max_length=200)
    next_of_kin_contact: str | None = Field(default=None, max_length=20)
    folder_number: str | None = Field(default=None, max_length=50)
    reason_for_visit: str = Field(min_length=1)


class PatientResponse(BaseModel):
    id: int
    first_name: str
    surname: str
    id_number: str | None
    date_of_birth: date | None
    gender: str | None
    contact_number: str | None
    next_of_kin_name: str | None
    next_of_kin_contact: str | None
    folder_number: str
    facility_id: int
    created_at: datetime

    model_config = {"from_attributes": True}


class PatientVisitResponse(PatientResponse):
    visit_id: int
    reason_for_visit: str
