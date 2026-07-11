/**
 * Auth domain types — mirrors the backend user/auth schemas.
 *
 * StaffRole values must match the Python StaffRole enum exactly
 * (app/db/models/user.py). If a new role is added to the backend enum,
 * add it here too and update NAV_ITEMS in AppShell.tsx.
 */

export type StaffRole = 'admin' | 'reception' | 'nurse' | 'doctor' | 'pharmacist'

export type AccountType = 'staff' | 'patient'

export type User = {
  id: number
  account_type: AccountType
  first_name: string
  surname: string
  full_name: string
  email: string
  role: StaffRole | null
  facility_id: number | null
  status: string
  department: string
}

export type LoginRequest = {
  email: string
  password: string
}

export type TokenResponse = {
  access_token: string
  refresh_token?: string
  token_type: string
}

export type AuthSession = {
  accessToken: string
  refreshToken: string | null
}
