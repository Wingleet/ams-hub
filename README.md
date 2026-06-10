# AMS APPS HUB – Application Management System

Multi-tenant platform for centralized management of users, organizations and applications with a client frontend interface and admin dashboard via EasyAdmin.

## 🚀 Technology Stack

- **Frontend** : React 19 + TypeScript + Vite + TanStack Router/Query + Zustand + Tailwind CSS
- **Backend** : Symfony 7.3 + PHP 8.2 + Doctrine ORM + API Platform + EasyAdmin
- **Database** : PostgreSQL 16
- **Infra** : Docker Compose + Caddy (reverse proxy)

## 📋 Prerequisites

- Docker & Docker Compose
- Node.js 18+
- PHP 8.2+ (local development)
- PostgreSQL 16 (via Docker)

## 🏃 Quick Start

### With Docker (recommended)

**Development** (default):

```bash
docker compose up -d
```

**Production**:

```bash
docker compose -f docker-compose.prod.yml up -d
```

Available services:

- Frontend & Admin : http://localhost
- Backend API : http://localhost/api
- EasyAdmin Dashboard : http://localhost/admin
- SSO Application : http://localhost:3000
- PostgreSQL : localhost:5432

> **ℹ️ SSO Integration**: If you plan to integrate external applications with the SSO system, please refer to [SSO_GUIDE.md](SSO_GUIDE.md) to ensure all pre-requisites are met.

### Environment Differences

#### Development vs Production

| Feature               | Development               | Production                             |
| --------------------- | ------------------------- | -------------------------------------- |
| **Compose File**      | `docker-compose.yml`      | `docker-compose.prod.yml`              |
| **APP_ENV**           | `dev`                     | `prod`                                 |
| **Build Target**      | `development`             | `production`                           |
| **DEFAULT_URI**       | `http://localhost`        | `https://localhost`                    |
| **Database Port**     | `5433` (exposed)          | `5432` (internal only)                 |
| **Frontend Port**     | `5173` (dev server)       | Not exposed (static build)             |
| **Backend Volumes**   | Hot-reload enabled        | Read-only volumes                      |
| **Frontend Volumes**  | Hot-reload enabled        | Not mounted                            |
| **SSO_App Volumes**   | Hot-reload enabled        | Not available in prod                  |
| **CORS_ALLOW_ORIGIN** | `localhost` + `127.0.0.1` | `localhost` only                       |
| **SSO_HUB_URL**       | `http://caddy`            | Not configured (requires external SSO) |
| **Caching**           | Disabled for development  | Enabled for performance                |

**Key Notes:**

- **Dev**: All services run with hot-reload, debug ports exposed, SSO_App included for testing
- **Prod**: Optimized builds, no development ports, SSO_App must be deployed separately or via external provider
- **APP_SECRET** & **JWT_SECRET**: Must be changed in production (see `.env.docker` variables)

### Local Development

**Backend:**

```bash
cd backend
composer install
php bin/console doctrine:migrations:migrate
php bin/console serve
```

**Frontend:**

```bash
cd frontend
npm install
npm run dev
```

## 📁 Project Structure

```
wingleet-ams/
├── frontend/                    # React application (Client SPA)
│   ├── src/
│   │   ├── pages/              # Pages (Home.tsx, Login.tsx)
│   │   ├── routes/             # Routes TanStack Router file-based
│   │   ├── components/         # Reusable components
│   │   ├── services/           # API services (HTTP calls)
│   │   ├── store/              # Global state (Zustand)
│   │   ├── hooks/              # Custom hooks
│   │   ├── types/              # TypeScript types
│   │   ├── utils/              # Utility functions
│   │   └── test/               # Tests Vitest
│   └── Dockerfile
├── backend/                     # API Symfony + EasyAdmin
│   ├── src/
│   │   ├── Controller/         # Controllers (AuthController, SsoController, Admin/DashboardController)
│   │   ├── Entity/             # Doctrine models (User, Organization, Application, Subscription, SsoCode)
│   │   ├── Repository/         # Database queries
│   │   ├── Service/            # Business logic (AuthService, JwtService, SsoService)
│   │   ├── Security/           # JWT authentication
│   │   ├── DTO/                # Data Transfer Objects
│   │   ├── Enum/               # Enumerations (UserRole)
│   │   ├── Command/            # CLI commands (SsoCleanupCommand)
│   │   ├── Trait/              # Reusable traits
│   │   └── DataFixtures/       # Test data
│   ├── config/                 # Symfony configuration
│   ├── tests/                  # PHPUnit tests
│   ├── migrations/             # Doctrine migrations
│   ├── templates/              # Twig templates (Admin)
│   └── Dockerfile
├── SSO_App/                     # Node.js Express SSO authentication server
│   ├── public/                 # Static assets (login, register, callback pages)
│   ├── server.js               # Express server
│   ├── package.json
│   └── Dockerfile
├── docker-compose.yml
├── Caddyfile
├── Makefile
└── 📖 Documentation/
    ├── ARCHITECTURE.md
    ├── DATABASE_STRUCTURE.md
    ├── ROLES_MANAGEMENT.md
    ├── SSO_GUIDE.md
    ├── TESTS.md
    ├── EASYADMIN.md
    └── DOCKER_COMPOSE_GUIDE.md
```

## 🏗️ Architecture

