<?php

namespace App\Trait;

use Symfony\Component\HttpFoundation\Cookie;

trait SecureCookieTrait
{
    private function createSecureCookie(string $name, string $value, int $maxAge): Cookie
    {
        $isProduction = $_ENV['APP_ENV'] === 'prod';
        
        return Cookie::create($name)
            ->withValue($value)
            ->withExpires(time() + $maxAge)
            ->withPath('/')
            ->withSecure($isProduction) // Only HTTPS in production
            ->withHttpOnly(true) // Not accessible via JavaScript
            ->withSameSite($isProduction ? 'strict' : 'lax'); // Lax for development
    }
}
