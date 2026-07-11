import type { AuthSession } from '../types/auth'

// Tokens are stored in sessionStorage, not localStorage.
// sessionStorage is cleared when the tab is closed, which limits the exposure
// window for a stolen token. localStorage would survive browser restarts and
// is a bigger target for XSS. Change this only if persistent login across
// tabs/sessions is an explicit product requirement.
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
