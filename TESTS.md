# Tests - AMS APPS HUB

## Backend (PHPUnit)

**Framework** : PHPUnit  
**Coverage** : Target 80%+  
**Location** : `backend/tests/`

### Run the tests

```bash
# Local
php bin/phpunit

# With HTML coverage
php bin/phpunit --coverage-html=coverage/

# Docker
docker compose exec backend php bin/phpunit
```

### Test structure

```
backend/tests/
├── Entity/          - Entity tests (User, Organization, Application, Subscription)
├── Service/         - Business logic service tests (AuthService, JwtService, StatsService)
├── Security/        - JWT authentication tests (JwtAuthenticator)
├── DTO/             - Transfer object tests (LoginRequest, RegisterRequest)
├── Enum/            - Enumeration tests (UserRole)
├── Integration/     - Integration tests
├── Repository/      - Repository tests
└── DatabaseRefreshTrait.php  - Trait for resetting the DB between tests
```

### Existing tests

- **Unit tests** : Entity validation, services, security
- **Integration tests** : API Platform endpoints, authentication
- **Coverage** : HTML code coverage generated in `coverage/`

### Configuration

```bash
# Configuration file
backend/phpunit.xml          # Main configuration
backend/phpunit.dist.xml     # Distribution (backup)
backend/services_test.yaml   # Services specific to tests
```

### Execute specific tests

```bash
# A test file
php bin/phpunit backend/tests/Entity/UserTest.php

# A test class
php bin/phpunit --filter UserTest

# A specific method
php bin/phpunit --filter testUserCanBeCreated

# With coverage
php bin/phpunit --coverage-html=coverage/ tests/Entity/
```

## Frontend (Vitest)

**Framework** : Vitest + React Testing Library  
**Location** : `frontend/src/test/`

### Run the tests

```bash
cd frontend

npm run test              # Watch mode (auto-reload)
npm run test:run         # Single run
npm run test:coverage    # With code coverage
npm run test:ui          # Vitest UI (visualize tests)
```

### Test structure

```
frontend/src/test/
├── services/
│   ├── authService.test.ts       - Authentication service tests
│   ├── apiClient.test.ts         - API client tests
│   ├── apiService.test.ts        - Generic API call tests
│   ├── applicationService.test.ts - Application service tests
│   └── organizationService.test.ts - Organization service tests
└── [other tests to come]
```

### Docker

```bash
docker compose exec frontend npm run test:run
docker compose exec frontend npm run test:coverage
docker compose exec frontend npm run test:ui
```

### Configuration

```bash
# Configuration files
frontend/vitest.config.ts  # Vitest configuration
frontend/tsconfig.json     # TypeScript configuration
```

### Execute specific tests

```bash
# A test file
npm run test:run -- src/test/services/authService.test.ts

# A specific pattern
npm run test:run -- --grep "login"

# Mode watch avec pattern
npm run test -- authService
```

## Coverage & Quality

### Backend

```bash
# Generate HTML report
php bin/phpunit --coverage-html=coverage/

# Display in terminal
php bin/phpunit --coverage-text

# Clover format for CI/CD
php bin/phpunit --coverage-clover=coverage.xml
```

### Frontend

```bash
# Generate report
npm run test:coverage

# Report available in
# frontend/coverage/
```

## Best practices

### Backend (PHPUnit)

1. **Unit tests** : Test a single responsibility
2. **DatabaseRefreshTrait** : Reset DB for each test
3. **Fixtures** : Use DataFixtures for test data
4. **Isolation** : Each test must be independent

### Frontend (Vitest)

1. **Mocking** : Mock API calls with `vi.mock()`
2. **Types** : Use TypeScript types in tests
3. **Isolation** : Tests isolated from actual network calls
4. **Assertions** : Check behavior, not implementation

## Continuous Integration

Tests must pass before each commit/PR:

```bash
# Frontend
npm run test:run
npm run lint
npm run type-check

# Backend
php bin/phpunit
php bin/console lint:yaml config/
php bin/console lint:twig templates/
```
