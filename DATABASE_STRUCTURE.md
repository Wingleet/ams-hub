# Database Structure

## Conceptual Data Model (CDM)

### ER Diagram

```
┌──────────────┐         ┌────────────────┐         ┌──────────────┐
│    User      │         │ Organization   │         │ Application  │
├──────────────┤         ├────────────────┤         ├──────────────┤
│ id (PK)      │         │ id (PK)        │         │ id (PK)      │
│ email        │         │ name           │         │ name         │
│ password     │         │ icon_url       │         │ description  │
│ firstname    │         │ is_active      │         │ url          │
│ lastname     │         │ deleted_at     │         │ icon_url     │
│ role (JSON)  │         │ created_at     │         │ database_name│
│ is_active    │         │ updated_at     │         │ is_active    │
│ created_at   │◄────────│                │         │ created_at   │
│ updated_at   │  FK     │                │         │ updated_at   │
│ last_login_at│         └────────────────┘         └──────────────┘
│              │                 │                          ▲
└──────────────┘                 │ 1..N                     │
                                 │                          │
                        ┌────────▼────────────┐             │
                        │  Subscription       │             │
                        ├────────────────────┤             │
                        │ id (PK)            │             │
                        │ organization_id (FK)├─────────────┤1
                        │ application_id (FK)├─────────────►
                        │ is_active          │  N..1
                        │ ends_at            │
                        │ created_at         │
                        │ updated_at         │
                        └────────────────────┘
```

### Description of Relations

| Source Entity | Target Entity | Type        | Cardinality | Description                                |
| ------------- | ------------- | ----------- | ----------- | ------------------------------------------ |
| User          | Organization  | Many-to-One | N..1        | A user belongs to one organization         |
| Organization  | User          | One-to-Many | 1..N        | An organization can have multiple users    |
| Organization  | Subscription  | One-to-Many | 1..N        | An organization has multiple subscriptions |
| Application   | Subscription  | One-to-Many | 1..N        | An application has multiple subscriptions  |
| Subscription  | Organization  | Many-to-One | N..1        | A subscription belongs to an organization  |
| Subscription  | Application   | Many-to-One | N..1        | A subscription is linked to an application |

## Entities

### User

Storage: `"user"` (quoted because reserved SQL word)

**Fields:**

- `id` (int, PK, auto-increment)
- `email` (varchar 255, unique)
- `password` (varchar 255, hashed)
- `firstname` (varchar 255)
- `lastname` (varchar 255)
- `role` (json/array) - Default: `['ROLE_USER']`. Can contain: `['ROLE_USER', 'ROLE_ADMIN']`
- `is_active` (boolean, default: true)
- `organization_id` (int, FK nullable, cascade delete) → Organization
- `created_at` (datetime)
- `updated_at` (datetime, via TimestampableTrait)
- `last_login_at` (datetime, nullable)

**Main methods:**

- `getRoles()` - Returns array of roles + mandatory 'ROLE_USER'
- `setRoles(array $roles)`
- `isAdmin()` - Check if ROLE_ADMIN is present

**API Platform Security:**

- GET, GetCollection: `IS_AUTHENTICATED_FULLY`
- POST, PUT, DELETE: `ROLE_ADMIN`

### Organization

Storage: `organization`

**Fields:**

- `id` (int, PK)
- `name` (varchar 255)
- `icon_url` (varchar 255, nullable)
- `is_active` (boolean, default: true)
- `deleted_at` (datetime, nullable)
- `created_at` (datetime)
- `updated_at` (datetime)

**Relations:** One-to-Many → Subscription, Many-to-One ← User

### Application

Storage: `application`

**Fields:**

- `id` (int, PK)
- `name` (varchar 255)
- `description` (varchar 255, nullable)
- `url` (varchar 255, nullable)
- `icon_url` (varchar 255, nullable)
- `database_name` (varchar 255, nullable)
- `is_active` (boolean)
- `created_at` (datetime)
- `updated_at` (datetime)

**Relations:** One-to-Many → Subscription

**Pagination:** `paginationEnabled: false`

### Subscription

Storage: `subscription`

**Fields:**

- `id` (int, PK)
- `organization_id` (int, FK, cascade delete) → Organization
- `application_id` (int, FK) → Application
- `is_active` (boolean)
- `ends_at` (datetime, nullable)
- `created_at` (datetime)
- `updated_at` (datetime)

## Relationship Schema

```
User → Organization (Many-to-One)
Organization → Subscription (One-to-Many)
Application → Subscription (One-to-Many)
```

## Commands

### Migrations

```bash
# Local
php bin/console doctrine:migrations:migrate

# Docker
docker compose exec backend php bin/console doctrine:migrations:migrate
```

### Fixtures

```bash
# Load test data
php bin/console doctrine:fixtures:load --group=dev

# Docker
docker compose exec backend php bin/console doctrine:fixtures:load --group=dev
```

### Inspection

```bash
# View mapped entities
php bin/console doctrine:mapping:info

# Validate schema
php bin/console doctrine:schema:validate

# Auto-generate migrations
php bin/console make:migration
```
