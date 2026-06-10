<?php

namespace App\Tests\Enum;

use App\Enum\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function testUserRoleCasesExist(): void
    {
        $this->assertNotEmpty(UserRole::cases());
        $this->assertCount(2, UserRole::cases());
    }

    public function testUserRoleValues(): void
    {
        $this->assertEquals('ROLE_USER', UserRole::USER->value);
        $this->assertEquals('ROLE_ADMIN', UserRole::ADMIN->value);
    }

    public function testGetAllValues(): void
    {
        $values = UserRole::values();
        
        $this->assertContains('ROLE_USER', $values);
        $this->assertContains('ROLE_ADMIN', $values);
    }
}
