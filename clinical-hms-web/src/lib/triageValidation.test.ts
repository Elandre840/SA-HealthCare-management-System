/**
 * Unit tests for validateVitalsForm.
 *
 * These tests verify the pure validation logic in isolation — no React rendering
 * or API calls involved. Field names must exactly match the VITALS_FIELD_CONFIG
 * keys (bp_systolic, bp_diastolic, heart_rate, etc.) which in turn match the
 * API VitalsCreate schema.
 */

import { EMPTY_VITALS_FORM, validateVitalsForm } from './triageValidation'

describe('validateVitalsForm', () => {
  it('returns a valid payload and priority when all required fields are provided', () => {
    const result = validateVitalsForm({
      ...EMPTY_VITALS_FORM,
      bp_systolic: '120',
      bp_diastolic: '80',
      heart_rate: '72',
      temperature: '36.5',
      respiratory_rate: '16',
      oxygen_saturation: '98',
      weight_kg: '70',
      triage_priority: 'orange',
    })

    // Priority is returned as a separate field, not inside the vitals payload.
    // The frontend submits them as two separate API calls:
    //   POST /triage/{id}/vitals    ← receives the payload object
    //   PATCH /triage/{id}/priority ← receives the priority string
    expect(result).toEqual({
      valid: true,
      payload: {
        bp_systolic: 120,
        bp_diastolic: 80,
        heart_rate: 72,
        temperature: 36.5,
        respiratory_rate: 16,
        oxygen_saturation: 98,
        weight_kg: 70,
      },
      priority: 'orange',
    })
  })

  it('returns valid with weight_kg null when weight is left empty (optional field)', () => {
    const result = validateVitalsForm({
      ...EMPTY_VITALS_FORM,
      bp_systolic: '120',
      bp_diastolic: '80',
      heart_rate: '72',
      temperature: '36.5',
      respiratory_rate: '16',
      oxygen_saturation: '98',
      weight_kg: '',
      triage_priority: 'green',
    })

    expect(result).toMatchObject({
      valid: true,
      payload: expect.objectContaining({ weight_kg: null }),
      priority: 'green',
    })
  })

  it('returns errors when required fields are empty', () => {
    const result = validateVitalsForm(EMPTY_VITALS_FORM)

    expect(result).toMatchObject({ valid: false })
    if (!result.valid) {
      expect(result.errors.bp_systolic).toMatch(/required/i)
      expect(result.errors.triage_priority).toMatch(/select a triage priority/i)
    }
  })

  it('returns an error when a field value is out of the allowed clinical range', () => {
    const result = validateVitalsForm({
      ...EMPTY_VITALS_FORM,
      bp_systolic: '120',
      bp_diastolic: '80',
      heart_rate: '5',   // below min of 20 bpm
      temperature: '36.5',
      respiratory_rate: '16',
      oxygen_saturation: '98',
      triage_priority: 'yellow',
    })

    expect(result).toMatchObject({ valid: false })
    if (!result.valid) {
      expect(result.errors.heart_rate).toMatch(/must be between/i)
    }
  })
})
