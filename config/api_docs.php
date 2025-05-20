<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Documentation Settings
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the API documentation.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | API Versions
    |--------------------------------------------------------------------------
    |
    | Here you can define the API versions that your application supports.
    | The key is the version number and the value is the path to the
    | documentation file.
    |
    */
    'versions' => [
        '1.0' => 'v1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default API Version
    |--------------------------------------------------------------------------
    |
    | This is the default version of the API that will be used when no version
    | is specified in the request. This should match one of the versions
    | defined in the 'versions' array above.
    |
    */
    'default_version' => '1.0',

    /*
    |--------------------------------------------------------------------------
    | API Title
    |--------------------------------------------------------------------------
    |
    | The title of your API documentation.
    |
    */
    'title' => env('APP_NAME', 'Asset Management System').' API Documentation',

    /*
    |--------------------------------------------------------------------------
    | API Description
    |--------------------------------------------------------------------------
    |
    | A short description of your API.
    |
    */
    'description' => 'API documentation for the Asset Management System',

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for your API. This will be used to generate example requests.
    |
    */
    'base_url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | API Terms of Service
    |--------------------------------------------------------------------------
    |
    | The terms of service for your API.
    |
    */
    'terms_of_service' => 'https://example.com/terms',

    /*
    |--------------------------------------------------------------------------
    | API Contact Information
    |--------------------------------------------------------------------------
    |
    | The contact information for the API.
    |
    */
    'contact' => [
        'name' => 'API Support',
        'email' => 'support@example.com',
        'url' => 'https://example.com/contact',
    ],

    /*
    |--------------------------------------------------------------------------
    | API License
    |--------------------------------------------------------------------------
    |
    | The license for the API.
    |
    */
    'license' => [
        'name' => 'MIT',
        'url' => 'https://opensource.org/licenses/MIT',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Security Definitions
    |--------------------------------------------------------------------------
    |
    | The security definitions for the API.
    |
    */
    'security' => [
        'bearerAuth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
            'description' => 'Enter your bearer token in the format **{token}**',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Servers
    |--------------------------------------------------------------------------
    |
    | The servers that host the API.
    |
    */
    'servers' => [
        [
            'url' => env('APP_URL', 'http://localhost').'/api',
            'description' => 'Development server',
        ],
        [
            'url' => 'https://api.example.com',
            'description' => 'Production server',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Documentation Path
    |--------------------------------------------------------------------------
    |
    | The path where the API documentation will be served from.
    |
    */
    'documentation_path' => 'api/documentation',

    /*
    |--------------------------------------------------------------------------
    | API Documentation UI Path
    |--------------------------------------------------------------------------
    |
    | The path where the API documentation UI will be served from.
    |
    */
    'ui_path' => 'api/docs',

    /*
    |--------------------------------------------------------------------------
    | API Documentation Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware that will be applied to the API documentation routes.
    | You may want to restrict access to the documentation in production.
    |
    */
    'middleware' => [
        'web',
        // 'auth:api', // Uncomment to require authentication
    ],
];
