/**
 * Integration tests for VitalsCaptureForm.
 *
 * These tests render the component in a jsdom environment and simulate real
 * user interactions (typing, clicking) via @testing-library/user-event.
 *
 * Key contract being tested:
 *   - The form calls onSubmit(vitals: VitalsCreate, priority: TriagePriority)
 *     as TWO separate arguments — matching how TriagePage forwards them to the
 *     API as two sequential requests (POST /vitals then PATCH /priority).
 *   - Field labels and names match VITALS_FIELD_CONFIG in triageValidation.ts.
 *   - RED priority requires a second confirmation click before submitting.
 */

import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'

import { VitalsCaptureForm } from './VitalsCaptureForm'
import { DUPLICATE_VITALS_MESSAGE, type TriageQueueItem } from '../types/triage'
import { ApiError } from '../lib/api'

// Minimal TriageQueueItem with all required fields populated.
const patient: TriageQueueItem = {
  visit_id: 42,
  patient_id: 1,
  folder_number: 'F-1001',
  full_name: 'Thabo Mokoena',
  reason_for_visit: 'Fever and cough',
  triage_priority: null,
  checked_in_at: new Date().toISOString(),
  wait_minutes: 18,
  has_vitals: false,
}

// Valid values for all required vitals fields.
const validVitals = {
  bp_systolic: '120',
  bp_diastolic: '80',
  heart_rate: '72',
  temperature: '36.5',
  respiratory_rate: '16',
  oxygen_saturation: '98',
  weight_kg: '70',
}

async function fillAllVitals(
  user: ReturnType<typeof userEvent.setup>,
  overrides: Partial<typeof validVitals> = {},
) {
  const values = { ...validVitals, ...overrides }

  await user.type(screen.getByLabelText(/systolic bp/i), values.bp_systolic)
  await user.type(screen.getByLabelText(/diastolic bp/i), values.bp_diastolic)
  await user.type(screen.getByLabelText(/heart rate/i), values.heart_rate)
  await user.type(screen.getByLabelText(/temperature/i), values.temperature)
  await user.type(screen.getByLabelText(/respiratory rate/i), values.respiratory_rate)
  await user.type(screen.getByLabelText(/oxygen saturation/i), values.oxygen_saturation)
  await user.type(screen.getByLabelText(/weight \(kg\)/i), values.weight_kg)
}

describe('VitalsCaptureForm', () => {
  const onSubmit = vi.fn()
  const onCancel = vi.fn()

  beforeEach(() => {
    onSubmit.mockReset()
    onCancel.mockReset()
  })

  function renderForm() {
    render(
      <VitalsCaptureForm patient={patient} onSubmit={onSubmit} onCancel={onCancel} />,
    )
  }

  it('requires vitals fields and triage priority before submitting', async () => {
    const user = userEvent.setup()
    renderForm()

    await user.click(screen.getByRole('button', { name: /submit vitals/i }))

    // Validation messages come from VITALS_FIELD_CONFIG label names.
    expect(await screen.findByText(/systolic bp \(mmhg\) is required/i)).toBeInTheDocument()
    expect(screen.getByText(/select a triage priority/i)).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
  })

  it('validates numeric ranges for vitals fields', async () => {
    const user = userEvent.setup()
    renderForm()

    // heart_rate min is 20 — value of 5 should fail range validation.
    await fillAllVitals(user, { heart_rate: '5' })
    await user.click(screen.getByRole('button', { name: /^green$/i }))
    await user.click(screen.getByRole('button', { name: /submit vitals/i }))

    expect(
      await screen.findByText(/heart rate \(bpm\) must be between 20 and 300/i),
    ).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
  })

  it('calls onSubmit with (VitalsCreate, TriagePriority) as two separate arguments', async () => {
    const user = userEvent.setup()
    onSubmit.mockResolvedValue(undefined)
    renderForm()

    await fillAllVitals(user)
    await user.click(screen.getByRole('button', { name: /^green$/i }))
    await user.type(screen.getByLabelText(/triage notes/i), 'Stable vitals')
    await user.click(screen.getByRole('button', { name: /submit vitals/i }))

    await waitFor(() => {
      // onSubmit is called with vitals as the first arg and priority as the second.
      // The priority is NOT nested inside the vitals payload — they go to separate
      // API endpoints in TriagePage.handleSubmitVitals.
      expect(onSubmit).toHaveBeenCalledWith(
        {
          bp_systolic: 120,
          bp_diastolic: 80,
          heart_rate: 72,
          temperature: 36.5,
          respiratory_rate: 16,
          oxygen_saturation: 98,
          weight_kg: 70,
        },
        'green',
      )
    })
  })

  it('shows the duplicate vitals message for a 409 response', async () => {
    const user = userEvent.setup()
    onSubmit.mockRejectedValue(
      new ApiError(DUPLICATE_VITALS_MESSAGE, 409, { detail: DUPLICATE_VITALS_MESSAGE }),
    )
    renderForm()

    await fillAllVitals(user)
    await user.click(screen.getByRole('button', { name: /^yellow$/i }))
    await user.click(screen.getByRole('button', { name: /submit vitals/i }))

    expect(await screen.findByRole('alert')).toHaveTextContent(DUPLICATE_VITALS_MESSAGE)
  })

  it('requires a second confirmation click before submitting RED priority', async () => {
    const user = userEvent.setup()
    onSubmit.mockResolvedValue(undefined)
    renderForm()

    await fillAllVitals(user)
    await user.click(screen.getByRole('button', { name: /^red$/i }))
    // First submit click shows the alertdialog — does NOT call onSubmit yet.
    await user.click(screen.getByRole('button', { name: /submit vitals/i }))

    expect(await screen.findByRole('alertdialog')).toHaveTextContent(
      'Confirm emergency priority?',
    )
    expect(onSubmit).not.toHaveBeenCalled()

    // Confirming the dialog then calls onSubmit with the RED priority.
    await user.click(screen.getByRole('button', { name: /confirm emergency priority/i }))

    await waitFor(() => {
      expect(onSubmit).toHaveBeenCalledWith(
        expect.objectContaining({ bp_systolic: 120 }),
        'red',
      )
    })
  })
})
