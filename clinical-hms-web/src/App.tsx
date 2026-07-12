/**
 * Application route tree.
 *
 * ProtectedRoute is used in two ways:
 *   1. Without allowedRoles — blocks unauthenticated users and redirects to /login.
 *   2. With allowedRoles    — additionally checks the user's role and redirects
 *      to /unauthorized if the role is not in the list.
 *
 * AppShell is an Outlet-based layout component that renders the nav header above
 * all authenticated pages. Adding a new page inside the AppShell route group
 * automatically gives it the shared navigation bar.
 *
 * Route tree:
 *   /login               — public login form
 *   /unauthorized        — public "access denied" page
 *   <ProtectedRoute>     — requires a valid session
 *     <AppShell>         — nav header + <Outlet />
 *       /dashboard       — role router → redirects to the user's module
 *       /facilities      — admin only
 *       /patients        — reception + admin only
 *       /triage          — nurse + admin only
 *       /consultations   — doctor + admin only
 *       /pharmacy        — pharmacist + admin only
 *   /*                   — catch-all → /dashboard (handled by role router)
 */

import { Navigate, Route, Routes } from 'react-router-dom'

import { ProtectedRoute } from './auth/ProtectedRoute'
import { AppShell } from './components/AppShell'
import { ConsultationPage } from './pages/ConsultationPage'
import { DashboardPage } from './pages/DashboardPage'
import { FacilitiesPage } from './pages/FacilitiesPage'
import { LoginPage } from './pages/LoginPage'
import { PatientsPage } from './pages/PatientsPage'
import { PharmacyPage } from './pages/PharmacyPage'
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

          <Route element={<ProtectedRoute allowedRoles={['admin']} />}>
            <Route path="/facilities" element={<FacilitiesPage />} />
          </Route>

          <Route element={<ProtectedRoute allowedRoles={['reception', 'admin']} />}>
            <Route path="/patients" element={<PatientsPage />} />
          </Route>

          <Route element={<ProtectedRoute allowedRoles={['nurse', 'admin']} />}>
            <Route path="/triage" element={<TriagePage />} />
          </Route>

          <Route element={<ProtectedRoute allowedRoles={['doctor', 'admin']} />}>
            <Route path="/consultations" element={<ConsultationPage />} />
          </Route>

          <Route element={<ProtectedRoute allowedRoles={['pharmacist', 'admin']} />}>
            <Route path="/pharmacy" element={<PharmacyPage />} />
          </Route>
        </Route>
      </Route>
      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  )
}

export default App
