# Architecture - AMS APPS HUB

## Overview

Multi-tenant platform for managing users, organizations and applications with a single client frontend interface and admin dashboard via EasyAdmin.

## Technology Stack

| Composant         | Technology                                       |
| ----------------- | ------------------------------------------------ |
| **Frontend**      | React 19 + TypeScript + Vite                     |
| **Backend**       | Symfony 7.3 + PHP 8.2 + API Platform + EasyAdmin |
| **Database**      | PostgreSQL 16                                    |
| **Reverse Proxy** | Caddy 2                                          |

## Architecture

```
┌──────────────────────────────────────────┐
│    Caddy (Reverse Proxy)                 │
│    http://localhost (port 80)            │
└──────────┬───────────────────────────────┘
           │
   ┌───────┼──────────────┐
   │       │              │
   ▼       ▼              ▼
┌────────────┐  ┌──────────────┐  ┌──────────────┐
│  Frontend  │  │   EasyAdmin  │  │ Backend API  │
│   React    │  │   Symfony    │  │ API Platform │
│   (SPA)    │  │  :8000/admin │  │ Symfony      │
└─────┬──────┘  └──────────────┘  └──────┬───────┘
      │                                   │
      └───────────────────────────────────┤
                                          │
                                 ┌────────▼────────┐
                                 │ PostgreSQL 16   │
                                 │ Port: 5432      │
                                 └─────────────────┘
```

## Authentication Flow

1. **Login** → `/api/auth/login` (standard users) or `/api/admin/auth/login` (administrators)
2. **JWT Token** → Stored in HttpOnly cookie (access_token + refresh_token)
3. **Requests** → Token automatically sent in headers
4. **Authorization** → Backend verification + roles (ROLE_USER, ROLE_ADMIN)

### Role distinction during login

- **`/api/auth/login`** : ROLE_USER role only → Admins are rejected
- **`/api/admin/auth/login`** : ROLE_ADMIN role → Standard users are rejected

## Main Endpoints

| Endpoint                   | Method   | Purpose                                | Auth |
| -------------------------- | -------- | -------------------------------------- | ---- |
| `/api/auth/register`       | POST     | Standard user registration             | ❌   |
| `/api/auth/login`          | POST     | Standard user login                    | ❌   |
| `/api/auth/logout`         | POST     | User logout                            | ✅   |
| `/api/auth/me`             | GET      | Get current user                       | ✅   |
| `/api/admin/auth/register` | POST     | Admin registration                     | ❌   |
| `/api/admin/auth/login`    | POST     | Administrator login                    | ❌   |
| `/api/admin/auth/logout`   | POST     | Administrator logout                   | ✅   |
| `/api/admin/auth/me`       | GET      | Get current admin                      | ✅   |
| `/api/organizations`       | GET/POST | CRUD Organizations                     | ✅   |
| `/api/applications`        | GET/POST | CRUD Applications                      | ✅   |
| `/api/subscriptions`       | GET/POST | CRUD Subscriptions                     | ✅   |
| `/api/docs`                | GET      | Swagger UI (API Documentation)         | ❌   |
| `/admin`                   | GET      | Dashboard d'administration (EasyAdmin) | ✅\* |

\*EasyAdmin requires ROLE_USER or ROLE_ADMIN (verification via login)

## Key Directories

