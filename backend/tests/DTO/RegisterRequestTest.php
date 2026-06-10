<?php

namespace App\Tests\DTO;

use App\DTO\RegisterRequest;
use PHPUnit\Framework\TestCase;

class RegisterRequestTest extends TestCase
{
    public function testValidRegisterRequest(): void
    {
        $registerRequest = new RegisterRequest();
        $registerRequest->email = 'test@example.com';
        $registerRequest->password = 'password123';
        $registerRequest->firstName = 'John';
        $registerRequest->lastName = 'Doe';

        $this->assertEquals('test@example.com', $registerRequest->email);
        $this->assertEquals('password123', $registerRequest->password);
        $this->assertEquals('John', $registerRequest->firstName);
        $this->assertEquals('Doe', $registerRequest->lastName);
    }

    public function testRegisterRequestSetters(): void
    {
        $registerRequest = new RegisterRequest();
        $registerRequest->email = 'user@example.com';
        $registerRequest->password = 'securepass';
        $registerRequest->firstName = 'Jane';
        $registerRequest->lastName = 'Smith';

        $this->assertEquals('user@example.com', $registerRequest->email);
        $this->assertEquals('securepass', $registerRequest->password);
        $this->assertEquals('Jane', $registerRequest->firstName);
        $this->assertEquals('Smith', $registerRequest->lastName);
    }
}
