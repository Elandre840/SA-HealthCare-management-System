"""
SQLAlchemy declarative base shared by all ORM models.

Every model class (Facility, User, Patient, Visit, etc.) inherits from Base so
that SQLAlchemy registers the table definitions in one unified metadata object.
Alembic then reads that metadata to generate and apply database migrations.

All model modules must be imported before Alembic runs — see alembic/env.py.
"""

from sqlalchemy.orm import DeclarativeBase


class Base(DeclarativeBase):
    pass
