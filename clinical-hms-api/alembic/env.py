"""
Alembic migration environment.

IMPORTANT — adding new models
------------------------------
Every SQLAlchemy model class must be imported in this file (directly or via the
models package __init__) so that Base.metadata is fully populated when Alembic
generates or runs migrations. A missing import causes autogenerate to think the
table does not exist and produce a migration that drops it.

Current models registered: Facility, User, Patient, Visit, Vitals,
                            Consultation, Prescription, AuditLog.
"""

from logging.config import fileConfig

from alembic import context
from sqlalchemy import engine_from_config, pool

from app.core.config import settings
from app.db.base import Base
# All model modules must be imported here before Alembic reads metadata.
# If you add a new model file, import it here to keep autogenerate accurate.
from app.db.models import Facility, Patient, User, Visit, Vitals
from app.db.models.audit_log import AuditLog
from app.db.models.consultation import Consultation
from app.db.models.prescription import Prescription

config = context.config
config.set_main_option("sqlalchemy.url", settings.database_url)

if config.config_file_name is not None:
    fileConfig(config.config_file_name)

target_metadata = Base.metadata


def run_migrations_offline() -> None:
    context.configure(
        url=settings.database_url,
        target_metadata=target_metadata,
        literal_binds=True,
        dialect_opts={"paramstyle": "named"},
    )

    with context.begin_transaction():
        context.run_migrations()


def run_migrations_online() -> None:
    connectable = engine_from_config(
        config.get_section(config.config_ini_section, {}),
        prefix="sqlalchemy.",
        poolclass=pool.NullPool,
    )

    with connectable.connect() as connection:
        context.configure(connection=connection, target_metadata=target_metadata)

        with context.begin_transaction():
            context.run_migrations()


if context.is_offline_mode():
    run_migrations_offline()
else:
    run_migrations_online()

# Suppress "imported but unused" linter warnings — these imports exist solely
# to populate Base.metadata for autogenerate.
_ = (Facility, Patient, User, Visit, Vitals, Consultation, Prescription, AuditLog)
