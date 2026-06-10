<?php

namespace App\EventListener;

use App\Attribute\RateLimit;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Psr\Cache\CacheItemPoolInterface;

#[AsEventListener(event: KernelEvents::REQUEST)]
class RateLimitListener
{
    private CacheItemPoolInterface $cache;
    
    // Rate limit configuration: [limit, interval_in_seconds]
    private const LIMITS = [
        'api_limiter' => [100, 3600],           // 100 requests per hour
        'login_limiter' => [5, 900],             // 5 requests per 15 minutes
        'sensitive_limiter' => [20, 3600],      // 20 requests per hour
    ];

    public function __construct(
        #[Autowire(service: 'cache.rate_limiter')]
        CacheItemPoolInterface $cache,
    ) {
        $this->cache = $cache;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        try {
            $request = $event->getRequest();
            $controller = $request->attributes->get('_controller');

            if (!$controller) {
                return;
            }

            // Handle string notation like "App\Controller\Class::method"
            $reflectionMethod = null;
            
            if (is_string($controller)) {
                if (strpos($controller, '::') !== false) {
                    [$className, $methodName] = explode('::', $controller, 2);
                    try {
                        $reflectionMethod = new \ReflectionMethod($className, $methodName);
                    } catch (\ReflectionException $e) {
                        return;
                    }
                } else {
                    return;
                }
            } elseif (is_array($controller) && count($controller) === 2) {
                [$controllerObject, $methodName] = $controller;
                try {
                    $reflectionMethod = new \ReflectionMethod($controllerObject, $methodName);
                } catch (\ReflectionException $e) {
                    return;
                }
            } else {
                return;
            }
            
            if (!$reflectionMethod) {
                return;
            }

            $rateLimitAttributes = $reflectionMethod->getAttributes(RateLimit::class);

            if (empty($rateLimitAttributes)) {
                return;
            }

            $rateLimit = $rateLimitAttributes[0]->newInstance();
            
            // Check if limiter is configured
            if (!isset(self::LIMITS[$rateLimit->limiter])) {
                return;
            }

            // Get the limit configuration
            [$limit, $interval] = self::LIMITS[$rateLimit->limiter];
            
            // Get the identifier key (IP or user ID)
            $key = $rateLimit->key ?? $this->getIdentifier($request);
            
            // Create cache key with timestamp window
            $windowStart = floor(time() / $interval) * $interval;
            $cacheKey = 'rate_limit_' . $rateLimit->limiter . '_' . $key . '_' . $windowStart;

            // Get current count from cache
            $item = $this->cache->getItem($cacheKey);
            $count = $item->isHit() ? $item->get() : 0;
            $count++;
            
            // Save updated count to cache
            $item->set($count);
            $item->expiresAfter($interval);
            $this->cache->save($item);

            // Check if rate limit exceeded
            if ($count > $limit) {
                
                // Calculate retry-after time
                $retryAfter = $windowStart + $interval - time();
                if ($retryAfter < 0) $retryAfter = $interval;
                
                $response = new Response(
                    json_encode([
                        'error' => 'Too Many Requests',
                        'message' => 'Rate limit exceeded. Please try again later.',
                        'retry_after' => (int)$retryAfter,
                    ]),
                    Response::HTTP_TOO_MANY_REQUESTS,
                    ['Content-Type' => 'application/json']
                );
                
                $response->headers->set('Retry-After', (string) $retryAfter);
                $event->setResponse($response);
            }
        } catch (\Exception $e) {
            // Fail open: don't block requests on rate limiter errors
        }
    }

    private function getIdentifier(Request $request): string
    {
        // Use connected user or IP
        $user = $request->attributes->get('user');
        if ($user instanceof \App\Entity\User) {
            return (string) $user->getId();
        }

        return $request->getClientIp() ?? 'unknown';
    }
}
