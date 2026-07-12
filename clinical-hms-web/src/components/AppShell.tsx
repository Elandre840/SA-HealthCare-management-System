/**
 * AppShell — shared layout wrapper for all authenticated pages.
 *
 * Dark-green navigation header consistent with the Clinical HMS brand.
 * NAV_ITEMS maps each StaffRole to the links that role should see.
 * The admin role sees all modules plus Facilities and Audit log.
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
    { to: '/facilities', label: 'Facilities' },
    { to: '/audit-log', label: 'Audit log' },
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
    <div className="min-h-screen" style={{ background: '#f3f7f5' }}>
      <header
        style={{
          background: 'linear-gradient(90deg, #073d32 0%, #0d5c4a 60%, #0f6652 100%)',
          fontFamily: '"Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif',
        }}
      >
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
          {/* Brand + nav */}
          <div className="flex items-center gap-6">
            <div>
              <p
                className="text-[10px] font-semibold uppercase tracking-[0.22em]"
                style={{ color: '#c4a35a' }}
              >
                Clinical HMS
              </p>
              <h1
                className="text-base font-semibold text-white"
                style={{ fontFamily: '"Fraunces", Georgia, serif' }}
              >
                Workspace
              </h1>
            </div>

            {navItems.length > 0 ? (
              <nav aria-label="Main navigation" className="hidden items-center gap-0.5 sm:flex">
                {navItems.map((item) => (
                  <NavLink
                    key={item.to}
                    to={item.to}
                    className="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                    style={({ isActive }) =>
                      isActive
                        ? {
                            background: 'rgba(196,163,90,0.18)',
                            color: '#c4a35a',
                            outline: '1px solid rgba(196,163,90,0.45)',
                            outlineOffset: '-1px',
                          }
                        : { color: 'rgba(255,255,255,0.72)' }
                    }
                  >
                    {item.label}
                  </NavLink>
                ))}
              </nav>
            ) : null}
          </div>

          {/* User info + logout */}
          <div className="flex items-center gap-4">
            <div className="text-right">
              <p className="text-sm font-medium text-white">
                {user?.full_name ?? 'Signed in user'}
              </p>
              <p className="text-[11px]" style={{ color: 'rgba(196,163,90,0.85)' }}>
                {formatRole(user?.role)}
              </p>
            </div>
            <button
              type="button"
              onClick={() => void logout()}
              className="rounded-lg border border-white/20 bg-white/10 px-3 py-2 text-sm font-medium text-white transition hover:bg-white/20"
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
