import type { TriagePriority, VitalsCreate } from '../types/triage'

export type VitalsFieldKey = keyof VitalsCreate

type NumericFieldConfig = {
  label: string
  min: number
  max: number
  step?: number
  optional?: boolean
}

// Field names match the API VitalsCreate schema (app/schemas/triage.py).
export const VITALS_FIELD_CONFIG: Record<VitalsFieldKey, NumericFieldConfig> = {
  bp_systolic: { label: 'Systolic BP (mmHg)', min: 50, max: 300 },
  bp_diastolic: { label: 'Diastolic BP (mmHg)', min: 30, max: 200 },
  heart_rate: { label: 'Heart rate (bpm)', min: 20, max: 300 },
  temperature: { label: 'Temperature (°C)', min: 30, max: 45, step: 0.1 },
  respiratory_rate: { label: 'Respiratory rate (/min)', min: 0, max: 100 },
  oxygen_saturation: { label: 'Oxygen saturation (%)', min: 0, max: 100 },
  weight_kg: { label: 'Weight (kg)', min: 0.5, max: 500, step: 0.1, optional: true },
}

export const TRIAGE_PRIORITIES: TriagePriority[] = ['green', 'yellow', 'orange', 'red']

export type VitalsFormValues = Record<VitalsFieldKey, string> & {
  triage_notes: string
  triage_priority: TriagePriority | ''
}

export const EMPTY_VITALS_FORM: VitalsFormValues = {
  bp_systolic: '',
  bp_diastolic: '',
  heart_rate: '',
  temperature: '',
  respiratory_rate: '',
  oxygen_saturation: '',
  weight_kg: '',
  triage_notes: '',
  triage_priority: '',
}

export type VitalsValidationResult =
  | { valid: true; payload: VitalsCreate; priority: TriagePriority }
  | { valid: false; errors: Partial<Record<keyof VitalsFormValues, string>> }

function parseNumericField(
  value: string,
  config: NumericFieldConfig,
): { valid: true; value: number | null } | { valid: false; message: string } {
  const trimmed = value.trim()

  if (!trimmed) {
    if (config.optional) return { valid: true, value: null }
    return { valid: false, message: `${config.label} is required.` }
  }

  const parsed = Number(trimmed)

  if (!Number.isFinite(parsed)) {
    return { valid: false, message: `${config.label} must be a number.` }
  }

  if (parsed < config.min || parsed > config.max) {
    return {
      valid: false,
      message: `${config.label} must be between ${config.min} and ${config.max}.`,
    }
  }

  return { valid: true, value: parsed }
}

export function validateVitalsForm(values: VitalsFormValues): VitalsValidationResult {
  const errors: Partial<Record<keyof VitalsFormValues, string>> = {}
  const parsedValues: Partial<Record<VitalsFieldKey, number | null>> = {}

  for (const [field, config] of Object.entries(VITALS_FIELD_CONFIG) as [
    VitalsFieldKey,
    NumericFieldConfig,
  ][]) {
    const result = parseNumericField(values[field], config)

    if (!result.valid) {
      errors[field] = result.message
    } else {
      parsedValues[field] = result.value
    }
  }

  if (!values.triage_priority) {
    errors.triage_priority = 'Select a triage priority.'
  }

  if (Object.keys(errors).length > 0 || !values.triage_priority) {
    return { valid: false, errors }
  }

  return {
    valid: true,
    payload: {
      temperature: parsedValues.temperature ?? null,
      bp_systolic: parsedValues.bp_systolic ?? null,
      bp_diastolic: parsedValues.bp_diastolic ?? null,
      heart_rate: parsedValues.heart_rate ?? null,
      oxygen_saturation: parsedValues.oxygen_saturation ?? null,
      respiratory_rate: parsedValues.respiratory_rate ?? null,
      weight_kg: parsedValues.weight_kg ?? null,
    },
    priority: values.triage_priority,
  }
}
