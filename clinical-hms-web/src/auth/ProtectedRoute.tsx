/**
 * ProtectedRoute — authentication and role guard for React Router v7.
 *
 * Renders the nested <Outlet /> only when both conditions are met:
 *   1. The user is authenticated (status !== 'loading' | 'unauthenticated').
 *   2. If allowedRoles is provided, the user's role is in the list.
 *
 * The 'loading' state is shown while AuthProvider is validating a saved token
 * from sessionStorage on page refresh. Without this check, the app would flash
 * the login page for ~200ms on every page reload even when the user is signed in.
 *
 * Usage in App.tsx:
 *   // Any authenticated user:
 *   <Route element={<ProtectedRoute />}> ... </Route>
 *
 *   // Nurses and admins only:
 *   <Route element={<ProtectedRoute allowedRoles={['nurse', 'admin']} />}> ... </Route>
 */

import { Navigate, Outlet, useLocation } from 'react-router-dom'

import type { StaffRole } from '../types/auth'
import { useAuth } from './useAuth'

type ProtectedRouteProps = {
  allowedRoles?: StaffRole[]
}

export function ProtectedRoute({ allowedRoles }: ProtectedRouteProps) {
  const location = useLocation()
  const { status, user } = useAuth()

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

  if (allowedRoles && user?.role && !allowedRoles.includes(user.role)) {
    return <Navigate to="/unauthorized" replace />
  }

  return <Outlet />
}
