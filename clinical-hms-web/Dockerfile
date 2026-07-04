FROM node:22-alpine

WORKDIR /app

# Install deps first (separate layer — only re-runs when package files change)
COPY package.json package-lock.json ./
RUN npm ci

COPY . .

# Default: Vite dev server. Override with "npm test" for the test suite.
CMD ["npm", "run", "dev"]
