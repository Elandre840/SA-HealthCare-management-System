# clinical-hms-web/Dockerfile
#
# This Dockerfile is intentionally configured for the development workflow:
#   - Source code is mounted as a volume from docker-compose.yml so Vite's HMR
#     (Hot Module Replacement) reloads the browser on every file save.
#   - node_modules are installed inside the container (not from the host) to
#     avoid native module incompatibilities between macOS/Windows and Alpine Linux.
#
# For a production build, replace the CMD with:
#   RUN npm run build
#   FROM nginx:alpine
#   COPY --from=0 /app/dist /usr/share/nginx/html
# and serve the static output with nginx.

FROM node:22-alpine

WORKDIR /app

# Install dependencies in an isolated layer.
# npm ci (clean install) is used instead of npm install to ensure the exact
# versions in package-lock.json are installed — no accidental upgrades.
COPY package.json package-lock.json ./
RUN npm ci

COPY . .

# VITE_API_BASE_URL is injected at runtime by docker-compose.yml via the
# environment block — Vite reads it at server start, not at build time in dev
# mode. In production (static build), it must be baked in at build time.
CMD ["npm", "run", "dev"]
