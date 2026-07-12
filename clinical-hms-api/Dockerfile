# clinical-hms-api/Dockerfile
#
# Multi-stage build is not used here because the image is for development only
# (source is mounted as a volume in docker-compose.yml). For a production image,
# add a build stage that runs `pip install --no-cache-dir` and copies only the
# installed packages, then copy the application code to a runtime stage without
# pip, keeping the final image small and free of build tools.
#
# Security hardening applied:
#   - Non-root user "app" is created and used at runtime.
#   - PYTHONDONTWRITEBYTECODE prevents .pyc files accumulating in the image.
#   - PYTHONUNBUFFERED ensures stdout/stderr are flushed immediately so Docker
#     logs are visible in real time without buffering.

FROM python:3-alpine3.23

ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1

# Create a non-root system user to run the application. Running as root inside
# a container is a security risk — if the process is exploited, the attacker
# gets root privileges on the host's kernel namespace.
RUN addgroup --system app && adduser --system --ingroup app app

WORKDIR /app

# Install Python dependencies in a separate layer before copying application
# code. Docker layer caching means this layer is only rebuilt when
# requirements.txt changes, not on every source code change.
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy application source and set ownership to the non-root user.
COPY --chown=app:app . .

USER app

# Port 8000 is the uvicorn default and matches the CMD below.
# The docker-compose.yml maps host:8000 → container:8000.
EXPOSE 8000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]