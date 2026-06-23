import {
  createContext,
  useCallback,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react'

import { createApiClient } from '../lib/api'
import { clearSession, loadSession, saveSession } from '../lib/session'
import type { AuthSession, LoginRequest, TokenResponse, User } from '../types/auth'

type AuthStatus = 'loading' | 'authenticated' | 'unauthenticated'

export type AuthContextValue = {
  api: ReturnType<typeof createApiClient>
  user: User | null
  status: AuthStatus
  login: (credentials: LoginRequest) => Promise<void>
  logout: () => Promise<void>
  refreshCurrentUser: () => Promise<void>
}

// eslint-disable-next-line react-refresh/only-export-components
export const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [session, setSession] = useState<AuthSession | null>(() => loadSession())
  const [user, setUser] = useState<User | null>(null)
  const [status, setStatus] = useState<AuthStatus>(() =>
    session ? 'loading' : 'unauthenticated',
  )

  const persistTokens = useCallback((tokens: TokenResponse) => {
    const nextSession = {
      accessToken: tokens.access_token,
      refreshToken: tokens.refresh_token ?? null,
    }

    setSession(nextSession)
    setStatus('loading')
    saveSession(nextSession)
  }, [])

  const clearAuthState = useCallback(() => {
    setSession(null)
    setUser(null)
    setStatus('unauthenticated')
    clearSession()
  }, [])

  const api = useMemo(
    () =>
      createApiClient({
        getAccessToken: () => session?.accessToken ?? null,
        getRefreshToken: () => session?.refreshToken ?? null,
        onSessionRefresh: persistTokens,
        onUnauthorized: clearAuthState,
      }),
    [clearAuthState, persistTokens, session],
  )

  const refreshCurrentUser = useCallback(async () => {
    if (!session?.accessToken) {
      return
    }

    try {
      const currentUser = await api.getCurrentUser()
      setUser(currentUser)
      setStatus('authenticated')
    } catch {
      clearAuthState()
    }
  }, [api, clearAuthState, session?.accessToken])

  const login = useCallback(
    async (credentials: LoginRequest) => {
      const tokens = await api.login(credentials)
      persistTokens(tokens)
    },
    [api, persistTokens],
  )

  const logout = useCallback(async () => {
    try {
      await api.logout()
    } finally {
      clearAuthState()
    }
  }, [api, clearAuthState])

  useEffect(() => {
    if (!session?.accessToken) {
      return
    }

    let isActive = true

    // On page refresh, restore the signed-in user from the saved token before
    // showing protected pages. Failed validation clears the session.
    api
      .getCurrentUser()
      .then((currentUser) => {
        if (!isActive) {
          return
        }

        setUser(currentUser)
        setStatus('authenticated')
      })
      .catch(() => {
        if (isActive) {
          clearAuthState()
        }
      })

    return () => {
      isActive = false
    }
  }, [api, clearAuthState, session?.accessToken])

  const value = useMemo(
    () => ({
      api,
      user,
      status,
      login,
      logout,
      refreshCurrentUser,
    }),
    [api, login, logout, refreshCurrentUser, status, user],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

