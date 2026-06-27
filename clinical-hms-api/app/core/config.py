from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    # Central configuration loaded from environment variables or .env.
    # SQLite is used for quick local dev; PostgreSQL is used inside Docker.
    project_name: str = "Clinical HMS API"
    environment: str = "local"
    database_url: str
    # Optional for now — wired in .env for future caching and background jobs.
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
