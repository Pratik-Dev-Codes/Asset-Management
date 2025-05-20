<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Report Settings
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the report generation
    | and management system.
    |
    */
    
    /*
    |--------------------------------------------------------------------------
    | Report Queue
    |--------------------------------------------------------------------------
    |
    | This option specifies the name of the queue that report generation jobs
    | will be dispatched to. This allows you to prioritize report generation
    | separately from other jobs in your application.
    |
    */
    'queue' => env('REPORTS_QUEUE', 'reports'),
    
    /*
    |--------------------------------------------------------------------------
    | Report Timeout
    |--------------------------------------------------------------------------
    |
    | This option specifies the maximum number of seconds a report generation
    | job is allowed to run before timing out.
    |
    */
    'timeout' => env('REPORTS_TIMEOUT', 3600), // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    |
    | This option controls the default storage disk that will be used to store
    | generated report files. This can be overridden on a per-report basis.
    |
    */
    'storage_disk' => env('REPORTS_STORAGE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Storage Path
    |--------------------------------------------------------------------------
    |
    | This is the base path where report files will be stored on the disk.
    |
    */
    'storage_path' => env('REPORTS_STORAGE_PATH', 'reports'),

    /*
    |--------------------------------------------------------------------------
    | Default File Format
    |--------------------------------------------------------------------------
    |
    | This is the default file format for generated reports.
    | Supported formats: 'xlsx', 'csv', 'pdf', 'html'
    |
    */
    'default_format' => env('REPORTS_DEFAULT_FORMAT', 'xlsx'),

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the automatic cleanup of old report files and records.
    | You can configure the retention period and cleanup schedule.
    |
    */
    'cleanup' => [
        'enabled' => env('REPORTS_CLEANUP_ENABLED', true),
        'retention_days' => env('REPORTS_RETENTION_DAYS', 30),
        'schedule' => [
            'time' => env('REPORTS_CLEANUP_TIME', '00:00'),
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Scheduling Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the scheduling of report generation.
    |
    */
    'scheduling' => [
        'enabled' => env('REPORTS_SCHEDULING_ENABLED', true),
        'frequency' => env('REPORTS_SCHEDULING_FREQUENCY', '5'), // in minutes
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the notifications sent to users when their reports
    | are ready or if there was an error during generation.
    |
    */
    'notifications' => [
        'enabled' => env('REPORTS_NOTIFICATIONS_ENABLED', true),
        'email' => [
            'enabled' => env('REPORTS_EMAIL_NOTIFICATIONS', true),
            'from_address' => env('REPORTS_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')),
            'from_name' => env('REPORTS_FROM_NAME', env('MAIL_FROM_NAME', 'Report System')),
        ],
        'database' => [
            'enabled' => env('REPORTS_DATABASE_NOTIFICATIONS', true),
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Configure the queue settings for report generation.
    |
    */
    'queue_settings' => [
        'connection' => env('REPORTS_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
        'queue' => env('REPORTS_QUEUE', 'reports'),
        'tries' => env('REPORTS_QUEUE_TRIES', 3),
        'timeout' => env('REPORTS_QUEUE_TIMEOUT', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Report Types
    |--------------------------------------------------------------------------
    |
    | Define the available report types and their configurations.
    |
    */
    'types' => [
        'asset' => [
            'label' => 'Asset Report',
            'model' => \App\Models\Asset::class,
            'export' => \App\Exports\AssetReportExport::class,
            'filters' => [
                'status' => [
                    'type' => 'select',
                    'options' => [
                        'active' => 'Active',
                        'in_maintenance' => 'In Maintenance',
                        'retired' => 'Retired',
                    ],
                    'label' => 'Status',
                ],
                'purchase_date' => [
                    'type' => 'date-range',
                    'label' => 'Purchase Date',
                ],
            ],
        ],
        // Add more report types as needed
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    |
    | Configure export-specific settings.
    |
    */
    'exports' => [
        'pdf' => [
            'orientation' => 'landscape', // 'portrait' or 'landscape'
            'paper' => 'a4', // a4, letter, etc.
            'font' => 'dejavu sans',
            'font_size' => 10,
            'margin_top' => 25,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
            'header' => [
                'enabled' => true,
                'html' => null, // Custom header HTML
                'spacing' => 10, // mm
            ],
            'footer' => [
                'enabled' => true,
                'html' => 'Page {PAGENO} of {nb}', // Default footer with page numbers
                'spacing' => 10, // mm
            ],
        ],
        'excel' => [
            'creator' => 'Asset Management System',
            'company' => 'Your Company',
            'title' => 'Report',
            'subject' => 'Generated Report',
            'description' => 'This report was generated by the Asset Management System',
            'keywords' => 'report,export,data',
            'category' => 'Report',
        ],
        'csv' => [
            'delimiter' => ',',
            'enclosure' => '"',
            'line_ending' => "\n",
            'use_bom' => true,
            'include_separator_line' => true,
            'excel_compatibility' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | These settings control security-related aspects of report generation.
    |
    */
    'security' => [
        'require_authentication' => env('REPORTS_REQUIRE_AUTH', true),
        'allowed_ips' => array_filter(explode(',', env('REPORTS_ALLOWED_IPS', ''))),
        'max_file_size' => env('REPORTS_MAX_FILE_SIZE', 10485760), // 10MB
        'allowed_mime_types' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'text/csv',
            'application/pdf',
            'text/html',
        ],
    ],
];
