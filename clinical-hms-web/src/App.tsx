import { Navigate, Route, Routes } from 'react-router-dom'

import { ProtectedRoute } from './auth/ProtectedRoute'
import { AppShell } from './components/AppShell'
import { DashboardPage } from './pages/DashboardPage'
import { LoginPage } from './pages/LoginPage'
import { TriagePage } from './pages/TriagePage'
import { UnauthorizedPage } from './pages/UnauthorizedPage'

function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/unauthorized" element={<UnauthorizedPage />} />
      <Route element={<ProtectedRoute />}>
        <Route element={<AppShell />}>
          <Route path="/dashboard" element={<DashboardPage />} />
          <Route element={<ProtectedRoute allowedRoles={['nurse']} />}>
            <Route path="/triage" element={<TriagePage />} />
          </Route>
        </Route>
      </Route>
      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  )
}

export default App
