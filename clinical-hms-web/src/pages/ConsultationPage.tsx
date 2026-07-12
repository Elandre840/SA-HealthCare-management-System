/**
 * ConsultationPage — doctor's consultation workflow.
 *
 * Stage machine
 * -------------
 *   queue   — lists patients awaiting_consultation at the doctor's facility.
 *   open    — doctor selects a patient; form to capture chief complaint before
 *             opening the consultation (POST /consultations/).
 *   consult — active consultation view. Doctor can:
 *               • Add prescriptions (POST /consultations/{id}/prescriptions).
 *               • Close the consultation with a diagnosis
 *                 (POST /consultations/{id}/close).
 *
 * On close, the API decides the next visit status:
 *   • pending prescriptions → awaiting_pharmacy (pharmacy queue picks it up)
 *   • no prescriptions      → completed (visit is done)
 *
 * The closed result card summarises the outcome and returns the doctor to the
 * queue with the next patient already visible.
 */

import { useEffect, useState } from 'react'

import { useAuth } from '../auth/useAuth'
import { PageHeader, HeaderActionGhost } from '../components/PageHeader'
import { ApiError } from '../lib/api'
import type {
  ConsultationQueueItem,
  ConsultationResponse,
  PrescriptionCreate,
} from '../types/consultation'

type Stage = 'queue' | 'open' | 'consult'

const PRIORITY_LABELS: Record<string, { label: string; className: string }> = {
  red: { label: 'Red', className: 'bg-red-50 text-red-700' },
  orange: { label: 'Orange', className: 'bg-orange-50 text-orange-700' },
  yellow: { label: 'Yellow', className: 'bg-yellow-50 text-yellow-800' },
  green: { label: 'Green', className: 'bg-emerald-50 text-emerald-700' },
}

const EMPTY_RX: PrescriptionCreate = {
  medication_name: '',
  dosage: '',
  frequency: '',
  duration: '',
  quantity: 1,
}

