<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Memory Monitoring
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for memory monitoring and management.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Memory Monitoring Settings
    |--------------------------------------------------------------------------
    |
    | Here you may configure the memory monitoring settings for your application.
    |
    */
    'monitor' => [
        'enabled' => env('MEMORY_MONITOR_ENABLED', true),
        'log_all' => env('MEMORY_MONITOR_LOG_ALL', false),
        'threshold' => env('MEMORY_MONITOR_THRESHOLD', 80), // percentage
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory Limits
    |--------------------------------------------------------------------------
    |
    | Configure memory limits for different parts of your application.
    |
    */
    'limits' => [
        'web' => env('MEMORY_LIMIT_WEB', '128M'),
        'cli' => env('MEMORY_LIMIT_CLI', '256M'),
        'queue' => env('MEMORY_LIMIT_QUEUE', '256M'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory Cleanup
    |--------------------------------------------------------------------------
    |
    | Configure automatic memory cleanup settings.
    |
    */
    'cleanup' => [
        'enabled' => env('MEMORY_CLEANUP_ENABLED', true),
        'threshold' => env('MEMORY_CLEANUP_THRESHOLD', 70), // percentage
        'interval' => env('MEMORY_CLEANUP_INTERVAL', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Cleanup
    |--------------------------------------------------------------------------
    |
    | Configure log file cleanup settings.
    |
    */
    'log_cleanup' => [
        'enabled' => env('LOG_CLEANUP_ENABLED', true),
        'days_to_keep' => env('LOG_CLEANUP_DAYS', 30),
        'time' => env('LOG_CLEANUP_TIME', '02:00'),
        'exclude' => [
            'laravel.log',
            '.gitignore',
        ],
    ],
];
