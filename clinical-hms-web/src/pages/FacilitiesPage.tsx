/**
 * FacilitiesPage — admin module for listing and creating clinics.
 *
 * View states
 * -----------
 *   list   — facilities ordered by province then city (same as the API)
 *   create — form to register a new facility (admin-only API)
 *
 * Create flow
 * -----------
 * POST /facilities/ requires an admin token. On success the new clinic is
 * merged into the local list (sorted by province, then city) and the user
 * returns to the list view.
 */

import { useEffect, useState } from 'react'

import { useAuth } from '../auth/useAuth'
import { ApiError } from '../lib/api'
import type { FacilityCreate, FacilityResponse } from '../types/facility'

type View = 'list' | 'create'

const SA_PROVINCES = [
  'Eastern Cape',
  'Free State',
  'Gauteng',
  'KwaZulu-Natal',
  'Limpopo',
  'Mpumalanga',
  'Northern Cape',
  'North West',
  'Western Cape',
]

const EMPTY_FORM: FacilityCreate = {
  province: '',
  city: '',
  name: '',
}

const fieldClass =
  'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500'

export function FacilitiesPage() {
  const { api } = useAuth()
  const [view, setView] = useState<View>('list')
  const [facilities, setFacilities] = useState<FacilityResponse[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [listError, setListError] = useState<string | null>(null)
  const [successBanner, setSuccessBanner] = useState<string | null>(null)

  const [form, setForm] = useState<FacilityCreate>(EMPTY_FORM)
  const [formError, setFormError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  function loadFacilities() {
    setIsLoading(true)
    setListError(null)
    void api
      .listFacilities()
      .then(setFacilities)
      .catch((err: unknown) => {
        setListError(
          err instanceof ApiError ? err.message : 'Failed to load facilities. Please try again.',
        )
      })
      .finally(() => setIsLoading(false))
  }

  useEffect(() => {
    loadFacilities()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function handleField(key: keyof FacilityCreate, value: string) {
    setForm((prev) => ({ ...prev, [key]: value }))
  }

  async function handleCreate(e: React.FormEvent) {
    e.preventDefault()
    setFormError(null)
    setIsSubmitting(true)
    try {
      const created = await api.createFacility(form)
      setFacilities((prev) =>
        [...prev, created].sort((a, b) =>
          a.province === b.province
            ? a.city.localeCompare(b.city)
            : a.province.localeCompare(b.province),
        ),
      )
      setForm(EMPTY_FORM)
      setSuccessBanner(`${created.name} in ${created.city}, ${created.province} was added.`)
      setView('list')
    } catch (err) {
      setFormError(
        err instanceof ApiError ? err.message : 'Could not create facility. Please try again.',
      )
    } finally {
      setIsSubmitting(false)
    }
  }

  if (view === 'create') {
    return (
      <section className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div className="mb-6">
          <p className="text-sm font-semibold uppercase tracking-wide text-teal-700">Admin</p>
          <h2 className="mt-2 text-3xl font-bold tracking-tight text-slate-950">
            Add facility
          </h2>
          <p className="mt-2 max-w-2xl text-slate-600">
            Register a clinic so staff can be assigned to it and patients can be checked in there.
          </p>
        </div>

        {formError ? (
          <p role="alert" className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
            {formError}
          </p>
        ) : null}

        <form onSubmit={(e) => void handleCreate(e)} className="max-w-xl space-y-5">
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="province">
              Province *
            </label>
            <select
              id="province"
              required
              value={form.province}
              onChange={(e) => handleField('province', e.target.value)}
              className={fieldClass}
            >
              <option value="">Select province</option>
              {SA_PROVINCES.map((province) => (
                <option key={province} value={province}>
                  {province}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="city">
              City *
            </label>
            <input
              id="city"
              required
              value={form.city}
              onChange={(e) => handleField('city', e.target.value)}
              className={fieldClass}
              placeholder="e.g. Johannesburg"
            />
          </div>

          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="name">
              Facility name *
            </label>
            <input
              id="name"
              required
              value={form.name}
              onChange={(e) => handleField('name', e.target.value)}
              className={fieldClass}
              placeholder="e.g. Demo Community Clinic"
            />
          </div>

          <div className="flex gap-3 pt-2">
            <button
              type="submit"
              disabled={isSubmitting}
              className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-teal-700 disabled:opacity-60"
            >
              {isSubmitting ? 'Saving…' : 'Create facility'}
            </button>
            <button
              type="button"
              onClick={() => {
                setForm(EMPTY_FORM)
                setFormError(null)
                setView('list')
              }}
              className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
              Cancel
            </button>
          </div>
        </form>
      </section>
    )
  }

  return (
    <section className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
      <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
          <p className="text-sm font-semibold uppercase tracking-wide text-teal-700">Admin</p>
          <h2 className="mt-2 text-3xl font-bold tracking-tight text-slate-950">Facilities</h2>
          <p className="mt-2 max-w-2xl text-slate-600">
            Clinics registered in the system. New facilities can only be created by an admin.
          </p>
        </div>
        <button
          type="button"
          onClick={() => {
            setSuccessBanner(null)
            setFormError(null)
            setView('create')
          }}
          className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-teal-700"
        >
          + Add facility
        </button>
      </div>

      {successBanner ? (
        <p
          role="status"
          className="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800"
        >
          {successBanner}
        </p>
      ) : null}

      {listError ? (
        <div className="rounded-lg bg-red-50 px-3 py-3 text-sm text-red-700">
          <p role="alert">{listError}</p>
          <button
            type="button"
            onClick={loadFacilities}
            className="mt-2 font-medium underline"
          >
            Retry
          </button>
        </div>
      ) : null}

      {isLoading ? (
        <p className="text-sm text-slate-500">Loading facilities…</p>
      ) : facilities.length === 0 && !listError ? (
        <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
          <p className="font-medium text-slate-800">No facilities yet</p>
          <p className="mt-1 text-sm text-slate-600">
            Add the first clinic to start assigning staff and registering patients.
          </p>
        </div>
      ) : (
        <div className="overflow-hidden rounded-xl border border-slate-200">
          <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead className="bg-slate-50">
              <tr>
                <th scope="col" className="px-4 py-3 font-semibold text-slate-700">
                  Name
                </th>
                <th scope="col" className="px-4 py-3 font-semibold text-slate-700">
                  City
                </th>
                <th scope="col" className="px-4 py-3 font-semibold text-slate-700">
                  Province
                </th>
                <th scope="col" className="px-4 py-3 font-semibold text-slate-700">
                  ID
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 bg-white">
              {facilities.map((facility) => (
                <tr key={facility.id} className="hover:bg-slate-50">
                  <td className="px-4 py-3 font-medium text-slate-950">{facility.name}</td>
                  <td className="px-4 py-3 text-slate-700">{facility.city}</td>
                  <td className="px-4 py-3 text-slate-700">{facility.province}</td>
                  <td className="px-4 py-3 text-slate-500">#{facility.id}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </section>
  )
}
