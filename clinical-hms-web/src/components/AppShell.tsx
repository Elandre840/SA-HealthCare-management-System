/**
 * AppShell — shared layout wrapper for all authenticated pages.
 *
 * Renders a top navigation header containing:
 *   - The application name and "Workspace" label.
 *   - Role-based navigation links (only links relevant to the user's role).
 *   - The signed-in user's full name, role badge, and a logout button.
 *
 * The page content is rendered via <Outlet />, which React Router replaces with
 * the matched child route component (e.g. PatientsPage, TriagePage, etc.).
 *
 * NAV_ITEMS maps each StaffRole to the set of links that role should see. The
 * admin role gets all links. Add new routes here when new clinical modules are
 * built — no changes needed in App.tsx or individual page components.
 */

import { NavLink, Outlet } from 'react-router-dom'

import { useAuth } from '../auth/useAuth'
import type { StaffRole } from '../types/auth'

function formatRole(role: string | null | undefined) {
  if (!role) return 'Staff'
  return role.charAt(0).toUpperCase() + role.slice(1)
}

type NavItem = { to: string; label: string }

const NAV_ITEMS: Partial<Record<StaffRole, NavItem[]>> = {
  reception: [{ to: '/patients', label: 'Patients' }],
  nurse: [{ to: '/triage', label: 'Triage queue' }],
  doctor: [{ to: '/consultations', label: 'Consultations' }],
  pharmacist: [{ to: '/pharmacy', label: 'Pharmacy' }],
  admin: [
    { to: '/patients', label: 'Patients' },
    { to: '/triage', label: 'Triage' },
    { to: '/consultations', label: 'Consultations' },
    { to: '/pharmacy', label: 'Pharmacy' },
  ],
}

export function AppShell() {
  const { logout, user } = useAuth()
  const navItems = user?.role ? (NAV_ITEMS[user.role] ?? []) : []

  return (
    <div className="min-h-screen bg-slate-100">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
          <div className="flex items-center gap-6">
            <div>
              <p className="text-xs font-semibold uppercase tracking-wide text-teal-700">
                Clinical HMS
              </p>
              <h1 className="text-lg font-semibold text-slate-950">Workspace</h1>
            </div>

            {navItems.length > 0 ? (
              <nav aria-label="Main navigation" className="hidden sm:flex items-center gap-1">
                {navItems.map((item) => (
                  <NavLink
                    key={item.to}
                    to={item.to}
                    className={({ isActive }) =>
                      `rounded-lg px-3 py-1.5 text-sm font-medium transition ${
                        isActive
                          ? 'bg-teal-50 text-teal-700'
                          : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950'
                      }`
                    }
                  >
                    {item.label}
                  </NavLink>
                ))}
              </nav>
            ) : null}
          </div>

          <div className="flex items-center gap-4">
            <div className="text-right">
              <p className="text-sm font-medium text-slate-950">
                {user?.full_name ?? 'Signed in user'}
              </p>
              <p className="text-xs text-slate-500">{formatRole(user?.role)}</p>
            </div>
            <button
              type="button"
              onClick={() => void logout()}
              className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
              Logout
            </button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <Outlet />
      </main>
    </div>
  )
}
