"""initial core tables

Revision ID: 20260616_0001
Revises:
Create Date: 2026-06-16
"""

from collections.abc import Sequence

import sqlalchemy as sa
from alembic import op


revision: str = "20260616_0001"
down_revision: str | None = None
branch_labels: str | Sequence[str] | None = None
depends_on: str | Sequence[str] | None = None


account_type = sa.Enum("staff", "patient", name="accounttype")
staff_role = sa.Enum(
    "admin", "reception", "nurse", "doctor", "pharmacist", name="staffrole"
)


def upgrade() -> None:
    account_type.create(op.get_bind(), checkfirst=True)
    staff_role.create(op.get_bind(), checkfirst=True)

    op.create_table(
        "facilities",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("province", sa.String(length=100), nullable=False),
        sa.Column("city", sa.String(length=100), nullable=False),
        sa.Column("name", sa.String(length=150), nullable=False),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_index(op.f("ix_facilities_id"), "facilities", ["id"], unique=False)

    op.create_table(
        "users",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("account_type", account_type, nullable=False),
        sa.Column("first_name", sa.String(length=100), nullable=False),
        sa.Column("surname", sa.String(length=100), nullable=False),
        sa.Column("id_number", sa.String(length=50), nullable=True),
        sa.Column("phone", sa.String(length=20), nullable=True),
        sa.Column("email", sa.String(length=150), nullable=False),
        sa.Column("hashed_password", sa.String(length=255), nullable=False),
        sa.Column("employee_number", sa.String(length=50), nullable=True),
        sa.Column("role", staff_role, nullable=True),
        sa.Column("status", sa.String(length=50), nullable=False),
        sa.Column("department", sa.String(length=50), nullable=False),
        sa.Column("notes", sa.Text(), nullable=True),
        sa.Column(
            "created_at",
            sa.DateTime(timezone=True),
            server_default=sa.text("now()"),
            nullable=False,
        ),
        sa.Column("facility_id", sa.Integer(), nullable=True),
        sa.ForeignKeyConstraint(["facility_id"], ["facilities.id"]),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_index(op.f("ix_users_email"), "users", ["email"], unique=True)
    op.create_index(
        op.f("ix_users_employee_number"), "users", ["employee_number"], unique=True
    )
    op.create_index(op.f("ix_users_id"), "users", ["id"], unique=False)


def downgrade() -> None:
    op.drop_index(op.f("ix_users_id"), table_name="users")
    op.drop_index(op.f("ix_users_employee_number"), table_name="users")
    op.drop_index(op.f("ix_users_email"), table_name="users")
    op.drop_table("users")
    op.drop_index(op.f("ix_facilities_id"), table_name="facilities")
    op.drop_table("facilities")

    staff_role.drop(op.get_bind(), checkfirst=True)
    account_type.drop(op.get_bind(), checkfirst=True)
