export type TriagePriority = 'green' | 'yellow' | 'orange' | 'red'

export type TriageQueueItem = {
  visit_id: number
  patient_name: string
  folder_number: string
  reason_for_visit: string
  wait_time_minutes: number
}

export type VitalsCreate = {
  blood_pressure_systolic: number
  blood_pressure_diastolic: number
  pulse_rate: number
  temperature: number
  respiratory_rate: number
  oxygen_saturation: number
  weight_kg: number
  height_cm: number
  triage_notes: string | null
  triage_priority: TriagePriority
}

export const DUPLICATE_VITALS_MESSAGE =
  'Vitals have already been recorded for this visit.'
