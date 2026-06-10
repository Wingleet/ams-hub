# Frontend Dockerfile - React/Vite Application

# Development stage - runs Vite dev server
FROM node:20-alpine AS development

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci

# Copy source files
COPY . .

# Expose Vite dev server port
EXPOSE 5173

# Start Vite dev server
CMD ["npm", "run", "dev"]

# Build stage
FROM node:20-alpine AS build

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci

# Copy source files
COPY . .

# Build the application
RUN npm run build

# Production stage - serves built app with Caddy
FROM caddy:2-alpine AS production

WORKDIR /app

# Copy built assets from build stage
COPY --from=build /app/dist /app/dist

# Copy Caddy configuration
COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 80
EXPOSE 443

CMD ["caddy", "run", "--config", "/etc/caddy/Caddyfile"]
