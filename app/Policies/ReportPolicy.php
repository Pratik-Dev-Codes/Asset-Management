<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReportPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view reports') || 
               $user->can('view own reports');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, $report): bool
    {
        // Users can view their own reports
        if ($user->id === $report->created_by) {
            return true;
        }
        
        // Check if report is public
        if ($report->is_public) {
            return true;
        }
        
        // Check if user has permission to view all reports
        return $user->can('view all reports') || 
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create reports') || 
               $user->hasRole('manager');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, $report): bool
    {
        // Users can update their own reports
        if ($user->id === $report->created_by) {
            return $user->can('update own reports');
        }
        
        // Check if user can update any report
        return $user->can('update reports') || 
               $user->can('update any report');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, $report): bool
    {
        // Users can delete their own reports
        if ($user->id === $report->created_by) {
            return $user->can('delete own reports');
        }
        
        // Check if user can delete any report
        return $user->can('delete reports') || 
               $user->can('delete any report');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, $report): bool
    {
        // Only super admins can force delete reports
        return $user->hasRole('super-admin');
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
     * @param  \App\Models\User  $user
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
     * @param  \App\Models\User  $user
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
