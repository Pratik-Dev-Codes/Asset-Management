<?php

namespace App\Providers;

use App\Console\Commands\CleanupExpiredReports;
use App\Console\Commands\CleanupReportFiles;
use App\Console\Commands\ListScheduledReports;
use App\Console\Commands\RunScheduledReports;
use App\Console\Commands\PruneOldNotifications;
use App\Services\ReportService;
use App\Services\ReportExportService;
use App\Services\ReportSchedulerService;
use App\Services\NotificationService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schedule;
use function app;

class ReportServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register commands
        $this->commands([
            CleanupExpiredReports::class,
            CleanupReportFiles::class,
            ListScheduledReports::class,
            RunScheduledReports::class,
            PruneOldNotifications::class,
        ]);

        // Merge the reports configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/reports.php', 'reports'
        );

        // Register the report services as singletons
        $this->app->singleton(ReportService::class, function ($app) {
            return new ReportService();
        });

        $this->app->singleton(ReportExportService::class, function ($app) {
            return new ReportExportService();
        });

        $this->app->singleton(ReportSchedulerService::class, function ($app) {
            return new ReportSchedulerService(
                $app->make(ReportService::class),
                $app->make(NotificationService::class)
            );
        });

        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish the configuration file
        $this->publishes([
            __DIR__.'/../../config/reports.php' => config_path('reports.php'),
        ], 'config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../../database/migrations/2025_05_19_001328_create_reports_table.php' => 
                database_path('migrations/2025_05_19_001328_create_reports_table.php'),
            __DIR__.'/../../database/migrations/2025_05_19_001518_create_report_files_table.php' => 
                database_path('migrations/2025_05_19_001518_create_report_files_table.php'),
        ], 'migrations');

        // Register report types from config
        $this->registerReportTypes();

        // Add custom validation rules
        $this->registerValidators();

        // Schedule the cleanup command to run daily
        $this->app->booted(function () {
            // Use the scheduler instance from the container
            $scheduler = $this->app->make('Illuminate\Console\Scheduling\Schedule');
            
            $scheduler->command('reports:cleanup')
                     ->dailyAt('02:00')
                     ->timezone(Config::get('app.timezone', 'UTC'))
                     ->withoutOverlapping()
                     ->onOneServer();
        });
    }

    /**
     * Register report types from the configuration.
     */
    protected function registerReportTypes(): void
    {
        $reportTypes = Config::get('reports.types', []);
        
        foreach ($reportTypes as $type => $config) {
            // Ensure the export class exists
            if (isset($config['export']) && class_exists($config['export'])) {
                // Register the export class with the container
                $this->app->bind("report.export.{$type}", $config['export']);
            }
        }
    }

    /**
     * Register custom validation rules.
     */
    protected function registerValidators(): void
    {
        // Validate report type
        Validator::extend('report_type', function ($attribute, $value, $parameters, $validator) {
            $types = array_keys(Config::get('reports.types', []));
            return in_array($value, $types);
        }, 'The selected report type is invalid.');

        // Validate export format
        Validator::extend('export_format', function ($attribute, $value, $parameters, $validator) {
            $formats = ['xlsx', 'csv', 'pdf', 'html'];
            return in_array(strtolower($value), $formats);
        }, 'The selected export format is invalid.');
    }
}
