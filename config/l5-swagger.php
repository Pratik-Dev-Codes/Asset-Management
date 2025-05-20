<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'Asset Management API',
                'description' => 'API for managing assets and locations',
                'version' => '1.0.0',
                'contact' => [
                    'email' => 'support@example.com',
                ],
                'license' => [
                    'name' => 'MIT',
                    'url' => 'https://opensource.org/licenses/MIT',
                ],
                'security' => [
                    [
                        'bearerAuth' => [],
                    ],
                ],
                'securityDefinitions' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                    ],
                ],
                'produces' => [
                    'application/json',
                ],
                'consumes' => [
                    'application/json',
                ],
            ],
            'routes' => [
                'api' => 'api/documentation',
                'docs' => 'docs',
                'oauth2_callback' => 'api/oauth2-callback',
                'middleware' => [
                    'api',
                    'auth:api',
                ],
            ],
            'paths' => [
                'docs' => storage_path('api-docs'),
                'docs_json' => 'api-docs.json',
                'docs_yaml' => 'api-docs.yaml',
                'annotations' => [
                    base_path('app/Http/Controllers/Api'),
                    base_path('app/Http/Resources'),
                ],
                'base' => base_path(),
                'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),
                'swagger_ui_custom_assets' => [],
                'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', true),
            ],
        ],
    ],
    'defaults' => [
        'routes' => [
            'api' => 'api/documentation',
            'docs' => 'docs',
            'oauth2_callback' => 'api/oauth2-callback',
            'middleware' => [
                'api' => ['api'],
                'docs' => ['web'],
                'oauth2_callback' => ['web'],
            ],
        ],
        'paths' => [
            'docs' => storage_path('api-docs'),
            'docs_json' => 'api-docs.json',
            'docs_yaml' => 'api-docs.yaml',
            'annotations' => [
                base_path('app/Http/Controllers'),
                base_path('app/Http/Resources'),
            ],
            'base' => base_path(),
            'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),
            'swagger_ui_custom_assets' => [],
            'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', true),
        ],
        'swagger' => [
            'swagger' => '2.0',
            'info' => [
                'title' => 'Asset Management API',
                'description' => 'API for managing assets and locations',
                'version' => '1.0.0',
                'contact' => [
                    'email' => 'support@example.com',
                ],
                'license' => [
                    'name' => 'MIT',
                    'url' => 'https://opensource.org/licenses/MIT',
                ],
            ],
            'host' => env('L5_SWAGGER_CONST_HOST', 'localhost:8000'),
            'basePath' => '/',
            'schemes' => [
                'http',
                'https',
            ],
            'consumes' => [
                'application/json',
            ],
            'produces' => [
                'application/json',
            ],
            'securityDefinitions' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                ],
            ],
            'security' => [
                [
                    'bearerAuth' => [],
                ],
            ],
        ],
    ],
];
