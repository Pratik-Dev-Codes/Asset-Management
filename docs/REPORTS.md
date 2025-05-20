# Report Generation System

This document provides an overview of the report generation system in the Asset Management application.

## Overview

The report generation system allows users to create, schedule, and export various types of reports in multiple formats (XLSX, CSV, PDF). The system is designed to be extensible, allowing for easy addition of new report types and formats.

## Features

- Generate reports in multiple formats (XLSX, CSV, PDF)
- Schedule reports to run at specific times
- Apply filters to reports
- Email notifications when reports are ready
- Cleanup of old report files
- Command-line interface for testing and automation

## Report Types

### Asset Report

Displays a list of assets with their details. Supports filtering by:
- Status (Active, In Maintenance, Retired)
- Purchase date range

## Installation

1. Publish the configuration file:
   ```bash
   php artisan vendor:publish --provider="App\Providers\ReportServiceProvider" --tag=config
   ```

2. Publish the migrations:
   ```bash
   php artisan vendor:publish --provider="App\Providers\ReportServiceProvider" --tag=migrations
   ```

3. Run the migrations:
   ```bash
   php artisan migrate
   ```

## Configuration

Edit the `config/reports.php` file to configure report settings:

```php
return [
    'storage_disk' => env('REPORTS_STORAGE_DISK', 'local'),
    'storage_path' => env('REPORTS_STORAGE_PATH', 'reports'),
    'default_format' => env('REPORTS_DEFAULT_FORMAT', 'xlsx'),
    
    'cleanup' => [
        'enabled' => env('REPORTS_CLEANUP_ENABLED', true),
        'retention_days' => env('REPORTS_RETENTION_DAYS', 7),
    ],
    
    'cleanup_schedule' => [
        'time' => env('REPORTS_CLEANUP_TIME', '00:00'),
    ],
    
    // Add your report types here
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
    ],
];
```

## Usage

### Web Interface

1. Navigate to the Reports section in the admin panel
2. Click "Create New Report"
3. Select the report type and format
4. Apply any filters if needed
5. Choose to receive email notification when the report is ready
6. Click "Generate Report"

### Command Line

Generate a test report:

```bash
php artisan reports:test
```

Cleanup old report files:

```bash
php artisan reports:cleanup --days=7
```

## Scheduling Reports

Reports can be scheduled to run at specific times using Laravel's task scheduler. The cleanup of old reports is scheduled to run daily at midnight by default.

To schedule a report, add it to the `app/Console/Kernel.php` file:

```php
protected function schedule(Schedule $schedule)
{
    // Schedule a report to run every Monday at 8:00 AM
    $schedule->command('reports:generate --type=asset --format=xlsx --filters=status:active --email=user@example.com')
             ->weekly()->mondays()->at('08:00');
}
```

## Extending the System

### Adding a New Report Type

1. Create a new export class in `app/Exports` that implements the required interfaces
2. Add the report type to the `config/reports.php` file
3. Create any necessary views for the report

### Adding a New Export Format

1. Create a new export class that implements the required interfaces
2. Update the `generate` method in `ReportService` to support the new format
3. Update the configuration as needed

## Troubleshooting

### Report Generation Fails

- Check the Laravel logs in `storage/logs/laravel.log`
- Ensure the storage directory is writable
- Verify that the queue worker is running if using queue

### Email Notifications Not Sent

- Check the mail configuration in `.env`
- Verify that the queue worker is running if using queue
- Check the Laravel logs for any errors

## API Endpoints

### List Reports

```
GET /api/reports
```

### Create Report

```
POST /api/reports
```

### Get Report

```
GET /api/reports/{id}
```

### Download Report

```
GET /api/reports/{id}/download
```

## Permissions

The following permissions are used by the report system:

- `view reports` - View the reports list
- `create reports` - Create new reports
- `view report` - View a specific report
- `delete report` - Delete a report
- `generate reports` - Generate reports
- `schedule reports` - Schedule reports
- `view report files` - View report files
- `delete report files` - Delete report files
- `cleanup report files` - Cleanup old report files
