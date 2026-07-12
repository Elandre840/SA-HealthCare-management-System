/**
 * PharmacyPage — medication dispensing workflow.
 *
 * Stage machine
 * -------------
 *   queue    — lists patients awaiting_pharmacy, showing pending/total
 *              prescription counts so the pharmacist can prioritise.
 *   dispense — selected patient view. Fetches the full prescription list and
 *              lets the pharmacist mark each one individually as DISPENSED.
 *              The "Complete visit" button is disabled until all prescriptions
 *              are dispensed, preventing accidental early completion.
 *
 * Dispense flow
 * -------------
 *   PATCH /pharmacy/prescriptions/{id}/dispense — marks one prescription dispensed.
 *   POST  /pharmacy/visits/{id}/complete        — closes the visit. Rejected if
 *     any prescriptions are still PENDING (422 Unprocessable Entity).
 *
 * allDispensed is derived from the local prescriptions state so the button
 * updates immediately on each dispense without a full queue reload.
 */

import { useEffect, useState } from 'react'

import { useAuth } from '../auth/useAuth'
import { PageHeader, HeaderActionGhost } from '../components/PageHeader'
import { ApiError } from '../lib/api'
import type { PharmacyQueueItem, PrescriptionResponse } from '../types/consultation'

type Stage = 'queue' | 'dispense'

