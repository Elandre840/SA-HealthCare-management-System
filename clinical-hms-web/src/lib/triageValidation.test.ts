import { EMPTY_VITALS_FORM, validateVitalsForm } from './triageValidation'

describe('validateVitalsForm', () => {
  it('returns a VitalsCreate payload with null notes when notes are blank', () => {
    const result = validateVitalsForm({
      ...EMPTY_VITALS_FORM,
      blood_pressure_systolic: '120',
      blood_pressure_diastolic: '80',
      pulse_rate: '72',
      temperature: '36.5',
      respiratory_rate: '16',
      oxygen_saturation: '98',
      weight_kg: '70',
      height_cm: '170',
      triage_priority: 'orange',
    })

    expect(result).toEqual({
      valid: true,
      payload: {
        blood_pressure_systolic: 120,
        blood_pressure_diastolic: 80,
        pulse_rate: 72,
        temperature: 36.5,
        respiratory_rate: 16,
        oxygen_saturation: 98,
        weight_kg: 70,
        height_cm: 170,
        triage_notes: null,
        triage_priority: 'orange',
      },
    })
  })
})
