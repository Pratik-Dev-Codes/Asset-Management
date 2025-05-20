<?php

namespace App\Policies;

use App\Models\ReportFile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReportFilePolicy
{
    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('view report files');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, ReportFile $reportFile)
    {
        // Users can view files for reports they can view
        return $user->can('view', $reportFile->report);
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('generate reports');
    }

    /**
     * Determine whether the user can download the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function download(User $user, ReportFile $reportFile)
    {
        // Users can download files for reports they can view
        return $user->can('view', $reportFile->report);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, ReportFile $reportFile)
    {
        // Only the user who generated the file or an admin can update it
        return $user->id === $reportFile->generated_by || $user->can('update all report files');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, ReportFile $reportFile)
    {
        // Users can delete their own files or if they have permission to delete all files
        return $user->id === $reportFile->generated_by || $user->can('delete all report files');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, ReportFile $reportFile)
    {
        return $user->can('restore report files');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, ReportFile $reportFile)
    {
        return $user->can('force delete report files');
    }

    /**
     * Determine whether the user can clean up expired report files.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function cleanup(User $user)
    {
        return $user->can('cleanup report files');
    }
}
