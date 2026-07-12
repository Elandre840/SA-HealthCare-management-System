export function UnauthorizedPage() {
  return (
    <main className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-12">
      <section className="w-full max-w-md rounded-2xl bg-white p-8 text-center shadow-xl shadow-slate-200">
        <p className="text-sm font-semibold uppercase tracking-wide text-teal-700">
          Clinical HMS
        </p>
        <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-950">Access denied</h1>
        <p className="mt-3 text-sm text-slate-600">
          Your account does not have permission to view this page.
        </p>
      </section>
    </main>
  )
}
