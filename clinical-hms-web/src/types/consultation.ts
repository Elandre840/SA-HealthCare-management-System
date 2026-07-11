/**
 * Consultation and pharmacy domain types.
 *
 * These types mirror the backend response shapes exactly. Keeping them in a
 * dedicated file (rather than inlining them in each page) means a single edit
 * here propagates TypeScript errors to every consumer if the API contract
 * changes.
 *
 * Relationship summary:
 *   ConsultationQueueItem — lightweight summary shown in the doctor's queue.
 *   ConsultationResponse  — full record including nested PrescriptionResponse[].
 *   PharmacyQueueItem     — lightweight summary shown in the pharmacy queue.
 *   PrescriptionResponse  — individual medication line with dispense_status.
 */

export type ConsultationQueueItem = {
  visit_id: number
  patient_id: number
  folder_number: string
  full_name: string
  reason_for_visit: string
  triage_priority: 'green' | 'yellow' | 'orange' | 'red' | null
  triaged_at: string | null
}

export type PrescriptionCreate = {
  medication_name: string
  dosage: string
  frequency: string
  duration: string
  quantity: number
}

export type PrescriptionResponse = {
  id: number
  consultation_id: number
  visit_id: number
  medication_name: string
  dosage: string
  frequency: string
  duration: string
  quantity: number
  dispense_status: 'pending' | 'dispensed'
  dispensed_by_id: number | null
  dispensed_at: string | null
  created_at: string
}

export type ConsultationCreate = {
  visit_id: number
  chief_complaint: string
  diagnosis_text?: string | null
  icd10_code?: string | null
  notes?: string | null
}

export type ConsultationResponse = {
  id: number
  visit_id: number
  doctor_id: number
  chief_complaint: string
  diagnosis_text: string | null
  icd10_code: string | null
  notes: string | null
  amendment_reason: string | null
  created_at: string
  updated_at: string
  prescriptions: PrescriptionResponse[]
}

export type ConsultationClose = {
  diagnosis_text: string
  icd10_code?: string | null
  notes?: string | null
}

export type PharmacyQueueItem = {
  visit_id: number
  patient_id: number
  folder_number: string
  full_name: string
  pending_prescriptions: number
  total_prescriptions: number
}
