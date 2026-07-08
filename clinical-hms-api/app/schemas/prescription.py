from datetime import datetime

from pydantic import BaseModel, Field

from app.db.models.prescription import DispenseStatus


class PrescriptionCreate(BaseModel):
    medication_name: str = Field(min_length=1, max_length=200)
    dosage: str = Field(min_length=1, max_length=100)
    frequency: str = Field(min_length=1, max_length=100)
    duration: str = Field(min_length=1, max_length=100)
    quantity: int = Field(ge=1, le=9999)


class PrescriptionResponse(BaseModel):
    id: int
    consultation_id: int
    visit_id: int
    medication_name: str
    dosage: str
    frequency: str
    duration: str
    quantity: int
    dispense_status: DispenseStatus
    dispensed_by_id: int | None
    dispensed_at: datetime | None
    created_at: datetime

    model_config = {"from_attributes": True}
