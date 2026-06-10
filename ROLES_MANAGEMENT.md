# Role Management System

## Overview

Two roles available in the system:

- **ROLE_USER** : Standard role, assigned by default to users
- **ROLE_ADMIN** : Access to administration interface (EasyAdmin)

## Authentication Flow

The system distinguishes between standard users and administrators from login:

### Standard Users

**Endpoint:** `POST /api/auth/login`

```json
{
  "email": "user@example.com",
  "password": "password123",
  "rememberMe": false
}
```

- Role required: `ROLE_USER`
- Successful response: JWT token + user data
- If user has `ROLE_ADMIN` → **Error 403** : "Admin users must log in via the admin portal."

### Administrators

**Endpoint:** `POST /api/admin/auth/login`

```json
{
  "email": "admin@example.com",
  "password": "password123",
  "rememberMe": false
}
```

- Role required: `ROLE_ADMIN`
- Successful response: JWT token + user data
- If user only has `ROLE_USER` → **Error 403** : "Access denied. Admin privileges are required to access the admin portal."

## Backend (PHP)

### Enum UserRole

```php
enum UserRole: string
{
    case USER = 'ROLE_USER';
    case ADMIN = 'ROLE_ADMIN';

    public static function values(): array { ... }
    public static function isValid(string $role): bool { ... }
    public function getLabel(): string { ... }
}
```

### Role management in User

Roles are stored in a JSON array on the `User` entity.

```php
// By default, all users have ROLE_USER
public function __construct()
{
    $this->role = ['ROLE_USER'];
}

// Get roles (adds ROLE_USER if missing)
$roles = $user->getRoles(); // ['ROLE_USER', 'ROLE_ADMIN']

// Set roles
$user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);

// Check a specific role
$user->isAdmin() // true if ROLE_ADMIN is present
```

### Access control in controllers

```php
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Restrict to ROLE_ADMIN
#[IsGranted('ROLE_ADMIN')]
#[Route('/api/admin/auth/login', methods: ['POST'])]
public function adminLogin(Request $request): JsonResponse
{
    // ...
}
```

### Access control via API Platform

```php
#[ApiResource(
    operations: [
        new Post(
            denormalizationContext: ['groups' => ['user:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
    ]
)]
class User implements UserInterface
{
    // ...
}
```

## Frontend (TypeScript/React)

```typescript
interface User {
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  fullName: string;
  roles: string[];
  isAdmin: boolean;
  isActive: boolean;
  createdAt: string;
  lastLoginAt?: string;
}

// Check permissions
if (user.isAdmin) {
  // Display admin menu or redirect
}

// Display link to admin interface
{user.isAdmin && (
  <a href="/admin">Dashboard Admin</a>
)}
```

## Authentication & Tokens

### JWT Tokens (HttpOnly Cookies)

Two tokens are generated during login:

1. **access_token** (1 hour) - For API calls
2. **refresh_token** (30 days or 1 day depending on "Remember Me")

Tokens are sent as **HttpOnly cookies** and are **never exposed in JSON**.

### Authentication endpoints

| Endpoint                   | Method | Required role | Return          |
| -------------------------- | ------ | ------------- | --------------- |
| `/api/auth/register`       | POST   | ❌            | User + tokens   |
| `/api/auth/login`          | POST   | ❌            | User + tokens   |
| `/api/auth/logout`         | POST   | ✅            | Success message |
| `/api/auth/me`             | GET    | ✅            | Current user    |
| `/api/admin/auth/register` | POST   | ❌            | User + tokens   |
| `/api/admin/auth/login`    | POST   | ❌            | User + tokens   |
| `/api/admin/auth/logout`   | POST   | ✅            | Success message |
| `/api/admin/auth/me`       | GET    | ✅            | Current user    |

## Security

### Fundamental rules

1. **Mandatory backend verification** - Never trust frontend roles
2. **Login distinction** - Admins and standard users log in on different endpoints
3. **API Platform** - Les entités ont `security: "is_granted('ROLE_ADMIN')"` sur POST/PUT/DELETE
4. **EasyAdmin** - Nécessite `ROLE_USER` ou `ROLE_ADMIN` (via `access_control`)

### Firewall Configuration

```yaml
# config/packages/security.yaml
access_control:
  - { path: ^/admin/login, roles: PUBLIC_ACCESS }
  - { path: ^/admin, roles: [ROLE_USER, ROLE_ADMIN] }
  - { path: ^/api/auth, roles: PUBLIC_ACCESS }
  - { path: ^/api/admin/auth, roles: PUBLIC_ACCESS }
  - { path: ^/api/docs, roles: PUBLIC_ACCESS }
```

## Usage Scenarios

### 1. Standard User

```json
{
  "id": 1,
  "email": "user@example.com",
  "roles": ["ROLE_USER"],
  "isAdmin": false
}
```

- ✅ Access to `/api/auth/*`
- ✅ Access to `/api/organizations`, `/api/applications`, `/api/subscriptions`
- ❌ Access to `/api/admin/auth/*`
- ❌ Access to `/admin`

### 2. Administrator

```json
{
  "id": 2,
  "email": "admin@example.com",
  "roles": ["ROLE_USER", "ROLE_ADMIN"],
  "isAdmin": true
}
```

- ✅ Access to `/api/admin/auth/*`
- ✅ Access to `/admin` (EasyAdmin Dashboard)
- ✅ Access to API Platform (POST/PUT/DELETE)
- ✅ Access to `/api/organizations`, `/api/applications`, `/api/subscriptions` (GET/POST)

## User Creation

### Via CLI (Create test users)

```bash
# Standard user
php bin/console app:create-user \
  user@example.com \
  Jean \
  Dupont \
  SecurePassword123! \
  --organization=1

# Administrator
php bin/console app:create-user \
  admin@example.com \
  Admin \
  User \
  SecurePassword123! \
  --admin \
  --organization=1
```

### Via API

```bash
# Register standard user
POST /api/auth/register
{
  "email": "user@example.com",
  "password": "password123",
  "firstName": "John",
  "lastName": "Doe"
}

# Register admin
POST /api/admin/auth/register
{
  "email": "admin@example.com",
  "password": "password123",
  "firstName": "Admin",
  "lastName": "User"
}
```

isAdmin: true

```

- Access to applications
- Access to administration interface

### 3. Pure Admin (rare)

Registration endpoints

- `POST /api/auth/register` : Standard registration (ROLE_USER)
- `POST /api/admin/auth/register` : Admin registration (ROLE_USER + ROLE_ADMIN)

## Best Practices

1. Always verify roles on backend side
2. Use `$user->isAdmin()` instead of manual checks
3. Test permissions in unit tests
```
