from fastapi import APIRouter


router = APIRouter()


# /health is intentionally kept at the root path with no /api/v1 prefix.
# Infrastructure probes (Docker HEALTHCHECK, load balancers, uptime monitors)
# expect a stable, unversioned URL. The prefix must never change for these callers.
@router.get("/health")
def health_check() -> dict[str, str]:
    return {"status": "ok"}
