"""
Centralised application configuration.

All runtime settings are read from environment variables (or a .env file at the
project root). Pydantic Settings validates the types and raises a clear error at
startup if a required variable like DATABASE_URL or SECRET_KEY is missing, which
prevents hard-to-debug runtime failures later.

Local development:   copy .env.sqlite.example → .env  (uses SQLite, no Docker)
Docker development:  copy .env.example → .env          (uses PostgreSQL in Docker)
"""

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    # Central configuration loaded from environment variables or .env.
    # SQLite is used for quick local dev; PostgreSQL is used inside Docker.
    project_name: str = "Clinical HMS API"
    environment: str = "local"
    database_url: str
    # Optional for Docker — used for refresh-token revocation. When unset,
    # the API falls back to an in-process store (fine for pytest / SQLite).
    redis_url: str | None = None
    secret_key: str
    access_token_expire_minutes: int = 60
    refresh_token_expire_days: int = 7

    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        extra="ignore",
    )


settings = Settings()
