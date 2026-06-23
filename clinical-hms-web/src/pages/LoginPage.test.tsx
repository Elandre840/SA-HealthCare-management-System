import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'

import { LoginPage } from './LoginPage'

const { loginMock } = vi.hoisted(() => ({
  loginMock: vi.fn(),
}))

vi.mock('../auth/useAuth', () => ({
  useAuth: () => ({
    login: loginMock,
    status: 'unauthenticated',
  }),
}))

describe('LoginPage', () => {
  beforeEach(() => {
    loginMock.mockReset()
  })

  it('requires an email and password before submitting', async () => {
    render(
      <MemoryRouter>
        <LoginPage />
      </MemoryRouter>,
    )

    await userEvent.click(screen.getByRole('button', { name: /sign in/i }))

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'Enter both your email and password.',
    )
    expect(loginMock).not.toHaveBeenCalled()
  })
})
