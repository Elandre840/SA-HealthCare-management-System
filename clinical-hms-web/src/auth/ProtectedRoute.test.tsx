import { render, screen } from '@testing-library/react'
import { MemoryRouter, Route, Routes } from 'react-router-dom'

import { ProtectedRoute } from './ProtectedRoute'
import type { StaffRole, User } from '../types/auth'

const { useAuthMock } = vi.hoisted(() => ({
  useAuthMock: vi.fn(),
}))

vi.mock('./useAuth', () => ({
  useAuth: useAuthMock,
}))

function buildUser(role: StaffRole): User {
  return {
    id: 1,
    account_type: 'staff',
    first_name: 'Demo',
    surname: 'User',
    full_name: 'Demo User',
    email: `${role}@clinicdemo.co.za`,
    role,
    facility_id: 1,
    status: 'active',
    department: 'Demo',
  }
}

function renderProtectedRoute(role: StaffRole | null, status: 'loading' | 'authenticated' | 'unauthenticated' = 'authenticated') {
  useAuthMock.mockReturnValue({
    status,
    user: role ? buildUser(role) : null,
  })

  render(
    <MemoryRouter initialEntries={['/triage']}>
      <Routes>
        <Route path="/login" element={<div>Login page</div>} />
        <Route path="/unauthorized" element={<div>Unauthorized page</div>} />
        <Route element={<ProtectedRoute allowedRoles={['nurse']} />}>
          <Route path="/triage" element={<div>Nurse triage page</div>} />
        </Route>
      </Routes>
    </MemoryRouter>,
  )
}

describe('ProtectedRoute', () => {
  it('allows nurses to access nurse-only routes', () => {
    renderProtectedRoute('nurse')

    expect(screen.getByText('Nurse triage page')).toBeInTheDocument()
  })

  it('redirects reception staff to unauthorized', () => {
    renderProtectedRoute('reception')

    expect(screen.getByText('Unauthorized page')).toBeInTheDocument()
    expect(screen.queryByText('Nurse triage page')).not.toBeInTheDocument()
  })

  it('redirects doctors to unauthorized', () => {
    renderProtectedRoute('doctor')

    expect(screen.getByText('Unauthorized page')).toBeInTheDocument()
    expect(screen.queryByText('Nurse triage page')).not.toBeInTheDocument()
  })
})
