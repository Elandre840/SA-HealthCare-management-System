import { Navigate, Route, Routes } from 'react-router-dom'

import { ProtectedRoute } from './auth/ProtectedRoute'
import { AppShell } from './components/AppShell'
import { DashboardPage } from './pages/DashboardPage'
import { LoginPage } from './pages/LoginPage'
import { TriagePage } from './pages/TriagePage'
import { UnauthorizedPage } from './pages/UnauthorizedPage'

// Route tree for the app. The nesting pattern here is:
//   <ProtectedRoute />           — checks any valid session exists
//     <AppShell />               — renders the nav/layout wrapper
//       /dashboard               — role-aware landing page
//       <ProtectedRoute allowedRoles={['nurse']} /> — role gate
//         /triage                — nurse-only page
// Adding a new role-restricted page: wrap it in a ProtectedRoute with the
// appropriate allowedRoles and nest it inside AppShell.
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
