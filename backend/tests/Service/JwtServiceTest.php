<?php

namespace App\Tests\Service;

use App\Service\JwtService;
use PHPUnit\Framework\TestCase;

class JwtServiceTest extends TestCase
{
    private JwtService $jwtService;
    private string $secretKey = 'test_secret_key_123';

    protected function setUp(): void
    {
        $this->jwtService = new JwtService($this->secretKey);
    }

    public function testGenerateAccessToken(): void
    {
        $userId = 1;
        $email = 'test@example.com';

        $token = $this->jwtService->generateAccessToken($userId, $email);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertCount(3, explode('.', $token));
    }

    public function testGenerateRefreshToken(): void
    {
        $userId = 1;
        $email = 'test@example.com';

        $token = $this->jwtService->generateRefreshToken($userId, $email);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertCount(3, explode('.', $token));
    }

    public function testGenerateRefreshTokenWithRememberMe(): void
    {
        $userId = 1;
        $email = 'test@example.com';

        $tokenWithoutRememberMe = $this->jwtService->generateRefreshToken($userId, $email, false);
        $tokenWithRememberMe = $this->jwtService->generateRefreshToken($userId, $email, true);

        $this->assertIsString($tokenWithoutRememberMe);
        $this->assertIsString($tokenWithRememberMe);
        $this->assertNotEquals($tokenWithoutRememberMe, $tokenWithRememberMe);
    }

    public function testValidateTokenReturnsPayloadForValidToken(): void
    {
        $userId = 1;
        $email = 'test@example.com';

        $token = $this->jwtService->generateAccessToken($userId, $email);
        $payload = $this->jwtService->validateToken($token);

        $this->assertIsArray($payload);
        $this->assertEquals($userId, $payload['user_id']);
        $this->assertEquals($email, $payload['email']);
        $this->assertEquals('access', $payload['type']);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertArrayHasKey('iat', $payload);
    }

    public function testValidateTokenReturnsNullForInvalidToken(): void
    {
        $invalidToken = 'invalid.token.here';

        $payload = $this->jwtService->validateToken($invalidToken);

        $this->assertNull($payload);
    }

    public function testValidateTokenReturnsNullForExpiredToken(): void
    {
        // Create a JWT service with a known secret for testing
        $secretKey = 'test_secret_key_123';
        
        // Create an expired token manually
        $payload = [
            'user_id' => 1,
            'email' => 'test@example.com',
            'exp' => time() - 3600, // Expired 1 hour ago
            'iat' => time() - 7200,
            'type' => 'access'
        ];

        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payloadJson = json_encode($payload);

        $base64UrlHeader = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $base64UrlPayload = rtrim(strtr(base64_encode($payloadJson), '+/', '-_'), '=');

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secretKey, true);
        $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $expiredToken = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        $result = $this->jwtService->validateToken($expiredToken);

        $this->assertNull($result);
    }

    public function testValidateTokenReturnsNullForTamperedToken(): void
    {
        $token = $this->jwtService->generateAccessToken(1, 'test@example.com');
        
        // Tamper with the token
        $parts = explode('.', $token);
        $parts[2] = 'tampered_signature';
        $tamperedToken = implode('.', $parts);

        $payload = $this->jwtService->validateToken($tamperedToken);

        $this->assertNull($payload);
    }

    public function testGetAccessTokenExpiry(): void
    {
        $expiry = $this->jwtService->getAccessTokenExpiry();

        $this->assertEquals(3600, $expiry);
    }

    public function testGetRefreshTokenExpiry(): void
    {
        $expiryWithoutRememberMe = $this->jwtService->getRefreshTokenExpiry(false);
        $expiryWithRememberMe = $this->jwtService->getRefreshTokenExpiry(true);

        $this->assertEquals(86400, $expiryWithoutRememberMe);
        $this->assertEquals(2592000, $expiryWithRememberMe);
    }

    public function testAccessTokenContainsCorrectPayload(): void
    {
        $userId = 42;
        $email = 'user@example.com';

        $token = $this->jwtService->generateAccessToken($userId, $email);
        $payload = $this->jwtService->validateToken($token);

        $this->assertEquals($userId, $payload['user_id']);
        $this->assertEquals($email, $payload['email']);
        $this->assertEquals('access', $payload['type']);
        $this->assertGreaterThan(time(), $payload['exp']);
        $this->assertLessThanOrEqual(time(), $payload['iat']);
    }

    public function testRefreshTokenContainsCorrectPayload(): void
    {
        $userId = 42;
        $email = 'user@example.com';

        $token = $this->jwtService->generateRefreshToken($userId, $email);
        $payload = $this->jwtService->validateToken($token);

        $this->assertEquals($userId, $payload['user_id']);
        $this->assertEquals($email, $payload['email']);
        $this->assertEquals('refresh', $payload['type']);
        $this->assertGreaterThan(time(), $payload['exp']);
        $this->assertLessThanOrEqual(time(), $payload['iat']);
    }
}
