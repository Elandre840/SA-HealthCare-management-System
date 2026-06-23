import { Navigate, Outlet, useLocation } from 'react-router-dom'

import { useAuth } from './useAuth'

export function ProtectedRoute() {
  const location = useLocation()
  const { status } = useAuth()

  if (status === 'loading') {
    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
        <p className="text-sm font-medium text-slate-600">Checking your session...</p>
      </div>
    )
  }

  if (status === 'unauthenticated') {
    return <Navigate to="/login" replace state={{ from: location }} />
  }

  return <Outlet />
}
