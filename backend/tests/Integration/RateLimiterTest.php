<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Test that rate limiters are properly configured
 */
class RateLimiterTest extends KernelTestCase
{
    public function testRateLimitersAreConfigured(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        // Verify that cache.rate_limiter pool is available (used by the rate limiter listener)
        $cache = $container->get('cache.rate_limiter');
        $this->assertNotNull($cache);
        
        // Verify that the RateLimitListener is registered
        $listener = $container->get('App\EventListener\RateLimitListener');
        $this->assertNotNull($listener);
    }
}
