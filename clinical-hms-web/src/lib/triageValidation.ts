import type { TriagePriority, VitalsCreate } from '../types/triage'

export type VitalsFieldKey = keyof Omit<VitalsCreate, 'triage_notes' | 'triage_priority'>

type NumericFieldConfig = {
  label: string
  min: number
  max: number
  step?: number
}

// Bounds aligned with backend VitalsCreate Pydantic constraints.
export const VITALS_FIELD_CONFIG: Record<VitalsFieldKey, NumericFieldConfig> = {
  blood_pressure_systolic: { label: 'Systolic BP (mmHg)', min: 50, max: 300 },
  blood_pressure_diastolic: { label: 'Diastolic BP (mmHg)', min: 30, max: 200 },
  pulse_rate: { label: 'Pulse rate (bpm)', min: 30, max: 250 },
  temperature: { label: 'Temperature (°C)', min: 34, max: 42, step: 0.1 },
  respiratory_rate: { label: 'Respiratory rate (/min)', min: 8, max: 60 },
  oxygen_saturation: { label: 'Oxygen saturation (%)', min: 50, max: 100 },
  weight_kg: { label: 'Weight (kg)', min: 0.5, max: 500, step: 0.1 },
  height_cm: { label: 'Height (cm)', min: 30, max: 250, step: 0.1 },
}

export const TRIAGE_PRIORITIES: TriagePriority[] = ['green', 'yellow', 'orange', 'red']

export type VitalsFormValues = Record<VitalsFieldKey, string> & {
  triage_notes: string
  triage_priority: TriagePriority | ''
}

export const EMPTY_VITALS_FORM: VitalsFormValues = {
  blood_pressure_systolic: '',
  blood_pressure_diastolic: '',
  pulse_rate: '',
  temperature: '',
  respiratory_rate: '',
  oxygen_saturation: '',
  weight_kg: '',
  height_cm: '',
  triage_notes: '',
  triage_priority: '',
}

export type VitalsValidationResult =
  | { valid: true; payload: VitalsCreate }
  | { valid: false; errors: Partial<Record<keyof VitalsFormValues, string>> }

function parseNumericField(
  value: string,
  config: NumericFieldConfig,
): { valid: true; value: number } | { valid: false; message: string } {
  const trimmed = value.trim()

  if (!trimmed) {
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
  const parsedValues: Partial<Record<VitalsFieldKey, number>> = {}

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
      blood_pressure_systolic: parsedValues.blood_pressure_systolic!,
      blood_pressure_diastolic: parsedValues.blood_pressure_diastolic!,
      pulse_rate: parsedValues.pulse_rate!,
      temperature: parsedValues.temperature!,
      respiratory_rate: parsedValues.respiratory_rate!,
      oxygen_saturation: parsedValues.oxygen_saturation!,
      weight_kg: parsedValues.weight_kg!,
      height_cm: parsedValues.height_cm!,
      triage_notes: values.triage_notes.trim() ? values.triage_notes.trim() : null,
      triage_priority: values.triage_priority,
    },
  }
}
