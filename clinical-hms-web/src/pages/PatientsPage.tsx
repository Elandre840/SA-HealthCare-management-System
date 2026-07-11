/**
 * PatientsPage — reception module for patient registration and search.
 *
 * View states
 * -----------
 *   list     — displays the patient list with a live search input (350ms debounce)
 *              and a "+ Register patient" button.
 *   register — shows the registration form. On success, transitions to the
 *              success card view.
 *   (success card) — shown when registered !== null while view === 'register';
 *              displays the new patient's folder number and visit ID and offers
 *              "Register another" or "Back to list" actions.
 *
 * Registration flow
 * -----------------
 * POST /patients/ creates the Patient AND a Visit (awaiting_triage) atomically.
 * The nurse will see the patient in the triage queue as soon as this succeeds.
 * No separate "check in" step is needed.
 */

import { useEffect, useRef, useState } from 'react'

import { useAuth } from '../auth/useAuth'
import { ApiError } from '../lib/api'
import type { PatientCreate, PatientResponse, PatientVisitResponse } from '../types/patient'

type View = 'list' | 'register'

const GENDERS = ['Male', 'Female', 'Other', 'Prefer not to say']

const EMPTY_FORM: PatientCreate = {
  first_name: '',
  surname: '',
  id_number: '',
  date_of_birth: '',
  gender: '',
  contact_number: '',
  next_of_kin_name: '',
  next_of_kin_contact: '',
  folder_number: '',
  reason_for_visit: '',
}

