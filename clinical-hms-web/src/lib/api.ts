/**
 * API client factory for the Clinical HMS backend.
 *
 * All network calls go through the two-layer architecture below:
 *
 *   request()                — bare fetch wrapper; handles JSON parsing and
 *                              converts non-2xx responses to ApiError instances.
 *
 *   authenticatedRequest()   — wraps request() with automatic 401 recovery:
 *     1. Calls request() with the current access token.
 *     2. On a 401, attempts to refresh the token via POST /auth/refresh.
 *     3. Retries the original request with the new access token.
 *     4. If the refresh also fails, calls onUnauthorized() which clears the
 *        session and redirects to /login.
 *
 * Usage
 * -----
 * Never instantiate ApiClient directly. Use the api object from useAuth():
 *   const { api } = useAuth()
 *   const queue = await api.getTriageQueue()
 *
 * The api client is memoised in AuthContext and recreated only when the stored
 * tokens change, so every component gets the same instance per session.
 */

import type { LoginRequest, TokenResponse, User } from '../types/auth'
import type {
  ConsultationClose,
  ConsultationCreate,
  ConsultationQueueItem,
  ConsultationResponse,
  PharmacyQueueItem,
  PrescriptionCreate,
  PrescriptionResponse,
} from '../types/consultation'
import type { FacilityCreate, FacilityResponse } from '../types/facility'
import type { PatientCreate, PatientResponse, PatientVisitResponse } from '../types/patient'
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
        body: JSON.stringify({
          refresh_token: options.getRefreshToken(),
        }),
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
    setTriagePriority(visitId: number, priority: string) {
      return authenticatedRequest<void>(`/triage/${visitId}/priority`, {
        method: 'PATCH',
        body: JSON.stringify({ priority }),
      })
    },

    // Patients
    registerPatient(data: PatientCreate) {
      return authenticatedRequest<PatientVisitResponse>('/patients/', {
        method: 'POST',
        body: JSON.stringify(data),
      })
    },
    listPatients(search?: string) {
      const qs = search ? `?search=${encodeURIComponent(search)}` : ''
      return authenticatedRequest<PatientResponse[]>(`/patients/${qs}`)
    },

    // Facilities
    listFacilities() {
      return authenticatedRequest<FacilityResponse[]>('/facilities/')
    },
    createFacility(data: FacilityCreate) {
      return authenticatedRequest<FacilityResponse>('/facilities/', {
        method: 'POST',
        body: JSON.stringify(data),
      })
    },

    // Consultations
    getConsultationQueue() {
      return authenticatedRequest<ConsultationQueueItem[]>('/consultations/queue')
    },
    openConsultation(data: ConsultationCreate) {
      return authenticatedRequest<ConsultationResponse>('/consultations/', {
        method: 'POST',
        body: JSON.stringify(data),
      })
    },
    getConsultation(consultationId: number) {
      return authenticatedRequest<ConsultationResponse>(`/consultations/${consultationId}`)
    },
    addPrescription(consultationId: number, data: PrescriptionCreate) {
      return authenticatedRequest<PrescriptionResponse>(
        `/consultations/${consultationId}/prescriptions`,
        { method: 'POST', body: JSON.stringify(data) },
      )
    },
    closeConsultation(consultationId: number, data: ConsultationClose) {
      return authenticatedRequest<{ consultation_id: number; visit_status: string; pending_prescriptions: number }>(
        `/consultations/${consultationId}/close`,
        { method: 'POST', body: JSON.stringify(data) },
      )
    },

    // Pharmacy
    getPharmacyQueue() {
      return authenticatedRequest<PharmacyQueueItem[]>('/pharmacy/queue')
    },
    getVisitPrescriptions(visitId: number) {
      return authenticatedRequest<PrescriptionResponse[]>(`/pharmacy/visits/${visitId}/prescriptions`)
    },
    dispensePrescription(prescriptionId: number) {
      return authenticatedRequest<PrescriptionResponse>(
        `/pharmacy/prescriptions/${prescriptionId}/dispense`,
        { method: 'PATCH' },
      )
    },
    completeVisit(visitId: number) {
      return authenticatedRequest<{ visit_id: number; status: string }>(
        `/pharmacy/visits/${visitId}/complete`,
        { method: 'POST' },
      )
    },

    request: authenticatedRequest,
  }
}
