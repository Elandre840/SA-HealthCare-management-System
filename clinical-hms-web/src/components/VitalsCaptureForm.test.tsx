import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'

import { VitalsCaptureForm } from './VitalsCaptureForm'
import { DUPLICATE_VITALS_MESSAGE, type TriageQueueItem } from '../types/triage'
import { ApiError } from '../lib/api'

const patient: TriageQueueItem = {
  visit_id: 42,
  patient_name: 'Thabo Mokoena',
  folder_number: 'F-1001',
  reason_for_visit: 'Fever and cough',
  wait_time_minutes: 18,
}

const validVitals = {
  blood_pressure_systolic: '120',
  blood_pressure_diastolic: '80',
  pulse_rate: '72',
  temperature: '36.5',
  respiratory_rate: '16',
  oxygen_saturation: '98',
  weight_kg: '70',
  height_cm: '170',
}

async function fillAllVitals(user: ReturnType<typeof userEvent.setup>, overrides: Partial<typeof validVitals> = {}) {
  const values = { ...validVitals, ...overrides }

  await user.type(screen.getByLabelText(/systolic bp/i), values.blood_pressure_systolic)
  await user.type(screen.getByLabelText(/diastolic bp/i), values.blood_pressure_diastolic)
  await user.type(screen.getByLabelText(/pulse rate/i), values.pulse_rate)
  await user.type(screen.getByLabelText(/temperature/i), values.temperature)
  await user.type(screen.getByLabelText(/respiratory rate/i), values.respiratory_rate)
  await user.type(screen.getByLabelText(/oxygen saturation/i), values.oxygen_saturation)
  await user.type(screen.getByLabelText(/weight \(kg\)/i), values.weight_kg)
  await user.type(screen.getByLabelText(/height \(cm\)/i), values.height_cm)
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

    expect(await screen.findByText(/systolic bp \(mmhg\) is required/i)).toBeInTheDocument()
    expect(screen.getByText(/select a triage priority/i)).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
  })

  it('validates numeric ranges for vitals fields', async () => {
    const user = userEvent.setup()
    renderForm()

    await fillAllVitals(user, { pulse_rate: '10' })
    await user.click(screen.getByRole('button', { name: /^green$/i }))
    await user.click(screen.getByRole('button', { name: /submit vitals/i }))

    expect(
      await screen.findByText(/pulse rate \(bpm\) must be between 30 and 250/i),
    ).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
  })

  it('posts a VitalsCreate-shaped payload', async () => {
    const user = userEvent.setup()
    onSubmit.mockResolvedValue(undefined)
    renderForm()

    await fillAllVitals(user)
    await user.click(screen.getByRole('button', { name: /^green$/i }))
    await user.type(screen.getByLabelText(/triage notes/i), 'Stable vitals')
    await user.click(screen.getByRole('button', { name: /submit vitals/i }))

    await waitFor(() => {
      expect(onSubmit).toHaveBeenCalledWith({
        blood_pressure_systolic: 120,
        blood_pressure_diastolic: 80,
        pulse_rate: 72,
        temperature: 36.5,
        respiratory_rate: 16,
        oxygen_saturation: 98,
        weight_kg: 70,
        height_cm: 170,
        triage_notes: 'Stable vitals',
        triage_priority: 'green',
      })
    })
  })

  it('shows the duplicate vitals message for a 409 response', async () => {
    const user = userEvent.setup()
    onSubmit.mockRejectedValue(new ApiError(DUPLICATE_VITALS_MESSAGE, 409, { detail: DUPLICATE_VITALS_MESSAGE }))
    renderForm()

    await fillAllVitals(user)
    await user.click(screen.getByRole('button', { name: /^yellow$/i }))
    await user.click(screen.getByRole('button', { name: /submit vitals/i }))

    expect(await screen.findByRole('alert')).toHaveTextContent(DUPLICATE_VITALS_MESSAGE)
  })

  it('requires confirmation before posting RED priority vitals', async () => {
    const user = userEvent.setup()
    onSubmit.mockResolvedValue(undefined)
    renderForm()

    await fillAllVitals(user)
    await user.click(screen.getByRole('button', { name: /^red$/i }))
    await user.click(screen.getByRole('button', { name: /submit vitals/i }))

    expect(await screen.findByRole('alertdialog')).toHaveTextContent(
      'Confirm emergency priority?',
    )
    expect(onSubmit).not.toHaveBeenCalled()

    await user.click(screen.getByRole('button', { name: /confirm emergency priority/i }))

    await waitFor(() => {
      expect(onSubmit).toHaveBeenCalledWith(
        expect.objectContaining({ triage_priority: 'red' }),
      )
    })
  })
})
