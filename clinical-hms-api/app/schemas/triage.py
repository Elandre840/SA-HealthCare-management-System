from typing import Literal

from pydantic import BaseModel, ConfigDict, Field


TriagePriorityLiteral = Literal["green", "yellow", "orange", "red"]


class TriageQueueItem(BaseModel):
    visit_id: int
    patient_name: str
    folder_number: str
    reason_for_visit: str
    wait_time_minutes: int


class VitalsCreate(BaseModel):
    # Clinical bounds for the South African Triage Scale (SATS).
    # These ranges reject obviously invalid hardware readings before they reach
    # the database. They are not diagnostic thresholds — normal ranges are much
    # narrower but vary by patient. The nurse assigns the triage_priority.
    blood_pressure_systolic: int = Field(ge=50, le=300)
    blood_pressure_diastolic: int = Field(ge=30, le=200)
    pulse_rate: int = Field(ge=30, le=250)
    temperature: float = Field(ge=34.0, le=42.0)
    respiratory_rate: int = Field(ge=8, le=60)
    oxygen_saturation: int = Field(ge=50, le=100)
    weight_kg: float = Field(ge=0.5, le=500.0)
    height_cm: float = Field(ge=30.0, le=250.0)
    triage_notes: str | None = None
    triage_priority: TriagePriorityLiteral


class VitalsOut(BaseModel):
    id: int
    visit_id: int
    blood_pressure_systolic: int
    blood_pressure_diastolic: int
    pulse_rate: int
    temperature: float
    respiratory_rate: int
    oxygen_saturation: int
    weight_kg: float
    height_cm: float
    triage_notes: str | None
    triage_priority: TriagePriorityLiteral

    model_config = ConfigDict(from_attributes=True)
