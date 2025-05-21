<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any reports.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view reports');
    }

    /**
     * Determine whether the user can view the report.
     */
    public function view(User $user, Report $report): bool
    {
        return $user->hasPermissionTo('view reports') ||
            $user->id === $report->created_by ||
            $report->is_public;
    }

    /**
     * Determine whether the user can create reports.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create reports');
    }

    /**
     * Determine whether the user can update the report.
     */
    public function update(User $user, Report $report): bool
    {
        return $user->hasPermissionTo('edit reports') ||
            $user->id === $report->created_by;
    }

    /**
     * Determine whether the user can delete the report.
     */
    public function delete(User $user, Report $report): bool
    {
        return $user->hasPermissionTo('delete reports') ||
            $user->id === $report->created_by;
    }

    /**
     * Determine whether the user can restore the report.
     */
    public function restore(User $user, Report $report): bool
    {
        return $user->hasPermissionTo('restore reports');
    }

    /**
     * Determine whether the user can permanently delete the report.
     */
    public function forceDelete(User $user, Report $report): bool
    {
        return $user->hasPermissionTo('force delete reports');
    }

    /**
     * Determine whether the user can export the report.
     */
    public function export(User $user, $report): bool
    {
        // Users can export their own reports
        if ($user->id === $report->created_by) {
            return true;
        }

        // Check if report is public
        if ($report->is_public) {
            return true;
        }

        // Check if user has permission to export any report
        return $user->can('export reports') ||
               $user->can('export any report');
    }

    /**
     * Determine whether the user can download the report.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function download(User $user, $report)
    {
        // Users can download their own reports, public reports, or if they have permission
        return $user->id === $report->created_by ||
               $report->is_public ||
               $user->can('download all reports');
    }

    /**
     * Determine whether the user can generate the report.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function generate(User $user, $report)
    {
        // Users can generate reports they can view
        return $this->view($user, $report);
    }

    /**
     * Determine whether the user can schedule the report.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function schedule(User $user, $report): bool
    {
        // Users can schedule their own reports
        if ($user->id === $report->created_by) {
            return $user->can('schedule reports');
        }

        // Check if user can schedule any report
        return $user->can('schedule any report') ||
               $user->hasRole('admin');
    }
}
