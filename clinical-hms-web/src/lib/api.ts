import type { LoginRequest, TokenResponse, User } from '../types/auth'
import type { TriageQueueItem, VitalsCreate } from '../types/triage'

type ApiClientOptions = {
  getAccessToken: () => string | null
  getRefreshToken: () => string | null
  onSessionRefresh: (tokens: TokenResponse) => void
  onUnauthorized: () => void
}

// All paths passed to request() or authenticatedRequest() are relative to
// API_BASE_URL and must NOT repeat the /api/v1 segment — it is already
// in the base URL. E.g. use '/auth/login', not '/api/v1/auth/login'.
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api/v1'

export class ApiError extends Error {
  readonly status: number
  readonly payload: unknown

  constructor(message: string, status: number, payload: unknown) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.payload = payload
  }
}

async function parseResponse(response: Response): Promise<unknown> {
  const text = await response.text()

  if (!text) {
    return null
  }

  try {
    return JSON.parse(text)
  } catch {
    return text
  }
}

async function request<T>(
  path: string,
  options: RequestInit = {},
  accessToken?: string | null,
): Promise<T> {
  const headers = new Headers(options.headers)

  if (!headers.has('Content-Type') && options.body) {
    headers.set('Content-Type', 'application/json')
  }

  if (accessToken) {
    headers.set('Authorization', `Bearer ${accessToken}`)
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers,
  })

  const payload = await parseResponse(response)

  if (!response.ok) {
    const fallbackMessage = `Request failed with status ${response.status}`
    const message =
      typeof payload === 'object' && payload && 'detail' in payload
        ? String(payload.detail)
        : fallbackMessage

    throw new ApiError(message, response.status, payload)
  }

  return payload as T
}

export function createApiClient(options: ApiClientOptions) {
  async function authenticatedRequest<T>(
    path: string,
    requestOptions: RequestInit = {},
  ): Promise<T> {
    try {
      return await request<T>(path, requestOptions, options.getAccessToken())
    } catch (error) {
      if (!(error instanceof ApiError) || error.status !== 401) {
        throw error
      }

      const refreshToken = options.getRefreshToken()

      if (!refreshToken) {
        options.onUnauthorized()
        throw error
      }

      try {
        // On a 401, try refreshing the access token once before giving up.
        // This keeps the user signed in across normal shifts without requiring
        // them to re-login every 60 minutes.
        const tokens = await request<TokenResponse>('/auth/refresh', {
          method: 'POST',
          body: JSON.stringify({ refresh_token: refreshToken }),
        })
        options.onSessionRefresh(tokens)

        return await request<T>(path, requestOptions, tokens.access_token)
      } catch (refreshError) {
        options.onUnauthorized()
        throw refreshError
      }
    }
  }

  return {
    login(credentials: LoginRequest) {
      return request<TokenResponse>('/auth/login', {
        method: 'POST',
        body: JSON.stringify(credentials),
      })
    },
    getCurrentUser() {
      return authenticatedRequest<User>('/auth/me')
    },
    logout() {
      return authenticatedRequest<void>('/auth/logout', {
        method: 'POST',
      })
    },
    getTriageQueue() {
      return authenticatedRequest<TriageQueueItem[]>('/triage/queue')
    },
    submitVitals(visitId: number, vitals: VitalsCreate) {
      return authenticatedRequest<void>(`/triage/${visitId}/vitals`, {
        method: 'POST',
        body: JSON.stringify(vitals),
      })
    },
    request: authenticatedRequest,
  }
}
