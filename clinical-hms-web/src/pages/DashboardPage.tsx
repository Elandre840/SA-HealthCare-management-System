import { useAuth } from '../auth/useAuth'
import type { StaffRole } from '../types/auth'

const dashboardTitles: Record<StaffRole, string> = {
  admin: 'Admin Dashboard',
  reception: 'Reception Dashboard',
  nurse: 'Nurse Dashboard',
  doctor: 'Doctor Dashboard',
  pharmacist: 'Pharmacist Dashboard',
}

export function DashboardPage() {
  const { user } = useAuth()
  const title = user?.role ? dashboardTitles[user.role] : 'Staff Dashboard'

  return (
    <section className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
      <p className="text-sm font-semibold uppercase tracking-wide text-teal-700">
        Authenticated area
      </p>
      <h2 className="mt-2 text-3xl font-bold tracking-tight text-slate-950">{title}</h2>
      <p className="mt-3 max-w-2xl text-slate-600">
        This is the protected placeholder for the current role. Clinical modules can
        be added here once the authentication foundation is confirmed.
      </p>
    </section>
  )
}
