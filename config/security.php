<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Configure security-related HTTP headers. These headers help protect
    | your application from various web vulnerabilities.
    |
    */
    'headers' => [
        'hsts' => [
            'enabled' => env('SECURITY_HEADERS_HSTS_ENABLED', true),
            'max_age' => env('SECURITY_HEADERS_HSTS_MAX_AGE', 31536000), // 1 year
            'include_subdomains' => env('SECURITY_HEADERS_HSTS_INCLUDE_SUBDOMAINS', true),
            'preload' => env('SECURITY_HEADERS_HSTS_PRELOAD', true),
        ],
        'csp' => [
            'enabled' => env('SECURITY_HEADERS_CSP_ENABLED', true),
            'report_only' => env('SECURITY_HEADERS_CSP_REPORT_ONLY', false),
            'report_uri' => env('SECURITY_HEADERS_CSP_REPORT_URI', null),
        ],
        'feature_policy' => [
            'enabled' => env('SECURITY_HEADERS_FEATURE_POLICY_ENABLED', true),
        ],
        'permissions_policy' => [
            'enabled' => env('SECURITY_HEADERS_PERMISSIONS_POLICY_ENABLED', true),
        ],
        'xss_protection' => [
            'enabled' => env('SECURITY_HEADERS_XSS_PROTECTION_ENABLED', true),
            'mode' => 'block', // or '1; mode=block' for older browsers
        ],
        'content_type_options' => [
            'enabled' => env('SECURITY_HEADERS_CONTENT_TYPE_OPTIONS_ENABLED', true),
        ],
        'x_frame_options' => [
            'enabled' => env('SECURITY_HEADERS_X_FRAME_OPTIONS_ENABLED', true),
            'value' => 'SAMEORIGIN', // or 'DENY' or 'ALLOW-FROM uri'
        ],
        'referrer_policy' => [
            'enabled' => env('SECURITY_HEADERS_REFERRER_POLICY_ENABLED', true),
            'value' => 'strict-origin-when-cross-origin',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for various application routes.
    |
    */
    'rate_limiting' => [
        'enabled' => env('RATE_LIMITING_ENABLED', true),
        'max_attempts' => env('RATE_LIMITING_MAX_ATTEMPTS', 5),
        'decay_minutes' => env('RATE_LIMITING_DECAY_MINUTES', 1),
        'throttle' => [
            'enabled' => env('RATE_LIMITING_THROTTLE_ENABLED', true),
            'max_attempts' => env('RATE_LIMITING_THROTTLE_MAX_ATTEMPTS', 60),
            'decay_minutes' => env('RATE_LIMITING_THROTTLE_DECAY_MINUTES', 1),
        ],
        'login' => [
            'max_attempts' => env('LOGIN_MAX_ATTEMPTS', 5),
            'decay_minutes' => env('LOGIN_DECAY_MINUTES', 15),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Requirements
    |--------------------------------------------------------------------------
    |
    | Configure password requirements for user accounts.
    |
    */
    'password' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 12),
        'require_mixed_case' => env('PASSWORD_REQUIRE_MIXED_CASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', true),
        'uncompromised' => env('PASSWORD_UNCOMPROMISED', true),
        'max_attempts' => env('PASSWORD_MAX_ATTEMPTS', 3),
        'lockout_minutes' => env('PASSWORD_LOCKOUT_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Configure session security settings.
    |
    */
    'session' => [
        'lifetime' => env('SESSION_LIFETIME', 120),
        'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),
        'encrypt' => env('SESSION_ENCRYPT', true),
        'same_site' => env('SESSION_SAME_SITE', 'lax'), // 'lax', 'strict', 'none', or null
        'http_only' => env('SESSION_HTTP_ONLY', true),
        'secure' => env('SESSION_SECURE_COOKIE', true),
        'domain' => env('SESSION_DOMAIN', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (CSP)
    |--------------------------------------------------------------------------
    |
    | Configure Content Security Policy rules.
    |
    */
    'csp' => [
        'default-src' => [
            'self',
        ],
        'script-src' => [
            'self',
            'unsafe-inline',
            'unsafe-eval',
            'https:',
        ],
        'style-src' => [
            'self',
            'unsafe-inline',
            'https:',
        ],
        'img-src' => [
            'self',
            'data:',
            'https:',
        ],
        'font-src' => [
            'self',
            'data:',
            'https:',
        ],
        'connect-src' => [
            'self',
            'https:',
        ],
        'media-src' => [
            'self',
            'data:',
            'https:',
        ],
        'object-src' => [
            'none',
        ],
        'frame-src' => [
            'self',
        ],
        'frame-ancestors' => [
            'self',
        ],
        'form-action' => [
            'self',
        ],
        'base-uri' => [
            'self',
        ],
        'upgrade-insecure-requests' => true,
    ],
];
