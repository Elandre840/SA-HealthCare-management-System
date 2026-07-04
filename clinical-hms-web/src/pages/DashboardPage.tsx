import { Navigate } from 'react-router-dom'

import { RoleDashboardPlaceholder } from '../components/RoleDashboardPlaceholder'
import { useAuth } from '../auth/useAuth'
import { TriagePage } from './TriagePage'

// DashboardPage is the post-login landing page and acts as a role router.
// Each role will eventually have its own dedicated dashboard. For now, nurses
// land directly on the triage queue and all other roles see a placeholder.
// When a role gets a real dashboard, replace RoleDashboardPlaceholder with the
// actual component here.
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
