import type { AuthSession } from '../types/auth'

const SESSION_KEY = 'clinical-hms.auth'

export function loadSession(): AuthSession | null {
  const stored = window.sessionStorage.getItem(SESSION_KEY)

  if (!stored) {
    return null
  }

  try {
    return JSON.parse(stored) as AuthSession
  } catch {
    window.sessionStorage.removeItem(SESSION_KEY)
    return null
  }
}

export function saveSession(session: AuthSession): void {
  window.sessionStorage.setItem(SESSION_KEY, JSON.stringify(session))
}

export function clearSession(): void {
  window.sessionStorage.removeItem(SESSION_KEY)
}
