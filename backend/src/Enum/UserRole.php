<?php

namespace App\Enum;

enum UserRole: string
{
    case USER = 'ROLE_USER';
    case ADMIN = 'ROLE_ADMIN';

    /**
     * Get all available roles
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn(self $role) => $role->value, self::cases());
    }

    /**
     * Check if a role value is valid
     */
    public static function isValid(string $role): bool
    {
        return in_array($role, self::values(), true);
    }

    /**
     * Get role label for display
     */
    public function getLabel(): string
    {
        return match($this) {
            self::USER => 'User',
            self::ADMIN => 'Administrator',
        };
    }
}