```
Caddy (localhost:80)
    ├─ /         → Frontend React (Client SPA - Login + Home)
    ├─ /admin    → Dashboard EasyAdmin (Symfony - Full Management)
    ├─ /sso/*    → SSO endpoints (Symfony Backend)
    │               ├─ /sso/authorize       (Generate SSO code)
    │               └─ /sso/verify         (Verify SSO code)
    └─ /api      → API Platform REST
                    ├─ /api/auth/*          (Login/Register standard users)
                    ├─ /api/admin/auth/*    (Login/Register administrators)
                    ├─ /api/organizations   (CRUD Organizations)
                    ├─ /api/applications    (CRUD Applications)
                    ├─ /api/subscriptions   (CRUD Subscriptions)
                    └─ /api/docs            (Swagger UI)
                            ↓
                    PostgreSQL (port 5432)

SSO App (localhost:3000) - Node.js Express
    ├─ /login        → Login page
    ├─ /register     → Registration page
    ├─ /home         → Home page (after login)
    └─ /callback     → SSO callback (receives code from Hub)
```

### Main Entities

| **Entity**       | Description                                              |
| ---------------- | -------------------------------------------------------- |
| **User**         | User (ROLE_USER or ROLE_ADMIN)                           |
| **Organization** | Multi-tenant organizations                               |
| **Application**  | Available applications (with database_name)              |
| **Subscription** | Organization's subscription to app (is_active + ends_at) |

**Relations**: User → Organization, Organization ↔ Subscription ↔ Application

## 🔐 Authentication & Roles

### Role Distinction on Login

| Endpoint                | Role required | If admin    | If user     |
| ----------------------- | ------------- | ----------- | ----------- |
| `/api/auth/login`       | ROLE_USER     | ❌ Rejected | ✅ OK       |
| `/api/admin/auth/login` | ROLE_ADMIN    | ✅ OK       | ❌ Rejected |

**Error messages:**

- Standard user tries `/api/admin/auth/login` → **403**: "Access denied. Admin privileges are required to access the admin portal."
- Administrator tries `/api/auth/login` → **403**: "Admin users must log in via the admin portal."

### JWT Tokens (HttpOnly Cookies)

```
access_token:   1 hour
refresh_token:  30 days (with "Remember Me") or 1 day
```

Stored as HttpOnly cookies and never exposed in JSON.

### Role Management

- Two available roles: `ROLE_USER` (default) and `ROLE_ADMIN`
- Stored in a JSON array on User entity
- Centralized management at backend level (see [ROLES_MANAGEMENT.md](ROLES_MANAGEMENT.md))
- Tokens/Sessions managed by Symfony with secure HttpOnly cookies

## 🧪 Tests

### Frontend (Vitest)

```bash
cd frontend

npm run test              # Watch mode
npm run test:run         # Single run
npm run test:coverage    # With coverage
npm run test:ui          # Graphical interface
```

### Backend (PHPUnit)

```bash
cd backend

php bin/phpunit                # Tests
php bin/phpunit --coverage-html=coverage/  # With coverage
```

See [TESTS.md](TESTS.md) for complete details.

## � Docker

### Installation & Startup

**Prerequisites** :

- Docker >= 24.0
- Docker Compose >= 2.20

**Start** :

```bash
docker compose up -d --build
```

The backend initializes automatically: dependencies, cache, migrations, and fixtures.

### Available Services

```
Frontend:  http://localhost
Admin:     http://localhost/admin
API:       http://localhost/api
API Docs:  http://localhost/api/docs
Database:  localhost:5432
```

### Essential commands

```bash
# Logs (all services or specific one)
docker compose logs -f
docker compose logs -f backend
docker compose logs -f frontend
docker compose logs -f database

# Stop services
docker compose stop

# Stop and remove containers (data persists)
docker compose down

# Complete removal (data destruction)
docker compose down -v

# Execute commands
docker compose exec backend php bin/console [cmd]
docker compose exec frontend npm run [script]
docker compose exec database psql -U postgres -d wingleet-ams

# Access a shell
docker compose exec backend sh
docker compose exec frontend sh
```

### Troubleshooting

**Port 80 already in use**:

```bash
sudo lsof -i :80
# Stop the process or change the port in docker-compose.yml or docker compose override
```

**Permission denied on volumes**:

```bash
docker compose exec backend chown -R www-data:www-data var/
```

**Database cannot connect**:

```bash
docker compose logs database
docker compose ps  # Check service status
```

**Verify everything works**:

```bash
docker compose ps  # All services should be "Up"
docker compose logs backend | grep -i "listening"  # Backend ready
```

**Complete hard reset**:

```bash
docker compose down -v --remove-orphans
docker system prune -a
docker compose up -d --build
```

## 📝 Configuration

- `.env.docker` : Docker environment variables
- `docker-compose.yml` : Service orchestration
- `Caddyfile` : Reverse proxy configuration

## 🔄 Database Migrations

```bash
# Create a migration
php bin/console make:migration

# Apply migrations
php bin/console doctrine:migrations:migrate

# Load fixtures (dev)
php bin/console doctrine:fixtures:load --group=dev
```

## 📖 Documentation

Consult the files in the root directory for:

- **ARCHITECTURE.md** : Complete technical overview
- **DATABASE_STRUCTURE.md** : Schema and relations
- **ROLES_MANAGEMENT.md** : Roles and permissions management

## 🚀 VM Deployment

To deploy the application on a virtual machine with a custom domain (e.g.: staging-ams-apps-hub.wingleetdev.com), see the complete guide: **[VM_DEPLOYMENT.md](VM_DEPLOYMENT.md)**

The guide covers:

- DNS configuration and automatic SSL certificates (Let's Encrypt via Caddy)
- Docker installation on VM
- Environment configuration (.env)
- Application deployment with `make install-dev`
- Admin user creation and AMS synchronization
- Security and backups

## 📄 License

Voir [LICENSE](LICENSE)
