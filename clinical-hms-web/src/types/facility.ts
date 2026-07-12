/**
 * Facility domain types — mirrors the backend facility schemas.
 */

export type FacilityCreate = {
  province: string
  city: string
  name: string
}

export type FacilityResponse = FacilityCreate & {
  id: number
}
