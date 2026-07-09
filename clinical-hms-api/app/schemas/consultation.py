from datetime import datetime

from pydantic import BaseModel, Field

from app.schemas.prescription import PrescriptionResponse


class ConsultationCreate(BaseModel):
    visit_id: int
    chief_complaint: str = Field(min_length=1)
    diagnosis_text: str | None = None
    icd10_code: str | None = Field(default=None, max_length=20)
    notes: str | None = None


class ConsultationAmend(BaseModel):
    chief_complaint: str | None = None
    diagnosis_text: str | None = None
    icd10_code: str | None = Field(default=None, max_length=20)
    notes: str | None = None
    amendment_reason: str = Field(min_length=1)


class ConsultationClose(BaseModel):
    diagnosis_text: str = Field(min_length=1)
    icd10_code: str | None = Field(default=None, max_length=20)
    notes: str | None = None


class ConsultationResponse(BaseModel):
    id: int
    visit_id: int
    doctor_id: int
    chief_complaint: str
    diagnosis_text: str | None
    icd10_code: str | None
    notes: str | None
    amendment_reason: str | None
    created_at: datetime
    updated_at: datetime
    prescriptions: list[PrescriptionResponse] = []

    model_config = {"from_attributes": True}