```
├── frontend/                    # Application React (Client SPA)
│   ├── src/
│   │   ├── pages/              # Pages (Home.tsx, Login.tsx)
│   │   ├── routes/             # Routes TanStack Router file-based
│   │   ├── components/         # Reusable components
│   │   ├── services/           # API services (HTTP calls)
│   │   ├── store/              # Global state (Zustand)
│   │   ├── hooks/              # Custom hooks (useAuth)
│   │   ├── types/              # TypeScript definitions
│   │   ├── utils/              # Utility functions
│   │   └── test/               # Tests Vitest
│   ├── vite.config.ts
│   ├── tsconfig.json
│   └── Dockerfile
│
├── backend/                     # API Symfony + EasyAdmin
│   ├── src/
│   │   ├── Controller/         # Controllers (AuthController, Admin/DashboardController)
│   │   ├── Entity/             # Doctrine models (User, Organization, Application, Subscription)
│   │   ├── Repository/         # Database queries
│   │   ├── Service/            # Business logic (AuthService, JwtService, StatsService)
│   │   ├── Security/           # JWT authentication (JwtAuthenticator)
│   │   ├── DTO/                # Data Transfer Objects (LoginRequest, RegisterRequest)
│   │   ├── Enum/               # Enumerations (UserRole)
│   │   ├── Command/            # CLI commands
│   │   ├── EventListener/      # Event listeners
│   │   ├── Trait/              # Reusable traits (TimestampableTrait, SecureCookieTrait)
│   │   └── DataFixtures/       # Test data
│   ├── config/
│   │   ├── packages/           # Symfony configuration (security, api_platform, etc.)
│   │   └── routes/             # API Platform routes
│   ├── tests/                  # PHPUnit tests
│   ├── migrations/             # Doctrine migrations
│   ├── public/                 # Public entry point
│   ├── templates/              # Twig templates (Admin EasyAdmin)
│   ├── composer.json
│   ├── docker-compose.yaml (override)
│   └── Dockerfile
│
├── docker-compose.yml           # Orchestration des services
├── Caddyfile                    # Configuration reverse proxy
└── README.md, ARCHITECTURE.md, etc.  # Documentation
```

## Entity Data Model

```
User (user)
  ├─ id: int (PK)
  ├─ email: string (unique)
  ├─ password: string (hashed)
  ├─ firstname: string
  ├─ lastname: string
  ├─ role: array<string> (JSON - ['ROLE_USER', 'ROLE_ADMIN'])
  ├─ is_active: boolean (default: true)
  ├─ organization_id: int? (FK) ─→ Organization
  ├─ created_at: datetime
  ├─ updated_at: datetime
  └─ last_login_at: datetime?

Organization (organization)
  ├─ id: int (PK)
  ├─ name: string
  ├─ icon_url: string?
  ├─ is_active: boolean (default: true)
  ├─ deleted_at: datetime?
  ├─ created_at: datetime
  ├─ updated_at: datetime
  └─ Relations:
      ├─ users (1..N) ──→ User
      └─ subscriptions (1..N) ──→ Subscription

Application (application)
  ├─ id: int (PK)
  ├─ name: string
  ├─ description: string?
  ├─ url: string?
  ├─ icon_url: string?
  ├─ database_name: string?
  ├─ is_active: boolean
  ├─ created_at: datetime
  ├─ updated_at: datetime
  └─ Relations:
      └─ subscriptions (1..N) ──→ Subscription

Subscription (subscription)
  ├─ id: int (PK)
  ├─ organization_id: int (FK) ──→ Organization
  ├─ application_id: int (FK) ──→ Application
  ├─ is_active: boolean
  ├─ ends_at: datetime?
  ├─ created_at: datetime
  └─ updated_at: datetime
```

│ ├── tests/ # Tests unitaires (PHPUnit)
│ ├── migrations/ # Migrations Doctrine
│ ├── config/ # Configuration Symfony
│ ├── public/ # Web root
│ ├── templates/ # Templates Twig (Admin)
│ ├── composer.json
│ ├── phpunit.xml
│ └── Dockerfile
│
├── docker-compose.yml # Orchestration
├── Caddyfile # Configuration Reverse Proxy
└── 📖 Documentation/

````

## Roles and Permissions

| Role           | Access                          |
| -------------- | ------------------------------- |
| **ROLE_USER**  | `/` (Frontend)                  |
| **ROLE_ADMIN** | `/admin` (Admin) + `/api/admin` |

## Local Development

```bash
# Backend
cd backend && php -S localhost:8000 -t public

# Frontend
cd frontend && npm run dev

# Admin
cd Admin && npm run dev
````

## Docker

```bash
docker compose up -d --build
# All services: http://localhost/
```

## Detailed Documentation

- [DOCKER.md](DOCKER.md) - Setup and deployment
- [ROLES_MANAGEMENT.md](ROLES_MANAGEMENT.md) - Role system
- [DATABASE_STRUCTURE.md](DATABASE_STRUCTURE.md) - Entities
- [TESTS.md](TESTS.md) - Tests
- [AUTHENTICATION_ROLES_DISTINCTION.md](AUTHENTICATION_ROLES_DISTINCTION.md) - Auth flow
