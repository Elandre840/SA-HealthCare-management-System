"""clinical layer: patients, visits, vitals, consultations, prescriptions, audit_log

Revision ID: 20260708_0002
Revises: 20260616_0001
Create Date: 2026-07-08
"""

from collections.abc import Sequence

import sqlalchemy as sa
from alembic import op
from sqlalchemy.dialects.postgresql import ENUM as PgENUM


revision: str = "20260708_0002"
down_revision: str | None = "20260616_0001"
branch_labels: str | Sequence[str] | None = None
depends_on: str | Sequence[str] | None = None


def upgrade() -> None:
    # --- Enums ---
    op.execute(sa.text(
        "CREATE TYPE visitstatus AS ENUM "
        "('awaiting_triage','awaiting_consultation','in_consultation','awaiting_pharmacy','completed')"
    ))
    op.execute(sa.text(
        "CREATE TYPE triagepriority AS ENUM ('green','yellow','orange','red')"
    ))
    op.execute(sa.text(
        "CREATE TYPE dispensestatus AS ENUM ('pending','dispensed')"
    ))

    # --- patients ---
    op.create_table(
        "patients",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("first_name", sa.String(length=100), nullable=False),
        sa.Column("surname", sa.String(length=100), nullable=False),
        sa.Column("id_number", sa.String(length=13), nullable=True),
        sa.Column("date_of_birth", sa.Date(), nullable=True),
        sa.Column("gender", sa.String(length=20), nullable=True),
        sa.Column("contact_number", sa.String(length=20), nullable=True),
        sa.Column("next_of_kin_name", sa.String(length=200), nullable=True),
        sa.Column("next_of_kin_contact", sa.String(length=20), nullable=True),
        sa.Column("folder_number", sa.String(length=50), nullable=False),
        sa.Column("facility_id", sa.Integer(), nullable=False),
        sa.Column("created_by_id", sa.Integer(), nullable=True),
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.ForeignKeyConstraint(["created_by_id"], ["users.id"]),
        sa.ForeignKeyConstraint(["facility_id"], ["facilities.id"]),
        sa.PrimaryKeyConstraint("id"),
        sa.UniqueConstraint("id_number"),
        sa.UniqueConstraint("folder_number"),
    )
    op.create_index(op.f("ix_patients_id"), "patients", ["id"], unique=False)
    op.create_index(op.f("ix_patients_folder_number"), "patients", ["folder_number"], unique=True)

    # --- visits ---
    op.create_table(
        "visits",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("patient_id", sa.Integer(), nullable=False),
        sa.Column("facility_id", sa.Integer(), nullable=False),
        sa.Column(
            "status",
            PgENUM("awaiting_triage", "awaiting_consultation", "in_consultation",
                   "awaiting_pharmacy", "completed", name="visitstatus", create_type=False),
            nullable=False,
        ),
        sa.Column("reason_for_visit", sa.Text(), nullable=False),
        sa.Column(
            "triage_priority",
            PgENUM("green", "yellow", "orange", "red", name="triagepriority", create_type=False),
            nullable=True,
        ),
        sa.Column("checked_in_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.Column("checked_in_by_id", sa.Integer(), nullable=True),
        sa.Column("triaged_by_id", sa.Integer(), nullable=True),
        sa.Column("triaged_at", sa.DateTime(timezone=True), nullable=True),
        sa.Column("doctor_id", sa.Integer(), nullable=True),
        sa.Column("completed_at", sa.DateTime(timezone=True), nullable=True),
        sa.ForeignKeyConstraint(["checked_in_by_id"], ["users.id"]),
        sa.ForeignKeyConstraint(["doctor_id"], ["users.id"]),
        sa.ForeignKeyConstraint(["facility_id"], ["facilities.id"]),
        sa.ForeignKeyConstraint(["patient_id"], ["patients.id"]),
        sa.ForeignKeyConstraint(["triaged_by_id"], ["users.id"]),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_index(op.f("ix_visits_id"), "visits", ["id"], unique=False)

    # --- vitals ---
    op.create_table(
        "vitals",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("visit_id", sa.Integer(), nullable=False),
        sa.Column("temperature", sa.Float(), nullable=True),
        sa.Column("bp_systolic", sa.Integer(), nullable=True),
        sa.Column("bp_diastolic", sa.Integer(), nullable=True),
        sa.Column("heart_rate", sa.Integer(), nullable=True),
        sa.Column("oxygen_saturation", sa.Integer(), nullable=True),
        sa.Column("respiratory_rate", sa.Integer(), nullable=True),
        sa.Column("weight_kg", sa.Float(), nullable=True),
        sa.Column("recorded_by_id", sa.Integer(), nullable=False),
        sa.Column("recorded_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.ForeignKeyConstraint(["recorded_by_id"], ["users.id"]),
        sa.ForeignKeyConstraint(["visit_id"], ["visits.id"]),
        sa.PrimaryKeyConstraint("id"),
        sa.UniqueConstraint("visit_id"),
    )
    op.create_index(op.f("ix_vitals_id"), "vitals", ["id"], unique=False)

    # --- consultations ---
    op.create_table(
        "consultations",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("visit_id", sa.Integer(), nullable=False),
        sa.Column("doctor_id", sa.Integer(), nullable=False),
        sa.Column("chief_complaint", sa.Text(), nullable=False),
        sa.Column("diagnosis_text", sa.Text(), nullable=True),
        sa.Column("icd10_code", sa.String(length=20), nullable=True),
        sa.Column("notes", sa.Text(), nullable=True),
        sa.Column("amendment_reason", sa.Text(), nullable=True),
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.Column("updated_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.ForeignKeyConstraint(["doctor_id"], ["users.id"]),
        sa.ForeignKeyConstraint(["visit_id"], ["visits.id"]),
        sa.PrimaryKeyConstraint("id"),
        sa.UniqueConstraint("visit_id"),
    )
    op.create_index(op.f("ix_consultations_id"), "consultations", ["id"], unique=False)

    # --- prescriptions ---
    op.create_table(
        "prescriptions",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("consultation_id", sa.Integer(), nullable=False),
        sa.Column("visit_id", sa.Integer(), nullable=False),
        sa.Column("medication_name", sa.String(length=200), nullable=False),
        sa.Column("dosage", sa.String(length=100), nullable=False),
        sa.Column("frequency", sa.String(length=100), nullable=False),
        sa.Column("duration", sa.String(length=100), nullable=False),
        sa.Column("quantity", sa.Integer(), nullable=False),
        sa.Column(
            "dispense_status",
            PgENUM("pending", "dispensed", name="dispensestatus", create_type=False),
            nullable=False,
        ),
        sa.Column("dispensed_by_id", sa.Integer(), nullable=True),
        sa.Column("dispensed_at", sa.DateTime(timezone=True), nullable=True),
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.ForeignKeyConstraint(["consultation_id"], ["consultations.id"]),
        sa.ForeignKeyConstraint(["dispensed_by_id"], ["users.id"]),
        sa.ForeignKeyConstraint(["visit_id"], ["visits.id"]),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_index(op.f("ix_prescriptions_id"), "prescriptions", ["id"], unique=False)

    # --- audit_log ---
    op.create_table(
        "audit_log",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("action", sa.String(length=100), nullable=False),
        sa.Column("actor_id", sa.Integer(), nullable=True),
        sa.Column("actor_role", sa.String(length=50), nullable=True),
        sa.Column("entity_type", sa.String(length=50), nullable=True),
        sa.Column("entity_id", sa.Integer(), nullable=True),
        sa.Column("details", sa.Text(), nullable=True),
        sa.Column("timestamp", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.ForeignKeyConstraint(["actor_id"], ["users.id"]),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_index(op.f("ix_audit_log_id"), "audit_log", ["id"], unique=False)
    op.create_index(op.f("ix_audit_log_action"), "audit_log", ["action"], unique=False)
    op.create_index(op.f("ix_audit_log_timestamp"), "audit_log", ["timestamp"], unique=False)


def downgrade() -> None:
    op.drop_table("audit_log")
    op.drop_table("prescriptions")
    op.drop_table("consultations")
    op.drop_table("vitals")
    op.drop_table("visits")
    op.drop_table("patients")

    op.execute(sa.text("DROP TYPE IF EXISTS dispensestatus"))
    op.execute(sa.text("DROP TYPE IF EXISTS triagepriority"))
    op.execute(sa.text("DROP TYPE IF EXISTS visitstatus"))
