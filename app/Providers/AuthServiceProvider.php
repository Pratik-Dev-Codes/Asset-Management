<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Maintenance;
use App\Models\Report;
use App\Models\User;
use App\Policies\AssetPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\MaintenancePolicy;
use App\Policies\ReportPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Memory Monitor
        'memory-monitor' => \App\Policies\MemoryMonitorPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define gates for role-based access control
        $this->defineGates();

        // Register security-related model observers
        $this->registerObservers();
    }

    /**
     * Define the application's authorization gates.
     */
    protected function defineGates(): void
    {
        // Super Admin has all permissions
        Gate::before(function (User $user, $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });

        // Define permissions for different roles
        $this->defineAssetGates();
        $this->defineUserGates();
        $this->defineReportGates();
        $this->defineSettingsGates();
    }

    /**
     * Define gates for asset management.
     */
    protected function defineAssetGates(): void
    {
        Gate::define('manage-assets', function (User $user) {
            return $user->hasPermissionTo('manage assets');
        });

        Gate::define('view-asset', function (User $user, Asset $asset) {
            return $user->can('view', $asset);
        });

        Gate::define('edit-asset', function (User $user, Asset $asset) {
            return $user->can('update', $asset);
        });

        Gate::define('delete-asset', function (User $user, Asset $asset) {
            return $user->can('delete', $asset);
        });
    }

    /**
     * Define gates for user management.
     */
    protected function defineUserGates(): void
    {
        Gate::define('manage-users', function (User $user) {
            return $user->hasPermissionTo('manage users');
        });

        Gate::define('view-user', function (User $user, User $targetUser) {
            return $user->can('view', $targetUser);
        });

        Gate::define('edit-user', function (User $user, User $targetUser) {
            // Users can edit their own profile
            if ($user->id === $targetUser->id) {
                return true;
            }

            return $user->hasPermissionTo('edit users');
        });

        Gate::define('delete-user', function (User $user, User $targetUser) {
            // Prevent users from deleting themselves
            if ($user->id === $targetUser->id) {
                return false;
            }

            return $user->hasPermissionTo('delete users');
        });
    }

    /**
     * Define gates for report management.
     */
    protected function defineReportGates(): void
    {
        Gate::define('generate-reports', function (User $user) {
            return $user->hasPermissionTo('generate reports');
        });

        Gate::define('view-report', function (User $user, Report $report) {
            // Users can view their own reports
            if ($report->user_id === $user->id) {
                return true;
            }

            return $user->hasPermissionTo('view all reports');
        });
    }

    /**
     * Define gates for system settings.
     */
    protected function defineSettingsGates(): void
    {
        Gate::define('manage-settings', function (User $user) {
            return $user->hasRole('admin') || $user->hasRole('super-admin');
        });

        Gate::define('view-audit-logs', function (User $user) {
            return $user->hasPermissionTo('view audit logs');
        });
    }

    /**
     * Register model observers.
     */
    protected function registerObservers(): void
    {
        // Register model observers for logging and auditing
        Asset::observe(\App\Observers\AssetObserver::class);
        User::observe(\App\Observers\UserObserver::class);
    }
}
