/**
 * TriagePage — nurse triage queue and vitals capture.
 *
 * When a patient is selected from the queue, the page renders VitalsCaptureForm
 * in place of the queue list. On successful vitals submission:
 *   1. POST /triage/{visit_id}/vitals   — saves the clinical measurements.
 *   2. PATCH /triage/{visit_id}/priority — sets the priority and advances
 *      the visit to awaiting_consultation.
 *
 * The patient is then removed from the local queue state immediately so the
 * nurse does not need to refresh the page. A success banner is shown briefly.
 *
 * reloadKey is an integer state value that increments when the user clicks
 * "Retry". The useEffect depends on it, so incrementing the key triggers a
 * fresh API call without duplicating fetch logic or adding a refetch callback.
 */

import { useEffect, useState } from 'react'

import { useAuth } from '../auth/useAuth'
import { ApiError } from '../lib/api'
import { VitalsCaptureForm } from '../components/VitalsCaptureForm'
import type { TriageQueueItem, TriagePriority, VitalsCreate } from '../types/triage'

function formatWaitTime(minutes: number) {
  if (!Number.isFinite(minutes) || minutes < 0) return '< 1 min'
  if (minutes < 60) return `${minutes} min`
  const hours = Math.floor(minutes / 60)
  const remainingMinutes = minutes % 60
  if (remainingMinutes === 0) return `${hours} hr`
  return `${hours} hr ${remainingMinutes} min`
}

export function TriagePage() {
  const { api } = useAuth()
  const [queue, setQueue] = useState<TriageQueueItem[]>([])
  const [selectedPatient, setSelectedPatient] = useState<TriageQueueItem | null>(null)
  const [successMessage, setSuccessMessage] = useState<string | null>(null)
  const [loadError, setLoadError] = useState<string | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  // reloadKey is incremented by handleRetry. The effect depends on it, so
  // bumping the key is a cheap way to re-fetch the queue without duplicating
  // the fetch logic or exposing a refetch function via a ref.
  const [reloadKey, setReloadKey] = useState(0)

  useEffect(() => {
    // isActive prevents a stale fetch from updating state after the component
    // unmounts or the user navigates away before the response arrives.
    let isActive = true

    void api
      .getTriageQueue()
      .then((items) => {
        if (!isActive) {
          return
        }

        setQueue(items)
        setLoadError(null)
      })
      .catch((error: unknown) => {
        if (!isActive) {
          return
        }

        setLoadError(
          error instanceof ApiError
            ? error.message
            : 'Unable to load the triage queue. Please try again.',
        )
      })
      .finally(() => {
        if (isActive) {
          setIsLoading(false)
        }
      })

    return () => {
      isActive = false
    }
  }, [api, reloadKey])

  function handleRetry() {
    setIsLoading(true)
    setReloadKey((current) => current + 1)
  }

  async function handleSubmitVitals(vitals: VitalsCreate, priority: TriagePriority) {
    if (!selectedPatient) {
      return
    }

    await api.submitVitals(selectedPatient.visit_id, vitals)
    await api.setTriagePriority(selectedPatient.visit_id, priority)

    setQueue((current) =>
      current.filter((item) => item.visit_id !== selectedPatient.visit_id),
    )
    setSelectedPatient(null)
    setSuccessMessage(`Vitals saved for ${selectedPatient.full_name}.`)
  }

  if (selectedPatient) {
    return (
      <VitalsCaptureForm
        patient={selectedPatient}
        onSubmit={(vitals, priority) => handleSubmitVitals(vitals, priority)}
        onCancel={() => setSelectedPatient(null)}
      />
    )
  }

  return (
    <section className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
      <div className="mb-6">
        <p className="text-sm font-semibold uppercase tracking-wide text-teal-700">
          Nurse triage
        </p>
        <h2 className="mt-2 text-3xl font-bold tracking-tight text-slate-950">Triage queue</h2>
        <p className="mt-2 max-w-2xl text-slate-600">
          Patients awaiting vitals capture at your facility, oldest first.
        </p>
      </div>

      {successMessage ? (
        <p
          role="status"
          className="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800"
        >
          {successMessage}
        </p>
      ) : null}

      {isLoading ? (
        <p className="text-sm text-slate-600">Loading triage queue...</p>
      ) : loadError ? (
        <div className="space-y-3">
          <p role="alert" className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
            {loadError}
          </p>
          <button
            type="button"
            onClick={handleRetry}
            className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
          >
            Retry
          </button>
        </div>
      ) : queue.length === 0 ? (
        <p className="rounded-lg border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-600">
          No patients are waiting for triage right now.
        </p>
      ) : (
        <ul className="divide-y divide-slate-200 rounded-xl border border-slate-200">
          {queue.map((patient) => (
            <li key={patient.visit_id}>
              <button
                type="button"
                onClick={() => {
                  setSuccessMessage(null)
                  setSelectedPatient(patient)
                }}
                className="flex w-full flex-wrap items-center justify-between gap-4 px-4 py-4 text-left transition hover:bg-slate-50"
              >
                <div>
                  <p className="font-semibold text-slate-950">{patient.full_name}</p>
                  <p className="mt-1 text-sm text-slate-600">
                    Folder {patient.folder_number} · {patient.reason_for_visit}
                  </p>
                </div>
                <span className="rounded-full bg-amber-50 px-3 py-1 text-sm font-medium text-amber-800">
                  Waiting {formatWaitTime(patient.wait_minutes)}
                </span>
              </button>
            </li>
          ))}
        </ul>
      )}
    </section>
  )
}