export function PharmacyPage() {
  const { api } = useAuth()

  const [stage, setStage] = useState<Stage>('queue')
  const [queue, setQueue] = useState<PharmacyQueueItem[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [loadError, setLoadError] = useState<string | null>(null)
  const [reloadKey, setReloadKey] = useState(0)
  const [successBanner, setSuccessBanner] = useState<string | null>(null)

  // Selected visit
  const [selected, setSelected] = useState<PharmacyQueueItem | null>(null)
  const [prescriptions, setPrescriptions] = useState<PrescriptionResponse[]>([])
  const [rxLoading, setRxLoading] = useState(false)
  const [rxError, setRxError] = useState<string | null>(null)

  // Dispense
  const [dispensingId, setDispensingId] = useState<number | null>(null)
  const [dispenseError, setDispenseError] = useState<string | null>(null)

  // Complete visit
  const [isCompleting, setIsCompleting] = useState(false)
  const [completeError, setCompleteError] = useState<string | null>(null)

  useEffect(() => {
    let active = true
    setIsLoading(true)
    void api
      .getPharmacyQueue()
      .then((data) => { if (active) { setQueue(data); setLoadError(null) } })
      .catch((err: unknown) => {
        if (active) setLoadError(err instanceof ApiError ? err.message : 'Failed to load pharmacy queue.')
      })
      .finally(() => { if (active) setIsLoading(false) })
    return () => { active = false }
  }, [api, reloadKey])

  function handleSelectVisit(item: PharmacyQueueItem) {
    setSelected(item)
    setSuccessBanner(null)
    setDispenseError(null)
    setCompleteError(null)
    setRxError(null)
    setRxLoading(true)
    setStage('dispense')
    void api
      .getVisitPrescriptions(item.visit_id)
      .then((data) => setPrescriptions(data))
      .catch((err: unknown) => {
        setRxError(err instanceof ApiError ? err.message : 'Failed to load prescriptions.')
      })
      .finally(() => setRxLoading(false))
  }

  async function handleDispense(prescriptionId: number) {
    setDispensingId(prescriptionId)
    setDispenseError(null)
    try {
      const updated = await api.dispensePrescription(prescriptionId)
      setPrescriptions((prev) => prev.map((rx) => (rx.id === updated.id ? updated : rx)))
    } catch (err) {
      setDispenseError(err instanceof ApiError ? err.message : 'Failed to dispense.')
    } finally {
      setDispensingId(null)
    }
  }

  async function handleCompleteVisit() {
    if (!selected) return
    setIsCompleting(true)
    setCompleteError(null)
    try {
      await api.completeVisit(selected.visit_id)
      setSuccessBanner(`Visit for ${selected.full_name} marked as complete.`)
      setStage('queue')
      setSelected(null)
      setPrescriptions([])
      setReloadKey((k) => k + 1)
    } catch (err) {
      setCompleteError(err instanceof ApiError ? err.message : 'Failed to complete visit.')
    } finally {
      setIsCompleting(false)
    }
  }

  const allDispensed = prescriptions.length > 0 && prescriptions.every((rx) => rx.dispense_status === 'dispensed')

  function handlePrint() {
    if (!selected || prescriptions.length === 0) return
    const win = window.open('', '_blank', 'width=620,height=820')
    if (!win) return
    const rows = prescriptions
      .map(
        (rx) =>
          `<tr>
            <td style="padding:8px 6px;border-bottom:1px solid #e5e7eb;vertical-align:top">
              <strong>${rx.medication_name}</strong>
            </td>
            <td style="padding:8px 6px;border-bottom:1px solid #e5e7eb;vertical-align:top">
              ${rx.dosage}
            </td>
            <td style="padding:8px 6px;border-bottom:1px solid #e5e7eb;vertical-align:top">
              ${rx.frequency}
            </td>
            <td style="padding:8px 6px;border-bottom:1px solid #e5e7eb;vertical-align:top">
              ${rx.duration}
            </td>
            <td style="padding:8px 6px;border-bottom:1px solid #e5e7eb;text-align:center;vertical-align:top">
              ${rx.quantity}
            </td>
          </tr>`,
      )
      .join('')
    const dateStr = new Date().toLocaleDateString('en-ZA', { dateStyle: 'full' })
    win.document.write(`<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Prescription Slip — ${selected.full_name}</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 32px; max-width: 560px; margin: 0 auto; color: #111; }
    h1 { font-size: 20px; margin: 0 0 4px; }
    .clinic { font-size: 12px; color: #6b7280; margin-bottom: 20px; }
    .meta { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; }
    .meta strong { display: inline-block; min-width: 90px; color: #374151; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    thead th { text-align: left; border-bottom: 2px solid #111; padding: 6px 6px 8px; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #374151; }
    tbody tr:last-child td { border-bottom: none; }
    .footer { margin-top: 32px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #9ca3af; }
    .dispensed { font-size: 11px; color: #059669; }
    @media print { body { padding: 0; } }
  </style>
</head>
<body>
  <h1>Prescription Slip</h1>
  <p class="clinic">SA Healthcare Management System &mdash; Clinical HMS</p>
  <div class="meta">
    <div><strong>Patient:</strong> ${selected.full_name}</div>
    <div><strong>Folder no:</strong> ${selected.folder_number}</div>
    <div><strong>Date:</strong> ${dateStr}</div>
  </div>
  <table>
    <thead>
      <tr>
        <th>Medication</th>
        <th>Dosage</th>
        <th>Frequency</th>
        <th>Duration</th>
        <th style="text-align:center">Qty</th>
      </tr>
    </thead>
    <tbody>${rows}</tbody>
  </table>
  <div class="footer">
    Dispensed by Clinical HMS &mdash; Printed ${new Date().toLocaleString('en-ZA')}
  </div>
  <script>window.onload = function() { window.print(); }<\/script>
</body>
</html>`)
    win.document.close()
  }

  // ── Dispense screen ────────────────────────────────────────────────────────
  if (stage === 'dispense' && selected) {
    return (
      <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <PageHeader
          section="Pharmacy"
          title="Dispense prescriptions"
          subtitle={`${selected.full_name} · Folder ${selected.folder_number}`}
          actions={
            <HeaderActionGhost onClick={() => { setStage('queue'); setSelected(null); setPrescriptions([]) }}>
              Back to queue
            </HeaderActionGhost>
          }
        />
        <div className="p-8">

        {dispenseError ? (
          <p role="alert" className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
            {dispenseError}
          </p>
        ) : null}

        {completeError ? (
          <p role="alert" className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
            {completeError}
          </p>
        ) : null}

        {rxLoading ? (
          <p className="text-sm text-slate-600">Loading prescriptions…</p>
        ) : rxError ? (
          <p role="alert" className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
            {rxError}
          </p>
        ) : prescriptions.length === 0 ? (
          <p className="rounded-lg border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-500">
            No prescriptions for this visit.
          </p>
        ) : (
          <ul className="mb-6 divide-y divide-slate-200 rounded-xl border border-slate-200">
            {prescriptions.map((rx) => {
              const isDispensed = rx.dispense_status === 'dispensed'
              const isDispensing = dispensingId === rx.id
              return (
                <li
                  key={rx.id}
                  className={`flex flex-wrap items-center justify-between gap-4 px-4 py-4 ${isDispensed ? 'bg-emerald-50/40' : ''}`}
                >
                  <div className="text-sm">
                    <p className="font-semibold text-slate-950">{rx.medication_name}</p>
                    <p className="text-slate-600">
                      {rx.dosage} · {rx.frequency} · {rx.duration} · Qty {rx.quantity}
                    </p>
                  </div>
                  {isDispensed ? (
                    <span className="flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                      <svg viewBox="0 0 16 16" fill="currentColor" className="h-3.5 w-3.5">
                        <path fillRule="evenodd" d="M12.416 3.376a.75.75 0 010 1.06l-6 6a.75.75 0 01-1.061 0l-3-3a.75.75 0 111.061-1.06l2.47 2.47 5.47-5.47a.75.75 0 011.06 0z" clipRule="evenodd" />
                      </svg>
                      Dispensed
                    </span>
                  ) : (
                    <button
                      type="button"
                      disabled={isDispensing}
                      onClick={() => void handleDispense(rx.id)}
                      className="rounded-lg bg-teal-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-teal-700 disabled:opacity-50"
                    >
                      {isDispensing ? 'Dispensing…' : 'Mark dispensed'}
                    </button>
                  )}
                </li>
              )
            })}
          </ul>
        )}

        <div className="flex flex-wrap gap-3">
          <button
            type="button"
            disabled={!allDispensed || isCompleting}
            onClick={() => void handleCompleteVisit()}
            title={!allDispensed ? 'Dispense all medications first' : undefined}
            className="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-900 disabled:opacity-40"
          >
            {isCompleting ? 'Completing…' : 'Complete visit'}
          </button>
          {!rxLoading && prescriptions.length > 0 ? (
            <button
              type="button"
              onClick={handlePrint}
              className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
              Print prescription slip
            </button>
          ) : null}
        </div>

        {!allDispensed && prescriptions.length > 0 ? (
          <p className="mt-2 text-xs text-slate-500">
            All medications must be dispensed before you can complete the visit.
          </p>
        ) : null}
        </div>
      </section>
    )
  }

  // ── Pharmacy queue ─────────────────────────────────────────────────────────
  return (
    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <PageHeader
        section="Pharmacy"
        title="Dispensing queue"
        subtitle="Patients awaiting medication dispensing, in order of arrival."
        actions={<HeaderActionGhost onClick={() => setReloadKey((k) => k + 1)}>Refresh</HeaderActionGhost>}
      />
      <div className="p-8">

      {successBanner ? (
        <p role="status" className="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
          {successBanner}
        </p>
      ) : null}

      {isLoading ? (
        <p className="text-sm text-slate-600">Loading pharmacy queue…</p>
      ) : loadError ? (
        <div className="space-y-3">
          <p role="alert" className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
            {loadError}
          </p>
          <button
            type="button"
            onClick={() => setReloadKey((k) => k + 1)}
            className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
          >
            Retry
          </button>
        </div>
      ) : queue.length === 0 ? (
        <p className="rounded-lg border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-600">
          No patients are waiting at pharmacy right now.
        </p>
      ) : (
        <ul className="divide-y divide-slate-200 rounded-xl border border-slate-200">
          {queue.map((item) => (
            <li key={item.visit_id}>
              <button
                type="button"
                onClick={() => handleSelectVisit(item)}
                className="flex w-full flex-wrap items-center justify-between gap-4 px-4 py-4 text-left transition hover:bg-slate-50"
              >
                <div>
                  <p className="font-semibold text-slate-950">{item.full_name}</p>
                  <p className="mt-1 text-sm text-slate-600">Folder {item.folder_number}</p>
                </div>
                <span className="rounded-full bg-violet-50 px-3 py-1 text-sm font-medium text-violet-700">
                  {item.pending_prescriptions}/{item.total_prescriptions} pending
                </span>
              </button>
            </li>
          ))}
        </ul>
      )}
      </div>
    </section>
  )
}
