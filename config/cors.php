<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'register'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
        'http://localhost:8000',
        'http://127.0.0.1:8000',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'Authorization',
        'Accept',
        'X-XSRF-TOKEN',
        'X-Socket-Id',
    ],
    'exposed_headers' => [
        'XSRF-TOKEN',
        'x-xsrf-token',
    ],
    'max_age' => 60 * 60 * 24, // 24 hours
    'supports_credentials' => true,
];
