# Docker Compose Setup

## Quick Start

### Development

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

### Production

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

## Configuration Files

- **docker-compose.yml** - Base (shared)
- **docker-compose.dev.yml** - Dev overrides (includes SSO_App, hot-reload)
- **docker-compose.prod.yml** - Prod (no SSO_App, optimized)

## Services

| Service          | Port   | Dev | Prod |
| ---------------- | ------ | --- | ---- |
| Frontend         | 80     | ✅  | ✅   |
| Backend API      | 80/api | ✅  | ✅   |
| SSO App          | 3000   | ✅  | ❌   |
| Database         | 5433   | ✅  | -    |
| Mailer (MailPit) | 8025   | ✅  | ❌   |

## Access

- **Frontend/Admin**: http://localhost
- **API Docs**: http://localhost/api/docs
- **EasyAdmin**: http://localhost/admin
- **SSO** (dev only): http://localhost:3000
- **Mailer** (dev only): http://localhost:8025

## Common Commands

```bash
# Start
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# Logs
docker compose -f docker-compose.yml -f docker-compose.dev.yml logs -f backend

# Stop
docker compose -f docker-compose.yml -f docker-compose.dev.yml down

# Rebuild
docker compose -f docker-compose.yml -f docker-compose.dev.yml build --no-cache

# Database access
docker compose exec database psql -U postgres -d ams-apps-hub

# Symfony commands
docker compose exec backend php bin/console doctrine:migrations:migrate

# Tests
docker compose exec backend php bin/phpunit
docker compose exec frontend npm run test
```

## Environment Variables

Default `.env`:

```env
APP_ENV=dev
DATABASE_URL=postgresql://postgres:postgres@database:5432/ams-apps-hub
VITE_API_URL=http://localhost
SSO_CALLBACK_URL=http://localhost:3000/auth/callback
```

For production, change:

```env
APP_ENV=prod
VITE_API_URL=https://your-domain.com
```

## Notes

- Dev mode includes SSO_App and MailPit; prod excludes SSO_App
- Database volumes persist between restarts
- Default credentials: `postgres` / `postgres`
