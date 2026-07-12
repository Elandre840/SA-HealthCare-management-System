# clinical-hms-web/Dockerfile
#
# Multi-stage image:
#   development  — Vite HMR server (default for docker compose)
#   production   — static build served by nginx
#
# Local (dev):
#   docker compose up web
#
# Production image:
#   docker build -t clinical-hms-web:prod --target production \
#     --build-arg VITE_API_BASE_URL=https://api.example.com/api/v1 \
#     ./clinical-hms-web

FROM node:22-alpine AS deps
WORKDIR /app

# npm ci pins exact versions from package-lock.json — no accidental upgrades.
COPY package.json package-lock.json ./
RUN npm ci

# ── Development (Vite HMR) ────────────────────────────────────────────────────
FROM deps AS development
COPY . .
# Source is also mounted as a volume from docker-compose.yml so file saves
# hot-reload in the browser. node_modules stay in an anonymous volume.
EXPOSE 5173
CMD ["npm", "run", "dev"]

# ── Production build ──────────────────────────────────────────────────────────
FROM deps AS build
COPY . .
# Vite bakes VITE_* values into the static bundle at build time.
ARG VITE_API_BASE_URL=http://localhost:8000/api/v1
ENV VITE_API_BASE_URL=$VITE_API_BASE_URL
RUN npm run build

FROM nginx:1.27-alpine AS production
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
