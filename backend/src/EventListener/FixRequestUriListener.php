<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 512)]
class FixRequestUriListener
{
    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Fix malformed REQUEST_URI from Caddy
        $server = $request->server;
        $requestUri = $server->get('REQUEST_URI');
        
        if ($requestUri && !str_starts_with($requestUri, 'http')) {
            // REQUEST_URI is already a path, ensure it's properly formatted
            if (!str_starts_with($requestUri, '/')) {
                $server->set('REQUEST_URI', '/' . $requestUri);
            }
        } elseif ($requestUri && preg_match('#^https?:///#', $requestUri)) {
            // Fix malformed absolute URI with missing host (http:///path)
            $server->set('REQUEST_URI', parse_url($requestUri, PHP_URL_PATH) ?? '/');
        }
        
        // Ensure HTTP_HOST is set
        if (!$server->has('HTTP_HOST')) {
            $server->set('HTTP_HOST', 'localhost');
        }
    }
}
