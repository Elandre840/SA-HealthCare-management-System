/**
 * Audit log domain types — mirrors the backend AuditLogEntry schema.
 *
 * AuditLogEntry is read-only; entries are created server-side by
 * audit_service.log_action() whenever a clinical action occurs.
 * The frontend only ever reads these for display in the admin Audit Log page.
 */

export type AuditLogEntry = {
  id: number
  action: string
  actor_id: number | null
  actor_role: string | null
  entity_type: string | null
  entity_id: number | null
  details: string | null
  timestamp: string
}
