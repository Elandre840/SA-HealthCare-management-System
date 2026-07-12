import { useState, type FormEvent } from 'react'

import { PageHeader, HeaderActionGhost } from './PageHeader'
import {
  EMPTY_VITALS_FORM,
  TRIAGE_PRIORITIES,
  VITALS_FIELD_CONFIG,
  validateVitalsForm,
  type VitalsFormValues,
} from '../lib/triageValidation'
import { ApiError } from '../lib/api'
import { DUPLICATE_VITALS_MESSAGE, type TriagePriority, type TriageQueueItem, type VitalsCreate } from '../types/triage'

const PRIORITY_STYLES = {
  green: {
    selected: 'border-triage-green bg-triage-green text-white ring-2 ring-triage-green ring-offset-2',
    idle: 'border-triage-green/40 bg-triage-green/10 text-triage-green hover:bg-triage-green/20',
  },
  yellow: {
    selected: 'border-triage-yellow bg-triage-yellow text-white ring-2 ring-triage-yellow ring-offset-2',
    idle: 'border-triage-yellow/40 bg-triage-yellow/10 text-triage-yellow hover:bg-triage-yellow/20',
  },
  orange: {
    selected: 'border-triage-orange bg-triage-orange text-white ring-2 ring-triage-orange ring-offset-2',
    idle: 'border-triage-orange/40 bg-triage-orange/10 text-triage-orange hover:bg-triage-orange/20',
  },
  red: {
    selected: 'border-triage-red bg-triage-red text-white ring-2 ring-triage-red ring-offset-2',
    idle: 'border-triage-red/40 bg-triage-red/10 text-triage-red hover:bg-triage-red/20',
  },
} as const

type VitalsCaptureFormProps = {
  patient: TriageQueueItem
  onSubmit: (vitals: VitalsCreate, priority: TriagePriority) => Promise<void>
  onCancel: () => void
}

export function VitalsCaptureForm({ patient, onSubmit, onCancel }: VitalsCaptureFormProps) {
  const [values, setValues] = useState<VitalsFormValues>(EMPTY_VITALS_FORM)
  const [fieldErrors, setFieldErrors] = useState<Partial<Record<keyof VitalsFormValues, string>>>(
    {},
  )
  const [submitError, setSubmitError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [showRedConfirmation, setShowRedConfirmation] = useState(false)

  function updateField<K extends keyof VitalsFormValues>(field: K, value: VitalsFormValues[K]) {
    setValues((current) => ({ ...current, [field]: value }))
    setFieldErrors((current) => {
      if (!current[field]) {
        return current
      }

      const next = { ...current }
      delete next[field]
      return next
    })
  }

  async function submitVitals(payload: VitalsCreate, priority: TriagePriority) {
    setSubmitError(null)
    setIsSubmitting(true)

    try {
      await onSubmit(payload, priority)
    } catch (error) {
      if (error instanceof ApiError && error.status === 409) {
        setSubmitError(DUPLICATE_VITALS_MESSAGE)
      } else {
        setSubmitError(
          error instanceof ApiError
            ? error.message
            : 'Unable to save vitals. Please try again.',
        )
      }
    } finally {
      setIsSubmitting(false)
      setShowRedConfirmation(false)
    }
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    const validation = validateVitalsForm(values)

    if (!validation.valid) {
      setFieldErrors(validation.errors)
      return
    }

    // RED priority triggers a MediAlert (emergency escalation). A confirmation
    // step is shown before submitting so the nurse cannot accidentally assign
    // the highest priority without an explicit second action.
    if (validation.priority === 'red' && !showRedConfirmation) {
      setShowRedConfirmation(true)
      return
    }

    await submitVitals(validation.payload, validation.priority)
  }

  return (
    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <PageHeader
        section={`Vitals capture · Folder ${patient.folder_number}`}
        title={patient.full_name}
        subtitle={patient.reason_for_visit}
        actions={<HeaderActionGhost onClick={onCancel}>Back to queue</HeaderActionGhost>}
      />
      <div className="p-8">

      <form className="space-y-6" onSubmit={handleSubmit} noValidate>
        <div className="grid gap-4 sm:grid-cols-2">
          {(Object.entries(VITALS_FIELD_CONFIG) as [keyof typeof VITALS_FIELD_CONFIG, (typeof VITALS_FIELD_CONFIG)[keyof typeof VITALS_FIELD_CONFIG]][]).map(
            ([field, config]) => (
              <div key={field}>
                <label htmlFor={field} className="block text-sm font-medium text-slate-700">
                  {config.label}
                </label>
                <input
                  id={field}
                  name={field}
                  type="number"
                  step={config.step ?? 1}
                  min={config.min}
                  max={config.max}
                  value={values[field]}
                  onChange={(event) => updateField(field, event.target.value)}
                  className="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-950 shadow-sm outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
                />
                {fieldErrors[field] ? (
                  <p className="mt-1 text-sm text-red-700">{fieldErrors[field]}</p>
                ) : null}
              </div>
            ),
          )}
        </div>

        <div>
          <label htmlFor="triage_notes" className="block text-sm font-medium text-slate-700">
            Triage notes
          </label>
          <textarea
            id="triage_notes"
            name="triage_notes"
            rows={3}
            value={values.triage_notes}
            onChange={(event) => updateField('triage_notes', event.target.value)}
            className="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-950 shadow-sm outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
            placeholder="Optional clinical observations"
          />
        </div>

        <fieldset>
          <legend className="block text-sm font-medium text-slate-700">Triage priority</legend>
          <div className="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {TRIAGE_PRIORITIES.map((priority) => {
              const isSelected = values.triage_priority === priority
              const styles = PRIORITY_STYLES[priority]

              return (
                <button
                  key={priority}
                  type="button"
                  aria-pressed={isSelected}
                  onClick={() => updateField('triage_priority', priority)}
                  className={`rounded-lg border px-4 py-3 text-sm font-semibold uppercase tracking-wide transition ${
                    isSelected ? styles.selected : styles.idle
                  }`}
                >
                  {priority}
                </button>
              )
            })}
          </div>
          {fieldErrors.triage_priority ? (
            <p className="mt-2 text-sm text-red-700">{fieldErrors.triage_priority}</p>
          ) : null}
        </fieldset>

        {showRedConfirmation ? (
          <div
            role="alertdialog"
            aria-labelledby="red-confirm-title"
            aria-describedby="red-confirm-description"
            className="rounded-lg border border-triage-red/30 bg-triage-red/5 p-4"
          >
            <h3 id="red-confirm-title" className="text-sm font-semibold text-triage-red">
              Confirm emergency priority?
            </h3>
            <p id="red-confirm-description" className="mt-1 text-sm text-slate-700">
              RED priority triggers an emergency MediAlert. Confirm only if this patient needs
              immediate escalation.
            </p>
            <div className="mt-4 flex flex-wrap gap-3">
              <button
                type="submit"
                disabled={isSubmitting}
                className="rounded-lg bg-triage-red px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-70"
              >
                {isSubmitting ? 'Submitting...' : 'Confirm emergency priority'}
              </button>
              <button
                type="button"
                onClick={() => setShowRedConfirmation(false)}
                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
              >
                Cancel
              </button>
            </div>
          </div>
        ) : null}

        {submitError ? (
          <p role="alert" className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
            {submitError}
          </p>
        ) : null}

        {!showRedConfirmation ? (
          <button
            type="submit"
            disabled={isSubmitting}
            className="rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-70"
          >
            {isSubmitting ? 'Submitting vitals...' : 'Submit vitals'}
          </button>
        ) : null}
      </form>
      </div>
    </section>
  )
}