export function ConsultationPage() {
  const { api } = useAuth()

  // Queue
  const [stage, setStage] = useState<Stage>('queue')
  const [queue, setQueue] = useState<ConsultationQueueItem[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [loadError, setLoadError] = useState<string | null>(null)
  const [reloadKey, setReloadKey] = useState(0)

  // Selected queue item
  const [selected, setSelected] = useState<ConsultationQueueItem | null>(null)

  // Open consultation form
  const [chiefComplaint, setChiefComplaint] = useState('')
  const [openError, setOpenError] = useState<string | null>(null)
  const [isOpening, setIsOpening] = useState(false)

  // Active consultation
  const [consultation, setConsultation] = useState<ConsultationResponse | null>(null)

  // Prescription add form
  const [showRxForm, setShowRxForm] = useState(false)
  const [rxForm, setRxForm] = useState<PrescriptionCreate>(EMPTY_RX)
  const [rxError, setRxError] = useState<string | null>(null)
  const [isAddingRx, setIsAddingRx] = useState(false)

  // Close consultation form
  const [showCloseForm, setShowCloseForm] = useState(false)
  const [diagnosisText, setDiagnosisText] = useState('')
  const [icd10Code, setIcd10Code] = useState('')
  const [closeNotes, setCloseNotes] = useState('')
  const [closeError, setCloseError] = useState<string | null>(null)
  const [isClosing, setIsClosing] = useState(false)
  const [closedResult, setClosedResult] = useState<{ visit_status: string; pending_prescriptions: number } | null>(null)

  // Success banner back on queue
  const [successBanner, setSuccessBanner] = useState<string | null>(null)

  useEffect(() => {
    let active = true
    setIsLoading(true)
    void api
      .getConsultationQueue()
      .then((data) => { if (active) { setQueue(data); setLoadError(null) } })
      .catch((err: unknown) => {
        if (active) {
          setLoadError(err instanceof ApiError ? err.message : 'Failed to load consultation queue.')
        }
      })
      .finally(() => { if (active) setIsLoading(false) })
    return () => { active = false }
  }, [api, reloadKey])

  async function handleOpenConsultation(e: React.FormEvent) {
    e.preventDefault()
    if (!selected) return
    setOpenError(null)
    setIsOpening(true)
    try {
      const result = await api.openConsultation({
        visit_id: selected.visit_id,
        chief_complaint: chiefComplaint,
      })
      setConsultation(result)
      setStage('consult')
    } catch (err) {
      setOpenError(err instanceof ApiError ? err.message : 'Failed to open consultation.')
    } finally {
      setIsOpening(false)
    }
  }

  async function handleAddPrescription(e: React.FormEvent) {
    e.preventDefault()
    if (!consultation) return
    setRxError(null)
    setIsAddingRx(true)
    try {
      const rx = await api.addPrescription(consultation.id, rxForm)
      setConsultation((prev) =>
        prev ? { ...prev, prescriptions: [...prev.prescriptions, rx] } : prev,
      )
      setRxForm(EMPTY_RX)
      setShowRxForm(false)
    } catch (err) {
      setRxError(err instanceof ApiError ? err.message : 'Failed to add prescription.')
    } finally {
      setIsAddingRx(false)
    }
  }

  async function handleCloseConsultation(e: React.FormEvent) {
    e.preventDefault()
    if (!consultation) return
    setCloseError(null)
    setIsClosing(true)
    try {
      const result = await api.closeConsultation(consultation.id, {
        diagnosis_text: diagnosisText,
        icd10_code: icd10Code || null,
        notes: closeNotes || null,
      })
      setClosedResult(result)
    } catch (err) {
      setCloseError(err instanceof ApiError ? err.message : 'Failed to close consultation.')
    } finally {
      setIsClosing(false)
    }
  }

  function handlePrintRx() {
    if (!consultation || !selected || consultation.prescriptions.length === 0) return
    const win = window.open('', '_blank', 'width=620,height=820')
    if (!win) return
    const rows = consultation.prescriptions
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
  <title>Prescription Note — ${selected.full_name}</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 32px; max-width: 560px; margin: 0 auto; color: #111; }
    h1 { font-size: 20px; margin: 0 0 4px; }
    .clinic { font-size: 12px; color: #6b7280; margin-bottom: 20px; }
    .meta { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; }
    .meta div { margin-bottom: 4px; }
    .meta strong { display: inline-block; min-width: 110px; color: #374151; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    thead th { text-align: left; border-bottom: 2px solid #111; padding: 6px 6px 8px; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #374151; }
    tbody tr:last-child td { border-bottom: none; }
    .footer { margin-top: 32px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #9ca3af; }
    @media print { body { padding: 0; } }
  </style>
</head>
<body>
  <h1>Prescription Note</h1>
  <p class="clinic">SA Healthcare Management System &mdash; Clinical HMS</p>
  <div class="meta">
    <div><strong>Patient:</strong> ${selected.full_name}</div>
    <div><strong>Folder no:</strong> ${selected.folder_number}</div>
    <div><strong>Reason for visit:</strong> ${selected.reason_for_visit}</div>
    <div><strong>Date:</strong> ${dateStr}</div>
    ${consultation.icd10_code ? `<div><strong>ICD-10:</strong> ${consultation.icd10_code}</div>` : ''}
    ${consultation.diagnosis_text ? `<div><strong>Diagnosis:</strong> ${consultation.diagnosis_text}</div>` : ''}
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
    Prescribed by Clinical HMS &mdash; Printed ${new Date().toLocaleString('en-ZA')}
  </div>
  <script>window.onload = function() { window.print(); }<\/script>
</body>
</html>`)
    win.document.close()
  }

  function handleReturnToQueue() {
    if (selected) {
      const pending = closedResult?.pending_prescriptions ?? 0
      const dest = closedResult?.visit_status === 'awaiting_pharmacy' ? 'pharmacy' : 'completed'
      setSuccessBanner(
        `Consultation for ${selected.full_name} closed — ${
          dest === 'pharmacy'
            ? `${pending} prescription(s) sent to pharmacy.`
            : 'visit completed (no prescriptions).'
        }`,
      )
    }
    setStage('queue')
    setSelected(null)
    setConsultation(null)
    setClosedResult(null)
    setChiefComplaint('')
    setDiagnosisText('')
    setIcd10Code('')
    setCloseNotes('')
    setShowRxForm(false)
    setShowCloseForm(false)
    setReloadKey((k) => k + 1)
  }

  // ── Closed result card ─────────────────────────────────────────────────────
  if (stage === 'consult' && closedResult) {
    return (
      <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <PageHeader section="Doctor · Consultation closed" title={selected?.full_name ?? ''} />
        <div className="p-8">

        <div className="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm">
          <dl className="grid grid-cols-2 gap-x-6 gap-y-3">
            <div>
              <dt className="font-medium text-slate-500">Visit status</dt>
              <dd className="mt-0.5 font-semibold capitalize text-slate-950">
                {closedResult.visit_status.replace(/_/g, ' ')}
              </dd>
            </div>
            <div>
              <dt className="font-medium text-slate-500">Prescriptions sent to pharmacy</dt>
              <dd className="mt-0.5 font-semibold text-slate-950">
                {closedResult.pending_prescriptions}
              </dd>
            </div>
          </dl>
        </div>

        <button
          type="button"
          onClick={handleReturnToQueue}
          className="mt-6 rounded-lg bg-[#0d5c4a] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#073d32]"
        >
          Back to consultation queue
        </button>
        </div>
      </section>
    )
  }

  // ── Active consultation ────────────────────────────────────────────────────
  if (stage === 'consult' && consultation) {
    return (
      <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <PageHeader
          section={`Doctor · Consultation #${consultation.id}`}
          title={selected?.full_name ?? ''}
          subtitle={`Folder ${selected?.folder_number ?? ''} · ${selected?.reason_for_visit ?? ''}`}
        />
        <div className="p-8">

        <div className="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
          <p className="font-medium text-slate-500">Chief complaint</p>
          <p className="mt-0.5 text-slate-900">{consultation.chief_complaint}</p>
        </div>

        {/* Prescriptions */}
        <div className="mb-6">
          <div className="mb-3 flex items-center justify-between">
            <h3 className="font-semibold text-slate-950">
              Prescriptions ({consultation.prescriptions.length})
            </h3>
            {!showCloseForm ? (
              <button
                type="button"
                onClick={() => setShowRxForm((s) => !s)}
                className="rounded-lg border border-teal-600 px-3 py-1.5 text-sm font-medium text-teal-600 transition hover:bg-teal-50"
              >
                {showRxForm ? 'Cancel' : '+ Add prescription'}
              </button>
            ) : null}
          </div>

          {showRxForm && !showCloseForm ? (
            <form
              onSubmit={(e) => void handleAddPrescription(e)}
              className="mb-4 rounded-xl border border-slate-200 p-4"
            >
              <h4 className="mb-3 text-sm font-semibold text-slate-700">New prescription</h4>
              {rxError ? (
                <p role="alert" className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
                  {rxError}
                </p>
              ) : null}
              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                  <label className="mb-1 block text-xs font-medium text-slate-600">Medication name *</label>
                  <input
                    required
                    value={rxForm.medication_name}
                    onChange={(e) => setRxForm((p) => ({ ...p, medication_name: e.target.value }))}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                  />
                </div>
                <div>
                  <label className="mb-1 block text-xs font-medium text-slate-600">Dosage *</label>
                  <input
                    required
                    placeholder="e.g. 500 mg"
                    value={rxForm.dosage}
                    onChange={(e) => setRxForm((p) => ({ ...p, dosage: e.target.value }))}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                  />
                </div>
                <div>
                  <label className="mb-1 block text-xs font-medium text-slate-600">Frequency *</label>
                  <input
                    required
                    placeholder="e.g. Twice daily"
                    value={rxForm.frequency}
                    onChange={(e) => setRxForm((p) => ({ ...p, frequency: e.target.value }))}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                  />
                </div>
                <div>
                  <label className="mb-1 block text-xs font-medium text-slate-600">Duration *</label>
                  <input
                    required
                    placeholder="e.g. 7 days"
                    value={rxForm.duration}
                    onChange={(e) => setRxForm((p) => ({ ...p, duration: e.target.value }))}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                  />
                </div>
                <div>
                  <label className="mb-1 block text-xs font-medium text-slate-600">Quantity *</label>
                  <input
                    required
                    type="number"
                    min={1}
                    max={9999}
                    value={rxForm.quantity}
                    onChange={(e) => setRxForm((p) => ({ ...p, quantity: Number(e.target.value) }))}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                  />
                </div>
              </div>
              <div className="mt-3 flex gap-2">
                <button
                  type="submit"
                  disabled={isAddingRx}
                  className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-teal-700 disabled:opacity-50"
                >
                  {isAddingRx ? 'Adding…' : 'Add prescription'}
                </button>
                <button
                  type="button"
                  onClick={() => { setShowRxForm(false); setRxError(null) }}
                  className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                  Cancel
                </button>
              </div>
            </form>
          ) : null}

          {consultation.prescriptions.length === 0 ? (
            <p className="rounded-lg border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-500">
              No prescriptions added yet.
            </p>
          ) : (
            <ul className="divide-y divide-slate-100 rounded-xl border border-slate-200">
              {consultation.prescriptions.map((rx) => (
                <li key={rx.id} className="px-4 py-3 text-sm">
                  <p className="font-semibold text-slate-950">{rx.medication_name}</p>
                  <p className="text-slate-600">
                    {rx.dosage} · {rx.frequency} · {rx.duration} · Qty {rx.quantity}
                  </p>
                </li>
              ))}
            </ul>
          )}
        </div>

        {/* Print prescription note */}
        {consultation.prescriptions.length > 0 && !showCloseForm ? (
          <div className="mb-4">
            <button
              type="button"
              onClick={handlePrintRx}
              className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
              Print prescription note
            </button>
          </div>
        ) : null}

        {/* Close consultation form */}
        {showCloseForm ? (
          <form onSubmit={(e) => void handleCloseConsultation(e)} className="rounded-xl border border-slate-200 p-5">
            <h3 className="mb-4 font-semibold text-slate-950">Close consultation</h3>
            {closeError ? (
              <p role="alert" className="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
                {closeError}
              </p>
            ) : null}
            <div className="space-y-4">
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">
                  Diagnosis / clinical findings *
                </label>
                <textarea
                  required
                  rows={3}
                  value={diagnosisText}
                  onChange={(e) => setDiagnosisText(e.target.value)}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                />
              </div>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label className="mb-1 block text-sm font-medium text-slate-700">
                    ICD-10 code
                  </label>
                  <input
                    value={icd10Code}
                    placeholder="e.g. J00, A09"
                    onChange={(e) => setIcd10Code(e.target.value)}
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                  />
                </div>
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">
                  Clinical notes
                </label>
                <textarea
                  rows={2}
                  value={closeNotes}
                  onChange={(e) => setCloseNotes(e.target.value)}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                />
              </div>
            </div>
            <div className="mt-4 flex gap-3">
              <button
                type="submit"
                disabled={isClosing}
                className="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-900 disabled:opacity-50"
              >
                {isClosing ? 'Closing…' : 'Close & finalise'}
              </button>
              <button
                type="button"
                onClick={() => { setShowCloseForm(false); setCloseError(null) }}
                className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
              >
                Cancel
              </button>
            </div>
          </form>
        ) : (
          <button
            type="button"
            onClick={() => setShowCloseForm(true)}
            className="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-900"
          >
            Close consultation
          </button>
        )}
        </div>
      </section>
    )
  }

  // ── Open consultation form ─────────────────────────────────────────────────
  if (stage === 'open' && selected) {
    return (
      <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <PageHeader
          section="Doctor"
          title="Open consultation"
          subtitle={`${selected.full_name} · Folder ${selected.folder_number}`}
        />
        <div className="p-8">

        <div className="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
          <dl className="grid grid-cols-2 gap-x-6 gap-y-2 sm:grid-cols-3">
            <div>
              <dt className="font-medium text-slate-500">Reason for visit</dt>
              <dd className="mt-0.5 text-slate-900">{selected.reason_for_visit}</dd>
            </div>
            {selected.triage_priority ? (
              <div>
                <dt className="font-medium text-slate-500">Triage priority</dt>
                <dd className="mt-0.5">
                  <span
                    className={`rounded-full px-2 py-0.5 text-xs font-semibold ${PRIORITY_LABELS[selected.triage_priority]?.className ?? ''}`}
                  >
                    {PRIORITY_LABELS[selected.triage_priority]?.label ?? selected.triage_priority}
                  </span>
                </dd>
              </div>
            ) : null}
          </dl>
        </div>

        {openError ? (
          <p role="alert" className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
            {openError}
          </p>
        ) : null}

        <form onSubmit={(e) => void handleOpenConsultation(e)}>
          <div className="mb-4">
            <label className="mb-1 block text-sm font-medium text-slate-700">
              Chief complaint *
            </label>
            <textarea
              required
              rows={3}
              placeholder="Describe the patient's chief complaint in your own words…"
              value={chiefComplaint}
              onChange={(e) => setChiefComplaint(e.target.value)}
              className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            />
          </div>
          <div className="flex gap-3">
            <button
              type="submit"
              disabled={isOpening}
              className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-teal-700 disabled:opacity-50"
            >
              {isOpening ? 'Opening…' : 'Start consultation'}
            </button>
            <button
              type="button"
              onClick={() => { setStage('queue'); setSelected(null); setOpenError(null) }}
              className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
              Cancel
            </button>
          </div>
        </form>
        </div>
      </section>
    )
  }

  // ── Consultation queue ─────────────────────────────────────────────────────
  return (
    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <PageHeader
        section="Doctor"
        title="Consultation queue"
        subtitle="Patients who have been triaged and are awaiting consultation."
        actions={<HeaderActionGhost onClick={() => setReloadKey((k) => k + 1)}>Refresh</HeaderActionGhost>}
      />
      <div className="p-8">

      {successBanner ? (
        <p role="status" className="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
          {successBanner}
        </p>
      ) : null}

      {isLoading ? (
        <p className="text-sm text-slate-600">Loading consultation queue…</p>
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
          No patients are awaiting consultation right now.
        </p>
      ) : (
        <ul className="divide-y divide-slate-200 rounded-xl border border-slate-200">
          {queue.map((item) => (
            <li key={item.visit_id}>
              <button
                type="button"
                onClick={() => {
                  setSelected(item)
                  setSuccessBanner(null)
                  setStage('open')
                }}
                className="flex w-full flex-wrap items-center justify-between gap-4 px-4 py-4 text-left transition hover:bg-slate-50"
              >
                <div>
                  <p className="font-semibold text-slate-950">{item.full_name}</p>
                  <p className="mt-1 text-sm text-slate-600">
                    Folder {item.folder_number} · {item.reason_for_visit}
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  {item.triage_priority ? (
                    <span
                      className={`rounded-full px-3 py-1 text-xs font-semibold ${PRIORITY_LABELS[item.triage_priority]?.className ?? ''}`}
                    >
                      {PRIORITY_LABELS[item.triage_priority]?.label ?? item.triage_priority}
                    </span>
                  ) : null}
                  <span className="rounded-full bg-sky-50 px-3 py-1 text-xs font-medium text-sky-700">
                    See patient →
                  </span>
                </div>
              </button>
            </li>
          ))}
        </ul>
      )}
      </div>
    </section>
  )
}
