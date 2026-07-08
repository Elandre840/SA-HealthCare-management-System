from datetime import datetime

from pydantic import BaseModel, Field

from app.db.models.visit import TriagePriority


class VitalsCreate(BaseModel):
    temperature: float | None = Field(default=None, ge=30.0, le=45.0)
    bp_systolic: int | None = Field(default=None, ge=50, le=300)
    bp_diastolic: int | None = Field(default=None, ge=30, le=200)
    heart_rate: int | None = Field(default=None, ge=20, le=300)
    oxygen_saturation: int | None = Field(default=None, ge=0, le=100)
    respiratory_rate: int | None = Field(default=None, ge=0, le=100)
    weight_kg: float | None = Field(default=None, ge=0.5, le=500.0)


class VitalsResponse(BaseModel):
    id: int
    visit_id: int
    temperature: float | None
    bp_systolic: int | None
    bp_diastolic: int | None
    heart_rate: int | None
    oxygen_saturation: int | None
    respiratory_rate: int | None
    weight_kg: float | None
    recorded_by_id: int
    recorded_at: datetime

    model_config = {"from_attributes": True}


class TriagePrioritySet(BaseModel):
    priority: TriagePriority


class TriageQueueItem(BaseModel):
    visit_id: int
    patient_id: int
    folder_number: str
    full_name: str
    reason_for_visit: str
    triage_priority: TriagePriority | None
    checked_in_at: datetime
    wait_minutes: int
    has_vitals: bool

    model_config = {"from_attributes": True}
