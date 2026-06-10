<?php

namespace App\Tests\DTO;

use App\DTO\LoginRequest;
use PHPUnit\Framework\TestCase;

class LoginRequestTest extends TestCase
{
    public function testValidLoginRequest(): void
    {
        $loginRequest = new LoginRequest();
        $loginRequest->username = 'test@example.com';
        $loginRequest->password = 'password123';
        $loginRequest->rememberMe = false;

        $this->assertEquals('test@example.com', $loginRequest->username);
        $this->assertEquals('password123', $loginRequest->password);
        $this->assertFalse($loginRequest->rememberMe);
    }

    public function testLoginRequestRememberMeDefault(): void
    {
        $loginRequest = new LoginRequest();
        $loginRequest->username = 'test@example.com';
        $loginRequest->password = 'password123';

        $this->assertFalse($loginRequest->rememberMe);
    }

    public function testLoginRequestSetters(): void
    {
        $loginRequest = new LoginRequest();
        $loginRequest->username = 'user@example.com';
        $loginRequest->password = 'securepass';
        $loginRequest->rememberMe = true;

        $this->assertEquals('user@example.com', $loginRequest->username);
        $this->assertEquals('securepass', $loginRequest->password);
        $this->assertTrue($loginRequest->rememberMe);
    }
}
