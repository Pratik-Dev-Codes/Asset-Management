<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Query Optimization Settings
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for query optimization features
    | such as slow query logging and query caching.
    |
    */


    /*
    |--------------------------------------------------------------------------
    | Slow Query Threshold
    |--------------------------------------------------------------------------
    |
    | This value determines the threshold in milliseconds after which a query
    | is considered slow and will be logged. Set to 0 to disable slow query logging.
    |
    */
    'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 100),

    /*
    |--------------------------------------------------------------------------
    | Query Cache TTL
    |--------------------------------------------------------------------------
    |
    | This value determines the default time to live for query cache in minutes.
    | Set to 0 to disable query caching.
    |
    */
    'cache_ttl' => env('QUERY_CACHE_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | Enable Query Logging
    |--------------------------------------------------------------------------
    |
    | This option enables logging of all database queries.
    |
    */
    'enable_query_logging' => env('ENABLE_QUERY_LOGGING', false),

    /*
    |--------------------------------------------------------------------------
    | Log Slow Queries
    |--------------------------------------------------------------------------
    |
    | Enable logging of slow queries. Requires enable_query_logging to be true.
    |
    */
    'log_slow_queries' => env('LOG_SLOW_QUERIES', true),

    /*
    |--------------------------------------------------------------------------
    | Log Channel
    |--------------------------------------------------------------------------
    |
    | The log channel to use for query logging. Set to null to use the default channel.
    |
    */
    'log_channel' => env('QUERY_LOG_CHANNEL', 'stack'),
];
