/**
 * useAuth — convenience hook for consuming the authentication context.
 *
 * Throws if called outside of AuthProvider (which is mounted at the root in
 * main.tsx) so that missing-provider bugs surface immediately as clear errors
 * rather than silent null-reference crashes downstream.
 *
 * Returns: { api, user, status, login, logout, refreshCurrentUser }
 * See AuthContext.tsx for the full AuthContextValue type.
 */

import { useContext } from 'react'

import { AuthContext } from './AuthContext'

export function useAuth() {
  const value = useContext(AuthContext)

  if (!value) {
    throw new Error('useAuth must be used inside AuthProvider')
  }

  return value
}
