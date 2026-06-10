<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    // Trust all proxies for Docker environment
    $_SERVER['TRUSTED_PROXIES'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $_SERVER['TRUSTED_HEADERS'] = \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR | 
                                   \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST | 
                                   \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT | 
                                   \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO;
    
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
