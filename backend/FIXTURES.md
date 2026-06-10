# Fixtures - Test Data

## Load Fixtures

```bash
# Load test fixtures (group 'test')
php bin/console doctrine:fixtures:load --group=test

# Add to existing data (without deleting)
php bin/console doctrine:fixtures:load --group=test --append

# Via Docker
docker compose exec backend php bin/console doctrine:fixtures:load --group=test
```

## Create Users Manually

### Standard User

```bash
# Local
php bin/console app:create-user \
  email@example.com \
  Jean \
  Dupont \
  SecurePassword123! \
  --organization=1

# Via Docker
docker compose exec backend php bin/console app:create-user \
  email@example.com \
  Jean \
  Dupont \
  SecurePassword123! \
  --organization=1
```

### Administrator

```bash
# Local
php bin/console app:create-user \
  admin@example.com \
  Admin \
  User \
  SecurePassword123! \
  --admin \
  --organization=1

# Via Docker
docker compose exec backend php bin/console app:create-user \
  admin@example.com \
  Admin \
  User \
  SecurePassword123! \
  --admin \
  --organization=1
```

### Available Options

| Option                        | Description                 |
| ----------------------------- | --------------------------- |
| `--admin` / `-a`              | Add ROLE_ADMIN role         |
| `--organization=ID` / `-o ID` | Assign to organization (ID) |
| `--inactive` / `-i`           | Create user as inactive     |

### Additional Examples

```bash
# Administrator without organization
php bin/console app:create-user admin@domain.com Admin User Password123! --admin

# Inactive user
php bin/console app:create-user inactive@example.com Jean Doe Password123! --inactive

# Active user assigned to org 5
php bin/console app:create-user user@example.com Marie Martin Password123! -o 5
```

## Created Data

### Organizations

- **20 organizations** : `Organization 1` to `Organization 20`
- Status : active (`is_active: true`)

### Applications

- **20 applications** : `Application 1` to `Application 20`
- Each application has:
    - Name : `Application X`
    - Description : `Description for application X`
    - URL : `https://appX.example.com`
    - Database name : `app_test_XX_db` (automatically generated)
    - Status : active (`is_active: true`)

### Users

- **60 regular users** : `user1@example.com` to `user60@example.com`
    - Distributed : 3 users per organization
    - Random names (French first/last names)
    - Password : `Password123!`
    - Status : active

- **1 test user** : `test@example.com`
    - Password : `TestPassword123!`
    - Assigned to first organization
    - Status : active

## File Structure

```
backend/src/DataFixtures/
└── TestFixtures.php         # Fixture loading class
```

## Features

- **Duplicate protection** : Fixtures detect and don't recreate data if it already exists
- **Fixture group** : `test` (to avoid accidental loading)
- **Consistent data** : Organizations, Applications and Users all linked and ready to use

## Common Usage

```bash
# Reset DB with fixtures
docker compose down -v
docker compose up -d
# Fixtures are automatically loaded on startup
```
