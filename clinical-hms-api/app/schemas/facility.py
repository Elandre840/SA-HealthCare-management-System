from pydantic import BaseModel, ConfigDict


class FacilityCreate(BaseModel):
    province: str
    city: str
    name: str


class FacilityOut(FacilityCreate):
    id: int

    model_config = ConfigDict(from_attributes=True)
