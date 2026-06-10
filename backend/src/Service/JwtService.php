<?php

namespace App\Service;

class JwtService
{
    private string $secretKey;
    private int $accessTokenExpiry = 3600; // 1 hour
    private int $refreshTokenExpiry = 2592000; // 30 days

    public function __construct(string $jwtSecret)
    {
        $this->secretKey = $jwtSecret;
    }

    public function generateAccessToken(int $userId, string $email): string
    {
        $payload = [
            'user_id' => $userId,
            'email' => $email,
            'exp' => time() + $this->accessTokenExpiry,
            'iat' => time(),
            'type' => 'access'
        ];

        return $this->encode($payload);
    }

    public function generateRefreshToken(int $userId, string $email, bool $rememberMe = false): string
    {
        $expiry = $rememberMe ? $this->refreshTokenExpiry : 86400; // 1 day if not remember me
        
        $payload = [
            'user_id' => $userId,
            'email' => $email,
            'exp' => time() + $expiry,
            'iat' => time(),
            'type' => 'refresh'
        ];

        return $this->encode($payload);
    }

    public function validateToken(string $token): ?array
    {
        try {
            $payload = $this->decode($token);
            
            if (!isset($payload['exp']) || $payload['exp'] < time()) {
                return null; // Token expired
            }

            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function encode(array $payload): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode($payload);

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secretKey, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    private function decode(string $token): array
    {
        $tokenParts = explode('.', $token);

        if (count($tokenParts) !== 3) {
            throw new \InvalidArgumentException('Invalid token format');
        }

        [$base64UrlHeader, $base64UrlPayload, $base64UrlSignature] = $tokenParts;

        // Verify signature
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secretKey, true);
        $base64UrlSignatureCheck = $this->base64UrlEncode($signature);

        if ($base64UrlSignature !== $base64UrlSignatureCheck) {
            throw new \InvalidArgumentException('Invalid token signature');
        }

        $payload = json_decode($this->base64UrlDecode($base64UrlPayload), true);

        return $payload;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public function getAccessTokenExpiry(): int
    {
        return $this->accessTokenExpiry;
    }

    public function getRefreshTokenExpiry(bool $rememberMe = false): int
    {
        return $rememberMe ? $this->refreshTokenExpiry : 86400;
    }
}
