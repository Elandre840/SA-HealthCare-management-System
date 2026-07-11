/**
 * Triage domain types — mirrors the backend triage schemas.
 *
 * TriagePriority represents the South African Triage Scale (SATS) colour
 * categories. The order in TRIAGE_PRIORITIES (lib/triageValidation.ts) matches
 * clinical severity from least to most critical.
 *
 * VitalsCreate field names must exactly match the API VitalsCreate Pydantic
 * schema (app/schemas/triage.py). A mismatch causes a 422 Validation Error
 * from FastAPI.
 */

export type TriagePriority = 'green' | 'yellow' | 'orange' | 'red'

export type TriageQueueItem = {
  visit_id: number
  patient_id: number
  folder_number: string
  full_name: string
  reason_for_visit: string
  triage_priority: TriagePriority | null
  checked_in_at: string
  wait_minutes: number
  has_vitals: boolean
}

// Matches the API VitalsCreate schema exactly.
// Triage priority is set separately via PATCH /triage/{visit_id}/priority.
export type VitalsCreate = {
  temperature: number | null
  bp_systolic: number | null
  bp_diastolic: number | null
  heart_rate: number | null
  oxygen_saturation: number | null
  respiratory_rate: number | null
  weight_kg: number | null
}

export const DUPLICATE_VITALS_MESSAGE =
  'Vitals have already been recorded for this visit.'
