import { Outlet } from 'react-router-dom'

import { useAuth } from '../auth/useAuth'

function formatRole(role: string | null | undefined) {
  if (!role) {
    return 'Staff'
  }

  return role.charAt(0).toUpperCase() + role.slice(1)
}

export function AppShell() {
  const { logout, user } = useAuth()

  return (
    <div className="min-h-screen bg-slate-100">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-teal-700">
              Clinical HMS
            </p>
            <h1 className="text-lg font-semibold text-slate-950">Workspace</h1>
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
