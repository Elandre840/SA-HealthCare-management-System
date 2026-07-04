"""add triage workflow tables

Revision ID: 20260626_0002
Revises: 20260616_0001
Create Date: 2026-06-26
"""

from collections.abc import Sequence

import sqlalchemy as sa
from alembic import op
from sqlalchemy.dialects.postgresql import ENUM as PgENUM


revision: str = "20260626_0002"
down_revision: str | None = "20260616_0001"
branch_labels: str | Sequence[str] | None = None
depends_on: str | Sequence[str] | None = None


def upgrade() -> None:
    op.execute(sa.text("CREATE TYPE visitstatus AS ENUM ('awaiting_triage', 'triaged', 'with_doctor', 'completed')"))
    op.execute(sa.text("CREATE TYPE triagepriority AS ENUM ('green', 'yellow', 'orange', 'red')"))

    op.create_table(
        "patients",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("first_name", sa.String(length=100), nullable=False),
        sa.Column("surname", sa.String(length=100), nullable=False),
        sa.Column("folder_number", sa.String(length=50), nullable=False),
        sa.Column("facility_id", sa.Integer(), nullable=False),
        sa.ForeignKeyConstraint(["facility_id"], ["facilities.id"]),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_index(op.f("ix_patients_folder_number"), "patients", ["folder_number"], unique=False)
    op.create_index(op.f("ix_patients_id"), "patients", ["id"], unique=False)

    op.create_table(
        "visits",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("patient_id", sa.Integer(), nullable=False),
        sa.Column("facility_id", sa.Integer(), nullable=False),
        sa.Column("reason_for_visit", sa.String(length=255), nullable=False),
        sa.Column("status", PgENUM("awaiting_triage", "triaged", "with_doctor", "completed", name="visitstatus", create_type=False), nullable=False),
        sa.Column(
            "checked_in_at",
            sa.DateTime(timezone=True),
            server_default=sa.text("now()"),
            nullable=False,
        ),
        sa.ForeignKeyConstraint(["facility_id"], ["facilities.id"]),
        sa.ForeignKeyConstraint(["patient_id"], ["patients.id"]),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_index(op.f("ix_visits_id"), "visits", ["id"], unique=False)

    op.create_table(
        "vitals",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("visit_id", sa.Integer(), nullable=False),
        sa.Column("blood_pressure_systolic", sa.Integer(), nullable=False),
        sa.Column("blood_pressure_diastolic", sa.Integer(), nullable=False),
        sa.Column("pulse_rate", sa.Integer(), nullable=False),
        sa.Column("temperature", sa.Float(), nullable=False),
        sa.Column("respiratory_rate", sa.Integer(), nullable=False),
        sa.Column("oxygen_saturation", sa.Integer(), nullable=False),
        sa.Column("weight_kg", sa.Float(), nullable=False),
        sa.Column("height_cm", sa.Float(), nullable=False),
        sa.Column("triage_notes", sa.Text(), nullable=True),
        sa.Column("triage_priority", PgENUM("green", "yellow", "orange", "red", name="triagepriority", create_type=False), nullable=False),
        sa.Column("recorded_by_id", sa.Integer(), nullable=False),
        sa.Column(
            "recorded_at",
            sa.DateTime(timezone=True),
            server_default=sa.text("now()"),
            nullable=False,
        ),
        sa.ForeignKeyConstraint(["recorded_by_id"], ["users.id"]),
        sa.ForeignKeyConstraint(["visit_id"], ["visits.id"]),
        sa.PrimaryKeyConstraint("id"),
        sa.UniqueConstraint("visit_id"),
    )
    op.create_index(op.f("ix_vitals_id"), "vitals", ["id"], unique=False)


def downgrade() -> None:
    op.drop_index(op.f("ix_vitals_id"), table_name="vitals")
    op.drop_table("vitals")
    op.drop_index(op.f("ix_visits_id"), table_name="visits")
    op.drop_table("visits")
    op.drop_index(op.f("ix_patients_id"), table_name="patients")
    op.drop_index(op.f("ix_patients_folder_number"), table_name="patients")
    op.drop_table("patients")

    op.execute(sa.text("DROP TYPE IF EXISTS triagepriority"))
    op.execute(sa.text("DROP TYPE IF EXISTS visitstatus"))
