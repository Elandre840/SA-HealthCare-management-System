/**
 * AuditLogPage — POPIA-compliant audit trail viewer (admin only).
 *
 * Loads the most recent 200 audit entries from GET /api/v1/audit-logs/ and
 * lets the admin filter by keyword, action type, or entity type. Entries are
 * read-only; the list is ordered newest-first.
 *
 * Action badge colour coding:
 *   teal    — reception actions (patient_registered, visit_checked_in)
 *   sky     — nurse actions (vitals_recorded, triage_priority_set)
 *   red     — critical alerts (medi_alert)
 *   indigo  — doctor actions (consultation_*, prescription_added)
 *   emerald — pharmacy actions (prescription_dispensed, visit_completed)
 *   slate   — auth and system actions
 */

import { useEffect, useMemo, useState } from 'react'

import { useAuth } from '../auth/useAuth'
import { ApiError } from '../lib/api'
import type { AuditLogEntry } from '../types/audit_log'

const ACTION_STYLES: Record<string, string> = {
  patient_registered: 'bg-teal-50 text-teal-800 ring-1 ring-inset ring-teal-200',
  visit_checked_in: 'bg-teal-50 text-teal-800 ring-1 ring-inset ring-teal-200',
  vitals_recorded: 'bg-sky-50 text-sky-800 ring-1 ring-inset ring-sky-200',
  triage_priority_set: 'bg-sky-50 text-sky-800 ring-1 ring-inset ring-sky-200',
  medi_alert: 'bg-red-50 text-red-800 ring-1 ring-inset ring-red-200',
  consultation_opened: 'bg-indigo-50 text-indigo-800 ring-1 ring-inset ring-indigo-200',
  consultation_amended: 'bg-indigo-50 text-indigo-800 ring-1 ring-inset ring-indigo-200',
  prescription_added: 'bg-indigo-50 text-indigo-800 ring-1 ring-inset ring-indigo-200',
  consultation_closed: 'bg-indigo-50 text-indigo-800 ring-1 ring-inset ring-indigo-200',
  prescription_dispensed: 'bg-emerald-50 text-emerald-800 ring-1 ring-inset ring-emerald-200',
  visit_completed: 'bg-emerald-50 text-emerald-800 ring-1 ring-inset ring-emerald-200',
}

const ROLE_STYLES: Record<string, string> = {
  admin: 'bg-slate-800 text-white',
  reception: 'bg-teal-600 text-white',
  nurse: 'bg-sky-600 text-white',
  doctor: 'bg-indigo-600 text-white',
  pharmacist: 'bg-emerald-600 text-white',
}

function actionStyle(action: string) {
  return ACTION_STYLES[action] ?? 'bg-slate-50 text-slate-700 ring-1 ring-inset ring-slate-200'
}

function roleStyle(role: string | null) {
  if (!role) return 'bg-slate-200 text-slate-600'
  return ROLE_STYLES[role] ?? 'bg-slate-500 text-white'
}

function formatAction(action: string) {
  return action.replace(/_/g, ' ')
}

