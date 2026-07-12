/**
 * Patient domain types — mirrors the backend patient schemas.
 *
 * PatientCreate is the registration form payload. Optional fields are sent as
 * null (not undefined) so the JSON body includes them and the API can
 * distinguish "not provided" from "empty string".
 *
 * PatientVisitResponse extends PatientResponse with the visit_id and reason
 * that are returned by the registration endpoint. The frontend uses the
 * visit_id to display the success card after registration.
 */

export type PatientCreate = {
  first_name: string
  surname: string
  id_number?: string | null
  date_of_birth?: string | null
  gender?: string | null
  contact_number?: string | null
  next_of_kin_name?: string | null
  next_of_kin_contact?: string | null
  folder_number?: string | null
  reason_for_visit: string
}

export type PatientResponse = {
  id: number
  first_name: string
  surname: string
  id_number: string | null
  date_of_birth: string | null
  gender: string | null
  contact_number: string | null
  next_of_kin_name: string | null
  next_of_kin_contact: string | null
  folder_number: string
  facility_id: number
  created_at: string
}

export type PatientVisitResponse = PatientResponse & {
  visit_id: number
  reason_for_visit: string
}