export function PatientsPage() {
  const { api } = useAuth()
  const [view, setView] = useState<View>('list')
  const [patients, setPatients] = useState<PatientResponse[]>([])
  const [search, setSearch] = useState('')
  const [isLoading, setIsLoading] = useState(true)
  const [listError, setListError] = useState<string | null>(null)
  const [successBanner, setSuccessBanner] = useState<string | null>(null)
  const searchTimeout = useRef<ReturnType<typeof setTimeout> | null>(null)

  // Registration form state
  const [form, setForm] = useState<PatientCreate>(EMPTY_FORM)
  const [formError, setFormError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [registered, setRegistered] = useState<PatientVisitResponse | null>(null)

  function loadPatients(q?: string) {
    setIsLoading(true)
    setListError(null)
    void api
      .listPatients(q || undefined)
      .then((data) => {
        setPatients(data)
      })
      .catch((err: unknown) => {
        setListError(
          err instanceof ApiError ? err.message : 'Failed to load patients. Please try again.',
        )
      })
      .finally(() => setIsLoading(false))
  }

  useEffect(() => {
    loadPatients()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function handleSearchChange(value: string) {
    setSearch(value)
    if (searchTimeout.current) clearTimeout(searchTimeout.current)
    searchTimeout.current = setTimeout(() => loadPatients(value), 350)
  }

  function handleField(key: keyof PatientCreate, value: string) {
    setForm((prev) => ({ ...prev, [key]: value }))
  }

  async function handleRegister(e: React.FormEvent) {
    e.preventDefault()
    setFormError(null)
    setIsSubmitting(true)
    try {
      const payload: PatientCreate = {
        ...form,
        id_number: form.id_number || null,
        date_of_birth: form.date_of_birth || null,
        gender: form.gender || null,
        contact_number: form.contact_number || null,
        next_of_kin_name: form.next_of_kin_name || null,
        next_of_kin_contact: form.next_of_kin_contact || null,
        folder_number: form.folder_number || null,
      }
      const result = await api.registerPatient(payload)
      setRegistered(result)
      setForm(EMPTY_FORM)
      // Reload list in the background so it's fresh when user returns
      void api.listPatients().then(setPatients).catch(() => null)
    } catch (err) {
      setFormError(
        err instanceof ApiError ? err.message : 'Registration failed. Please try again.',
      )
    } finally {
      setIsSubmitting(false)
    }
  }

  function handleNewRegistration() {
    setRegistered(null)
    setSuccessBanner(null)
  }

  function handleBackToList() {
    if (registered) {
      setSuccessBanner(
        `${registered.first_name} ${registered.surname} registered — folder ${registered.folder_number}.`,
      )
    }
    setRegistered(null)
    setView('list')
    loadPatients(search)
  }

  // ── Success card after registration ───────────────────────────────────────
  if (view === 'register' && registered) {
    return (
      <section className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div className="mb-6 flex items-center gap-3">
          <span className="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
            <svg viewBox="0 0 20 20" fill="currentColor" className="h-5 w-5">
              <path fillRule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clipRule="evenodd" />
            </svg>
          </span>
          <div>
            <p className="text-sm font-semibold uppercase tracking-wide text-emerald-700">
              Registration successful
            </p>
            <h2 className="text-2xl font-bold text-slate-950">Patient checked in</h2>
          </div>
        </div>

        <div className="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm">
          <dl className="grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-3">
            <div>
              <dt className="font-medium text-slate-500">Full name</dt>
              <dd className="mt-0.5 font-semibold text-slate-950">
                {registered.first_name} {registered.surname}
              </dd>
            </div>
            <div>
              <dt className="font-medium text-slate-500">Folder number</dt>
              <dd className="mt-0.5 font-semibold text-slate-950">{registered.folder_number}</dd>
            </div>
            <div>
              <dt className="font-medium text-slate-500">Visit ID</dt>
              <dd className="mt-0.5 font-semibold text-slate-950">#{registered.visit_id}</dd>
            </div>
            {registered.id_number ? (
              <div>
                <dt className="font-medium text-slate-500">ID number</dt>
                <dd className="mt-0.5 text-slate-900">{registered.id_number}</dd>
              </div>
            ) : null}
            {registered.contact_number ? (
              <div>
                <dt className="font-medium text-slate-500">Contact</dt>
                <dd className="mt-0.5 text-slate-900">{registered.contact_number}</dd>
              </div>
            ) : null}
            <div className="col-span-full">
              <dt className="font-medium text-slate-500">Reason for visit</dt>
              <dd className="mt-0.5 text-slate-900">{registered.reason_for_visit}</dd>
            </div>
          </dl>
        </div>

        <p className="mt-4 text-sm text-slate-600">
          The patient has been placed in the triage queue and is awaiting vitals capture by a nurse.
        </p>

        <div className="mt-6 flex gap-3">
          <button
            type="button"
            onClick={handleNewRegistration}
            className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-teal-700"
          >
            Register another patient
          </button>
          <button
            type="button"
            onClick={handleBackToList}
            className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
          >
            Back to patient list
          </button>
        </div>
      </section>
    )
  }

  // ── Registration form ──────────────────────────────────────────────────────
  if (view === 'register') {
    return (
      <section className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div className="mb-6">
          <p className="text-sm font-semibold uppercase tracking-wide text-teal-700">Reception</p>
          <h2 className="mt-2 text-3xl font-bold tracking-tight text-slate-950">
            Register new patient
          </h2>
          <p className="mt-2 max-w-2xl text-slate-600">
            Complete the form below. Required fields are marked with *.
          </p>
        </div>

        {formError ? (
          <p role="alert" className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
            {formError}
          </p>
        ) : null}

        <form onSubmit={(e) => void handleRegister(e)} className="space-y-6">
          {/* Personal details */}
          <fieldset className="rounded-xl border border-slate-200 p-5">
            <legend className="px-1 text-sm font-semibold text-slate-700">Personal details</legend>
            <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="first_name">
                  First name *
                </label>
                <input
                  id="first_name"
                  required
                  value={form.first_name}
                  onChange={(e) => handleField('first_name', e.target.value)}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="surname">
                  Surname *
                </label>
                <input
                  id="surname"
                  required
                  value={form.surname}
                  onChange={(e) => handleField('surname', e.target.value)}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="id_number">
                  SA ID number (13 digits)
                </label>
                <input
                  id="id_number"
                  value={form.id_number ?? ''}
                  maxLength={13}
                  pattern="\d{13}"
                  onChange={(e) => handleField('id_number', e.target.value)}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="date_of_birth">
                  Date of birth
                </label>
                <input
                  id="date_of_birth"
                  type="date"
                  value={form.date_of_birth ?? ''}
                  onChange={(e) => handleField('date_of_birth', e.target.value)}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="gender">
                  Gender
                </label>
                <select
                  id="gender"
                  value={form.gender ?? ''}
                  onChange={(e) => handleField('gender', e.target.value)}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                >
                  <option value="">— Select —</option>
                  {GENDERS.map((g) => (
                    <option key={g} value={g}>
                      {g}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="contact_number">
                  Contact number
                </label>
                <input
                  id="contact_number"
                  value={form.contact_number ?? ''}
                  onChange={(e) => handleField('contact_number', e.target.value)}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                />
              </div>
            </div>
          </fieldset>

          {/* Next of kin */}
          <fieldset className="rounded-xl border border-slate-200 p-5">
            <legend className="px-1 text-sm font-semibold text-slate-700">Next of kin</legend>
            <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="next_of_kin_name">
                  Name
                </label>
                <input
                  id="next_of_kin_name"
                  value={form.next_of_kin_name ?? ''}
                  onChange={(e) => handleField('next_of_kin_name', e.target.value)}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="next_of_kin_contact">
                  Contact number
                </label>
                <input
                  id="next_of_kin_contact"
                  value={form.next_of_kin_contact ?? ''}
                  onChange={(e) => handleField('next_of_kin_contact', e.target.value)}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                />
              </div>
            </div>
          </fieldset>

          {/* Visit */}
          <fieldset className="rounded-xl border border-slate-200 p-5">
            <legend className="px-1 text-sm font-semibold text-slate-700">Visit details</legend>
            <div className="mt-4">
              <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="reason_for_visit">
                Reason for visit *
              </label>
              <textarea
                id="reason_for_visit"
                required
                rows={3}
                value={form.reason_for_visit}
                onChange={(e) => handleField('reason_for_visit', e.target.value)}
                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              />
            </div>
          </fieldset>

          <div className="flex gap-3">
            <button
              type="submit"
              disabled={isSubmitting}
              className="rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-teal-700 disabled:opacity-50"
            >
              {isSubmitting ? 'Registering…' : 'Register & check in'}
            </button>
            <button
              type="button"
              onClick={() => setView('list')}
              className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
              Cancel
            </button>
          </div>
        </form>
      </section>
    )
  }

  // ── Patient list ───────────────────────────────────────────────────────────
  return (
    <section className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
      <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
          <p className="text-sm font-semibold uppercase tracking-wide text-teal-700">Reception</p>
          <h2 className="mt-2 text-3xl font-bold tracking-tight text-slate-950">Patients</h2>
          <p className="mt-2 max-w-2xl text-slate-600">
            Search by name or folder number, or register a new patient.
          </p>
        </div>
        <button
          type="button"
          onClick={() => {
            setView('register')
            setRegistered(null)
            setFormError(null)
          }}
          className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-teal-700"
        >
          + Register patient
        </button>
      </div>

      {successBanner ? (
        <p role="status" className="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
          {successBanner}
        </p>
      ) : null}

      <div className="mb-4">
        <input
          type="search"
          placeholder="Search by name or folder number…"
          value={search}
          onChange={(e) => handleSearchChange(e.target.value)}
          className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 sm:max-w-xs"
        />
      </div>

      {isLoading ? (
        <p className="text-sm text-slate-600">Loading patients…</p>
      ) : listError ? (
        <div className="space-y-3">
          <p role="alert" className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
            {listError}
          </p>
          <button
            type="button"
            onClick={() => loadPatients(search)}
            className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
          >
            Retry
          </button>
        </div>
      ) : patients.length === 0 ? (
        <p className="rounded-lg border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-600">
          {search ? 'No patients found matching your search.' : 'No patients registered yet.'}
        </p>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <th className="pb-3 pr-4">Folder</th>
                <th className="pb-3 pr-4">Name</th>
                <th className="pb-3 pr-4">ID number</th>
                <th className="pb-3 pr-4">Gender</th>
                <th className="pb-3">Contact</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {patients.map((p) => (
                <tr key={p.id} className="hover:bg-slate-50">
                  <td className="py-3 pr-4 font-mono text-xs text-slate-600">{p.folder_number}</td>
                  <td className="py-3 pr-4 font-medium text-slate-950">
                    {p.first_name} {p.surname}
                  </td>
                  <td className="py-3 pr-4 font-mono text-xs text-slate-600">
                    {p.id_number ?? '—'}
                  </td>
                  <td className="py-3 pr-4 text-slate-700">{p.gender ?? '—'}</td>
                  <td className="py-3 text-slate-700">{p.contact_number ?? '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </section>
  )
}