function formatDate(ts: string) {
  return new Date(ts).toLocaleDateString('en-ZA', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

function formatTime(ts: string) {
  return new Date(ts).toLocaleTimeString('en-ZA', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  })
}

function countByPrefix(entries: AuditLogEntry[], prefixes: string[]) {
  return entries.filter((e) => prefixes.some((p) => e.action.startsWith(p) || e.action === p)).length
}

export function AuditLogPage() {
  const { api } = useAuth()
  const [entries, setEntries] = useState<AuditLogEntry[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [loadError, setLoadError] = useState<string | null>(null)
  const [reloadKey, setReloadKey] = useState(0)

  const [search, setSearch] = useState('')
  const [actionFilter, setActionFilter] = useState('')
  const [entityFilter, setEntityFilter] = useState('')

  useEffect(() => {
    let active = true
    setIsLoading(true)
    setLoadError(null)
    void api
      .listAuditLogs({ limit: 200 })
      .then((data) => {
        if (active) setEntries(data)
      })
      .catch((err: unknown) => {
        if (active) {
          setLoadError(err instanceof ApiError ? err.message : 'Failed to load audit log.')
        }
      })
      .finally(() => {
        if (active) setIsLoading(false)
      })
    return () => {
      active = false
    }
  }, [api, reloadKey])

  const uniqueActions = useMemo(
    () => Array.from(new Set(entries.map((e) => e.action))).sort(),
    [entries],
  )
  const uniqueEntities = useMemo(
    () =>
      Array.from(new Set(entries.map((e) => e.entity_type).filter(Boolean))).sort() as string[],
    [entries],
  )

  const filtered = useMemo(() => {
    const q = search.trim().toLowerCase()
    return entries.filter((e) => {
      if (actionFilter && e.action !== actionFilter) return false
      if (entityFilter && e.entity_type !== entityFilter) return false
      if (!q) return true
      const haystack = [
        e.action,
        e.actor_role ?? '',
        e.entity_type ?? '',
        e.entity_id != null ? String(e.entity_id) : '',
        e.details ?? '',
      ]
        .join(' ')
        .toLowerCase()
      return haystack.includes(q)
    })
  }, [entries, actionFilter, entityFilter, search])

  const hasActiveFilters = Boolean(search || actionFilter || entityFilter)

  const stats = useMemo(
    () => ({
      total: entries.length,
      clinical: countByPrefix(entries, [
        'patient_',
        'visit_',
        'vitals_',
        'triage_',
        'consultation_',
        'prescription_',
        'medi_alert',
      ]),
      alerts: entries.filter((e) => e.action === 'medi_alert').length,
      latest: entries[0]?.timestamp ?? null,
    }),
    [entries],
  )

  return (
    <div className="space-y-6">
      {/* Header */}
      <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div className="border-b border-slate-200 bg-gradient-to-r from-slate-900 via-slate-800 to-teal-900 px-8 py-7 text-white">
          <div className="flex flex-wrap items-start justify-between gap-4">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.18em] text-teal-300">
                Compliance · Admin
              </p>
              <h2 className="mt-2 text-3xl font-bold tracking-tight">Audit trail</h2>
              <p className="mt-2 max-w-2xl text-sm leading-relaxed text-slate-300">
                Immutable POPIA activity log of clinical and administrative actions across the
                facility. Records are append-only and retained for compliance review.
              </p>
            </div>
            <button
              type="button"
              onClick={() => setReloadKey((k) => k + 1)}
              className="rounded-lg border border-white/20 bg-white/10 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-white/15"
            >
              Refresh
            </button>
          </div>
        </div>

        {/* Summary strip */}
        <div className="grid grid-cols-2 divide-x divide-slate-200 sm:grid-cols-4">
          <div className="px-6 py-4">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
              Loaded entries
            </p>
            <p className="mt-1 text-2xl font-bold tabular-nums text-slate-950">{stats.total}</p>
          </div>
          <div className="px-6 py-4">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
              Clinical events
            </p>
            <p className="mt-1 text-2xl font-bold tabular-nums text-slate-950">{stats.clinical}</p>
          </div>
          <div className="px-6 py-4">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
              Medi alerts
            </p>
            <p className="mt-1 text-2xl font-bold tabular-nums text-red-700">{stats.alerts}</p>
          </div>
          <div className="px-6 py-4">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
              Latest event
            </p>
            <p className="mt-1 text-sm font-semibold text-slate-950">
              {stats.latest ? (
                <>
                  {formatDate(stats.latest)}
                  <span className="ml-1.5 font-mono text-xs font-normal text-slate-500">
                    {formatTime(stats.latest)}
                  </span>
                </>
              ) : (
                '—'
              )}
            </p>
          </div>
        </div>
      </section>

      {/* Filters + table */}
      <section className="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div className="border-b border-slate-200 px-6 py-4">
          <div className="flex flex-wrap items-end gap-3">
            <div className="min-w-[220px] flex-1">
              <label
                htmlFor="audit-search"
                className="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500"
              >
                Search
              </label>
              <input
                id="audit-search"
                type="search"
                placeholder="Search action, role, entity, or details…"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              />
            </div>

            <div>
              <label
                htmlFor="audit-action"
                className="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500"
              >
                Action
              </label>
              <select
                id="audit-action"
                value={actionFilter}
                onChange={(e) => setActionFilter(e.target.value)}
                className="rounded-lg border border-slate-300 px-3 py-2 text-sm capitalize focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              >
                <option value="">All actions</option>
                {uniqueActions.map((a) => (
                  <option key={a} value={a}>
                    {formatAction(a)}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label
                htmlFor="audit-entity"
                className="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500"
              >
                Entity
              </label>
              <select
                id="audit-entity"
                value={entityFilter}
                onChange={(e) => setEntityFilter(e.target.value)}
                className="rounded-lg border border-slate-300 px-3 py-2 text-sm capitalize focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              >
                <option value="">All entities</option>
                {uniqueEntities.map((t) => (
                  <option key={t} value={t}>
                    {t}
                  </option>
                ))}
              </select>
            </div>

            {hasActiveFilters ? (
              <button
                type="button"
                onClick={() => {
                  setSearch('')
                  setActionFilter('')
                  setEntityFilter('')
                }}
                className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
              >
                Clear
              </button>
            ) : null}

            {!isLoading ? (
              <p className="ml-auto pb-2 text-xs text-slate-400">
                Showing{' '}
                <span className="font-semibold tabular-nums text-slate-600">{filtered.length}</span>
                {hasActiveFilters ? ` of ${entries.length}` : ''}{' '}
                {filtered.length === 1 ? 'entry' : 'entries'}
              </p>
            ) : null}
          </div>
        </div>

        <div className="px-2 pb-2 pt-1 sm:px-4 sm:pb-4">
          {isLoading ? (
            <div className="flex items-center gap-3 px-4 py-10 text-sm text-slate-600">
              <span className="inline-block h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-teal-600" />
              Loading audit trail…
            </div>
          ) : loadError ? (
            <div className="space-y-3 px-4 py-6">
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
          ) : filtered.length === 0 ? (
            <p className="mx-2 my-4 rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-600">
              {hasActiveFilters
                ? 'No entries match the selected filters.'
                : 'No audit log entries have been recorded yet.'}
            </p>
          ) : (
            <div className="overflow-x-auto rounded-xl border border-slate-200">
              <table className="w-full min-w-[760px] text-sm">
                <thead className="bg-slate-50">
                  <tr className="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                    <th className="whitespace-nowrap px-4 py-3">When</th>
                    <th className="px-4 py-3">Actor</th>
                    <th className="px-4 py-3">Action</th>
                    <th className="px-4 py-3">Target</th>
                    <th className="px-4 py-3">Details</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 bg-white">
                  {filtered.map((entry) => (
                    <tr key={entry.id} className="align-top transition hover:bg-slate-50/80">
                      <td className="whitespace-nowrap px-4 py-3.5">
                        <p className="font-medium text-slate-900">{formatDate(entry.timestamp)}</p>
                        <p className="mt-0.5 font-mono text-xs text-slate-500">
                          {formatTime(entry.timestamp)}
                        </p>
                      </td>
                      <td className="px-4 py-3.5">
                        <span
                          className={`inline-flex rounded-md px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide ${roleStyle(entry.actor_role)}`}
                        >
                          {entry.actor_role ?? 'system'}
                        </span>
                        {entry.actor_id != null ? (
                          <p className="mt-1.5 font-mono text-[11px] text-slate-400">
                            user #{entry.actor_id}
                          </p>
                        ) : null}
                      </td>
                      <td className="px-4 py-3.5">
                        <span
                          className={`inline-flex rounded-md px-2.5 py-1 text-xs font-semibold capitalize ${actionStyle(entry.action)}`}
                        >
                          {formatAction(entry.action)}
                        </span>
                      </td>
                      <td className="px-4 py-3.5">
                        {entry.entity_type ? (
                          <div>
                            <p className="font-medium capitalize text-slate-800">
                              {entry.entity_type}
                            </p>
                            {entry.entity_id != null ? (
                              <p className="mt-0.5 font-mono text-xs text-slate-400">
                                #{entry.entity_id}
                              </p>
                            ) : null}
                          </div>
                        ) : (
                          <span className="text-slate-400">—</span>
                        )}
                      </td>
                      <td className="max-w-sm px-4 py-3.5">
                        <p
                          className="break-words font-mono text-xs leading-relaxed text-slate-600"
                          title={entry.details ?? undefined}
                        >
                          {entry.details ?? '—'}
                        </p>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>

        {!isLoading && !loadError && filtered.length > 0 ? (
          <div className="border-t border-slate-200 px-6 py-3">
            <p className="text-xs text-slate-400">
              Append-only audit store · most recent 200 entries · times shown in local timezone
              (en-ZA)
            </p>
          </div>
        ) : null}
      </section>
    </div>
  )
}
