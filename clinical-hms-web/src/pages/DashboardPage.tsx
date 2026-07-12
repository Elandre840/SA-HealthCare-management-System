import { Navigate } from 'react-router-dom'

import { useAuth } from '../auth/useAuth'

// DashboardPage acts as a role router — each role is redirected to its
// dedicated clinical module. Add new roles here as modules are built.
export function DashboardPage() {
  const { user } = useAuth()

  switch (user?.role) {
    case 'reception':
      return <Navigate to="/patients" replace />
    case 'nurse':
      return <Navigate to="/triage" replace />
    case 'doctor':
      return <Navigate to="/consultations" replace />
    case 'pharmacist':
      return <Navigate to="/pharmacy" replace />
    case 'admin':
      return <Navigate to="/facilities" replace />
    default:
      return <Navigate to="/login" replace />
  }
}
