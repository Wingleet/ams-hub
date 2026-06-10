# Types Organization Guide

## Overview

This folder contains all TypeScript types and interfaces used across the frontend application, following the DRY (Don't Repeat Yourself) principle.

## Structure

```
types/
├── index.ts                    # Central export file
├── api.types.ts               # API request/response types
├── user.types.ts              # User and authentication types
├── application.types.ts       # Application types
└── organization.types.ts      # Organization and member types
```

## Usage

Import types from the centralized index:

```typescript
import type { User, Application, ApiResponse } from "@/types";
```

Or import from specific files:

```typescript
import type { User } from "@/types/user.types";
import type { Application } from "@/types/application.types";
```

## Type Definitions

### API Types (`api.types.ts`)

- `ApiResponse<T>` - Generic API response wrapper
- `ApiErrorResponse` - API error response

### User Types (`user.types.ts`)

- `User` - User entity with authentication information
- `Organization` - User's organization
- `LoginFormData` - Login form input
- `RegisterFormData` - Registration form input
- `LoginData` - API login request data
- `FormErrors` - Form validation errors

### Application Types (`application.types.ts`)

- `Application` - Application/service entity

### Organization Types (`organization.types.ts`)

- `OrganizationMember` - Organization member details
- `CreateOrganizationUserData` - Data for creating new organization member

## Best Practices

1. **Centralize all types** - Add new types to appropriate files in this folder
2. **Use the main export** - Import from `@/types` whenever possible
3. **Group by domain** - Keep related types together in domain-specific files
4. **Document interfaces** - Add JSDoc comments to complex types
5. **Reuse generic types** - Use `ApiResponse<T>` instead of creating custom response types

## Adding New Types

1. Create a new file if adding types for a new domain (e.g., `roles.types.ts`)
2. Export the types from `index.ts`
3. Update related services and components to use the centralized types
4. Remove duplicate type definitions from service files

## Related Files

- `/src/services/` - Services that use these types
- `/src/store/` - Zustand stores using auth types
- `/src/pages/` - Pages that import and use these types
- `/src/hooks/` - Hooks that work with typed data
