/**
 * LoginPage — staff authentication entry point.
 *
 * Visual design
 * -------------
 * Split composition: a South African clinic brand panel (left / top on mobile)
 * and a focused sign-in form (right / bottom). Brand name is the hero signal;
 * the form is the only card-like interaction surface.
 *
 * Auth flow
 * ---------
 * Always redirects to /dashboard after login so the role router sends each
 * user to the correct module. Using location.state.from is unsafe because a
 * nurse redirected from /consultations would land on a page they cannot access.
 */

import { useState, type FormEvent } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'

import { useAuth } from '../auth/useAuth'
import { ApiError } from '../lib/api'

export function LoginPage() {
  const navigate = useNavigate()
  const { login, status } = useAuth()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  const redirectTo = '/dashboard'

  if (status === 'authenticated') {
    return <Navigate to={redirectTo} replace />
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError(null)

    if (!email.trim() || !password) {
      setError('Enter both your email and password.')
      return
    }

    setIsSubmitting(true)

    try {
      await login({ email: email.trim(), password })
      navigate(redirectTo, { replace: true })
    } catch (loginError) {
      setError(
        loginError instanceof ApiError
          ? loginError.message
          : 'Unable to sign in. Please try again.',
      )
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <main
      className="login-page relative flex min-h-screen overflow-hidden"
      style={{ fontFamily: '"Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif' }}
    >
      {/* Ambient page background */}
      <div
        aria-hidden="true"
        className="pointer-events-none absolute inset-0 bg-[#f3f7f5]"
      />
      <div
        aria-hidden="true"
        className="pointer-events-none absolute inset-0 opacity-40"
        style={{
          backgroundImage:
            'radial-gradient(circle at 12% 18%, rgba(196,163,90,0.18), transparent 42%), radial-gradient(circle at 88% 78%, rgba(13,92,74,0.12), transparent 45%)',
        }}
      />

      <div className="relative z-10 mx-auto flex w-full max-w-6xl flex-1 flex-col lg:flex-row lg:items-stretch lg:p-6 xl:p-8">
        {/* Brand / atmosphere panel */}
        <section
          aria-label="Clinical HMS brand"
          className="login-brand relative flex min-h-[42vh] flex-1 flex-col justify-between overflow-hidden px-8 py-10 text-white sm:px-12 sm:py-12 lg:min-h-0 lg:rounded-3xl lg:px-12 lg:py-14"
        >
          {/* Landscape wash */}
          <div
            aria-hidden="true"
            className="absolute inset-0"
            style={{
              background:
                'linear-gradient(155deg, #073d32 0%, #0d5c4a 38%, #147a5f 68%, #1a6b55 100%)',
            }}
          />
          {/* Soft dawn light */}
          <div
            aria-hidden="true"
            className="absolute -right-16 -top-20 h-72 w-72 rounded-full opacity-30 blur-2xl"
            style={{ background: 'radial-gradient(circle, #e8c878 0%, transparent 70%)' }}
          />
          {/* Horizon band */}
          <div
            aria-hidden="true"
            className="absolute inset-x-0 bottom-0 h-2/5"
            style={{
              background:
                'linear-gradient(180deg, transparent 0%, rgba(8,45,38,0.55) 100%)',
            }}
          />
          {/* Subtle Ndebele-inspired geometric accent (decorative, not a flag) */}
          <svg
            aria-hidden="true"
            className="absolute bottom-0 left-0 h-40 w-full opacity-[0.14]"
            viewBox="0 0 800 160"
            preserveAspectRatio="none"
          >
            <path d="M0 160 L0 110 L120 70 L240 110 L360 40 L480 110 L600 55 L720 110 L800 80 L800 160 Z" fill="#c4a35a" />
            <path d="M0 160 L0 130 L160 95 L320 130 L480 70 L640 130 L800 100 L800 160 Z" fill="#0a3d32" />
          </svg>
          {/* Soft grid texture */}
          <div
            aria-hidden="true"
            className="absolute inset-0 opacity-[0.07]"
            style={{
              backgroundImage:
                'linear-gradient(rgba(255,255,255,0.5) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.5) 1px, transparent 1px)',
              backgroundSize: '48px 48px',
            }}
          />

          <div className="relative login-fade-in">
            <p
              className="text-sm font-semibold uppercase tracking-[0.22em] text-[#e8c878]"
              style={{ fontFamily: '"Plus Jakarta Sans", sans-serif' }}
            >
              South Africa
            </p>
            <h1
              className="mt-4 max-w-md text-4xl font-semibold leading-[1.1] tracking-tight sm:text-5xl lg:text-[3.25rem]"
              style={{ fontFamily: '"Fraunces", Georgia, serif', fontOpticalSizing: 'auto' }}
            >
              Clinical HMS
            </h1>
            <p className="mt-4 max-w-sm text-base leading-relaxed text-teal-50/90 sm:text-lg">
              Secure staff access for community clinics — registration, triage,
              consultation, and pharmacy in one workflow.
            </p>
          </div>

          <div className="relative mt-10 login-fade-in-delay space-y-4">
            <div className="flex flex-wrap gap-x-6 gap-y-2 text-sm text-teal-50/80">
              <span className="inline-flex items-center gap-2">
                <span className="h-1.5 w-1.5 rounded-sm bg-[#e8c878]" />
                POPIA-aware audit trail
              </span>
              <span className="inline-flex items-center gap-2">
                <span className="h-1.5 w-1.5 rounded-sm bg-[#e8c878]" />
                SATS triage colours
              </span>
              <span className="inline-flex items-center gap-2">
                <span className="h-1.5 w-1.5 rounded-sm bg-[#e8c878]" />
                Role-based access
              </span>
            </div>
            <p className="text-xs text-teal-100/55">
              Built for primary healthcare facilities across the provinces.
            </p>
          </div>
        </section>

        {/* Sign-in panel */}
        <section className="flex flex-1 items-center justify-center px-6 py-10 sm:px-10 lg:px-14 lg:py-8">
          <div className="login-fade-in-form w-full max-w-md">
            <div className="mb-8">
              <p className="text-sm font-semibold uppercase tracking-[0.16em] text-teal-800">
                Staff sign in
              </p>
              <h2
                className="mt-2 text-3xl font-semibold tracking-tight text-slate-950"
                style={{ fontFamily: '"Fraunces", Georgia, serif' }}
              >
                Welcome back
              </h2>
              <p className="mt-2 text-sm leading-relaxed text-slate-600">
                Sign in with your clinic staff account to open your workspace.
              </p>
            </div>

            <form
              className="space-y-5 rounded-2xl border border-slate-200/80 bg-white/90 p-6 shadow-[0_20px_50px_-28px_rgba(13,92,74,0.35)] backdrop-blur-sm sm:p-8"
              onSubmit={handleSubmit}
            >
              <div>
                <label htmlFor="email" className="block text-sm font-medium text-slate-700">
                  Email
                </label>
                <input
                  id="email"
                  name="email"
                  type="email"
                  autoComplete="email"
                  value={email}
                  onChange={(event) => setEmail(event.target.value)}
                  placeholder="you@clinicdemo.co.za"
                  className="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-teal-700 focus:ring-2 focus:ring-teal-100"
                />
              </div>

              <div>
                <label htmlFor="password" className="block text-sm font-medium text-slate-700">
                  Password
                </label>
                <input
                  id="password"
                  name="password"
                  type="password"
                  autoComplete="current-password"
                  value={password}
                  onChange={(event) => setPassword(event.target.value)}
                  className="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-slate-950 outline-none transition focus:border-teal-700 focus:ring-2 focus:ring-teal-100"
                />
              </div>

              {error ? (
                <p
                  role="alert"
                  className="rounded-xl border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm text-red-700"
                >
                  {error}
                </p>
              ) : null}

              <button
                type="submit"
                disabled={isSubmitting}
                className="w-full rounded-xl bg-[#0d5c4a] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#0a4a3c] disabled:cursor-not-allowed disabled:opacity-70"
              >
                {isSubmitting ? 'Signing in...' : 'Sign in'}
              </button>
            </form>

            <p className="mt-5 text-center text-xs text-slate-500">
              Authorised clinic staff only · Demo accounts are seeded for review
            </p>
          </div>
        </section>
      </div>

      <style>{`
        @keyframes loginFadeUp {
          from { opacity: 0; transform: translateY(14px); }
          to { opacity: 1; transform: translateY(0); }
        }
        .login-fade-in {
          animation: loginFadeUp 0.7s ease-out both;
        }
        .login-fade-in-delay {
          animation: loginFadeUp 0.7s ease-out 0.18s both;
        }
        .login-fade-in-form {
          animation: loginFadeUp 0.65s ease-out 0.12s both;
        }
        @media (prefers-reduced-motion: reduce) {
          .login-fade-in,
          .login-fade-in-delay,
          .login-fade-in-form {
            animation: none;
          }
        }
      `}</style>
    </main>
  )
}
