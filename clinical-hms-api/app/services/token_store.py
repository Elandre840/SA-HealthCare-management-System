"""
Refresh-token revocation store.

Stores issued refresh-token JTIs so stolen or rotated tokens can be rejected.
Uses Redis when REDIS_URL is configured; otherwise falls back to an in-process
dict so local SQLite/pytest runs work without a Redis container.

Key layout: refresh:{jti} → user subject, with TTL matching token expiry.
"""

from __future__ import annotations

import threading
import time
from typing import Protocol

from app.core.config import settings


class TokenStore(Protocol):
    def store(self, jti: str, subject: str, ttl_seconds: int) -> None: ...

    def exists(self, jti: str) -> bool: ...

    def revoke(self, jti: str) -> None: ...


class InMemoryTokenStore:
    """Process-local store used by pytest and SQLite-only local runs."""

    def __init__(self) -> None:
        self._lock = threading.Lock()
        self._entries: dict[str, tuple[str, float]] = {}

    def store(self, jti: str, subject: str, ttl_seconds: int) -> None:
        expires_at = time.monotonic() + max(ttl_seconds, 1)
        with self._lock:
            self._entries[jti] = (subject, expires_at)

    def exists(self, jti: str) -> bool:
        now = time.monotonic()
        with self._lock:
            entry = self._entries.get(jti)
            if entry is None:
                return False
            _subject, expires_at = entry
            if expires_at <= now:
                del self._entries[jti]
                return False
            return True

    def revoke(self, jti: str) -> None:
        with self._lock:
            self._entries.pop(jti, None)

    def clear(self) -> None:
        with self._lock:
            self._entries.clear()


class RedisTokenStore:
    """Redis-backed allow-list of active refresh-token JTIs (Docker Compose)."""

    def __init__(self, redis_url: str) -> None:
        # Imported lazily so pytest / SQLite local runs do not require the package
        # until REDIS_URL is actually configured.
        import redis

        self._client = redis.Redis.from_url(redis_url, decode_responses=True)

    def store(self, jti: str, subject: str, ttl_seconds: int) -> None:
        self._client.setex(f"refresh:{jti}", max(ttl_seconds, 1), subject)

    def exists(self, jti: str) -> bool:
        return bool(self._client.exists(f"refresh:{jti}"))

    def revoke(self, jti: str) -> None:
        self._client.delete(f"refresh:{jti}")


_memory_store = InMemoryTokenStore()
_redis_store: RedisTokenStore | None = None


def get_token_store() -> TokenStore:
    global _redis_store

    if settings.redis_url:
        if _redis_store is None:
            try:
                _redis_store = RedisTokenStore(settings.redis_url)
            except Exception:
                # Missing redis package or unreachable Redis must not break login.
                # Fall back to the in-process store until the image/env is fixed.
                return _memory_store
        return _redis_store

    return _memory_store


def reset_token_store_for_tests() -> None:
    """Reset store state between tests. Forces the in-memory backend in pytest."""
    global _redis_store
    _redis_store = None
    _memory_store.clear()
