<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Monitoring Settings
    |--------------------------------------------------------------------------
    |
    | This file is for managing monitoring related settings for your application.
    | You can set up various monitoring configurations here.
    |
    */


    /*
    |--------------------------------------------------------------------------
    | Error Tracking
    |--------------------------------------------------------------------------
    |
    | Configure error tracking services like Sentry, Bugsnag, etc.
    |
    */
    'error_tracking' => [
        'enabled' => env('ERROR_TRACKING_ENABLED', true),
        'service' => env('ERROR_TRACKING_SERVICE', 'sentry'), // sentry, bugsnag, etc.
        'dsn' => env('ERROR_TRACKING_DSN'),
        'environment' => env('APP_ENV', 'production'),
        'release' => trim(exec('git log --pretty="%h" -n1 HEAD')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure performance monitoring settings.
    |
    */
    'performance' => [
        'enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),
        'sample_rate' => env('PERFORMANCE_SAMPLE_RATE', 1.0), // 0.0 to 1.0
        'traces_sample_rate' => env('TRACES_SAMPLE_RATE', 0.2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure queue monitoring settings.
    |
    */
    'queue' => [
        'monitor' => env('MONITOR_QUEUE', true),
        'failed_jobs' => [
            'enabled' => true,
            'threshold' => 5, // Number of failed jobs before notification
            'notification_email' => env('ADMIN_EMAIL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure storage monitoring settings.
    |
    */
    'storage' => [
        'disk' => env('FILESYSTEM_DISK', 'local'),
        'warning_threshold' => 80, // Percentage
        'critical_threshold' => 90, // Percentage
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduled Tasks Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure scheduled tasks monitoring.
    |
    */
    'scheduled_tasks' => [
        'enabled' => true,
        'timezone' => env('APP_TIMEZONE', 'UTC'),
        'cache_driver' => env('CACHE_DRIVER', 'file'),
        'notification_channels' => ['mail', 'slack'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Checks
    |--------------------------------------------------------------------------
    |
    | Configure health check endpoints.
    |
    */
    'health_checks' => [
        'enabled' => true,
        'route' => '/health',
        'checks' => [
            'database' => true,
            'cache' => true,
            'redis' => true,
            'meilisearch' => false,
            'horizon' => false,
            's3' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure notification settings for monitoring alerts.
    |
    */
    'notifications' => [
        'mail' => [
            'enabled' => true,
            'to' => env('ADMIN_EMAIL'),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'monitoring@example.com'),
                'name' => env('APP_NAME', 'Asset Management'),
            ],
        ],
        'slack' => [
            'enabled' => env('SLACK_ALERTS_ENABLED', false),
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'channel' => env('SLACK_CHANNEL', '#alerts'),
            'username' => env('SLACK_USERNAME', 'Asset Management Monitor'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Telescope Integration
    |--------------------------------------------------------------------------
    |
    | Configure Laravel Telescope integration.
    |
    */
    'telescope' => [
        'enabled' => env('TELESCOPE_ENABLED', true),
        'domain' => env('TELESCOPE_DOMAIN'),
        'path' => 'telescope',
        'storage' => [
            'database' => [
                'connection' => env('DB_CONNECTION', 'mysql'),
                'chunk' => 1000,
            ],
        ],
        'middleware' => [
            'web',
            'auth',
            'role:admin',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging settings for monitoring.
    |
    */
    'logging' => [
        'channels' => [
            'monitoring' => [
                'driver' => 'daily',
                'path' => storage_path('logs/monitoring.log'),
                'level' => env('LOG_LEVEL', 'debug'),
                'days' => 14,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Configure metrics collection and export.
    |
    */
    'metrics' => [
        'enabled' => env('METRICS_ENABLED', true),
        'driver' => env('METRICS_DRIVER', 'prometheus'), // prometheus, statsd, etc.
        'namespace' => env('METRICS_NAMESPACE', 'asset_management'),
        'collect_default_metrics' => true,
        'route' => [
            'enabled' => env('METRICS_ROUTE_ENABLED', true),
            'path' => '/metrics',
            'middleware' => ['auth:api'],
        ],
    ],
];
