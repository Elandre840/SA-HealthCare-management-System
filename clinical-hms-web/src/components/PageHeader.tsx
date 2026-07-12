/**
 * PageHeader — shared dark-gradient header for all clinical workspace pages.
 *
 * Consistent with the login page's South African clinic brand:
 *   deep green background · gold section label · Fraunces serif title
 *
 * Usage:
 *   <PageHeader section="Reception" title="Patients" subtitle="Search or register." actions={<button>...</button>} />
 *
 * The parent section should use: className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
 * Page body content goes in: <div className="p-8">...</div>
 */

import type { ReactNode } from 'react'

type PageHeaderProps = {
  section: string
  title: string
  subtitle?: string
  actions?: ReactNode
}

export function PageHeader({ section, title, subtitle, actions }: PageHeaderProps) {
  return (
    <div
      className="flex flex-wrap items-start justify-between gap-4 px-8 py-7"
      style={{
        background: 'linear-gradient(135deg, #073d32 0%, #0d5c4a 55%, #147a5f 100%)',
        fontFamily: '"Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif',
      }}
    >
      <div>
        <p
          className="text-xs font-semibold uppercase tracking-[0.18em]"
          style={{ color: '#c4a35a' }}
        >
          {section}
        </p>
        <h2
          className="mt-2 text-3xl font-semibold tracking-tight text-white"
          style={{ fontFamily: '"Fraunces", Georgia, "Times New Roman", serif' }}
        >
          {title}
        </h2>
        {subtitle ? (
          <p className="mt-2 max-w-2xl text-sm leading-relaxed" style={{ color: 'rgba(204,241,230,0.85)' }}>
            {subtitle}
          </p>
        ) : null}
      </div>

      {actions ? <div className="flex shrink-0 items-center gap-3">{actions}</div> : null}
    </div>
  )
}

/** Pre-styled button for primary actions in a PageHeader (gold). */
export function HeaderActionPrimary({
  children,
  onClick,
  type = 'button',
  disabled,
}: {
  children: ReactNode
  onClick?: () => void
  type?: 'button' | 'submit'
  disabled?: boolean
}) {
  return (
    <button
      type={type}
      onClick={onClick}
      disabled={disabled}
      className="rounded-lg px-4 py-2 text-sm font-semibold transition disabled:opacity-60"
      style={{ background: '#c4a35a', color: '#073d32' }}
      onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.background = '#b8933a' }}
      onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.background = '#c4a35a' }}
    >
      {children}
    </button>
  )
}

/** Pre-styled button for secondary/ghost actions in a PageHeader (white outline). */
export function HeaderActionGhost({
  children,
  onClick,
}: {
  children: ReactNode
  onClick?: () => void
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      className="rounded-lg border border-white/25 bg-white/10 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/20"
    >
      {children}
    </button>
  )
}
