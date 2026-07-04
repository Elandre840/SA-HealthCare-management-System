import { Navigate } from 'react-router-dom'

import { RoleDashboardPlaceholder } from '../components/RoleDashboardPlaceholder'
import { useAuth } from '../auth/useAuth'
import { TriagePage } from './TriagePage'

export function DashboardPage() {
  const { user } = useAuth()

  if (user?.role === 'nurse') {
    return <TriagePage />
  }

  if (user?.role) {
    return <RoleDashboardPlaceholder role={user.role} />
  }

  return <Navigate to="/login" replace />
}
