<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Asset;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
// Use the correct PDF facade with an alias to avoid conflicts
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display the users landing page.
     *
     * @return \Illuminate\Http\Response
     */
    public function landing()
    {
        return view('users.landing');
    }

    /**
     * Display a listing of the users.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::with(['department', 'roles'])
            ->withCount('assets')
            ->latest()
            ->paginate(10);
            
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    /**
     * Display the specified user's profile.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        // Eager load relationships
        $user->load(['roles', 'department', 'assets' => function($query) {
            $query->latest('assigned_date')->take(5);
        }]);
        
        // Get user statistics
        $stats = [
            'assets_count' => $user->assets()->count(),
            'assigned_assets_count' => $user->assets()->where('status', 'assigned')->count(),
            'maintenance_requests_count' => $user->maintenanceRequests()->count(),
            'activity_logs_count' => $user->activityLogs()->count(),
        ];
        
        // Get recent activities
        $activities = ActivityLog::where('user_id', $user->id)
            ->with(['subject'])
            ->latest()
            ->take(10)
            ->get();
            
        // Get recently assigned assets
        $assignedAssets = $user->assets()
            ->with(['category', 'status'])
            ->orderBy('assigned_date', 'desc')
            ->take(5)
            ->get();
        
        return view('users.show', compact('user', 'stats', 'activities', 'assignedAssets'));
    }

    /**
     * Show the form for editing the specified user.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);
            
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);

        // Prevent deletion of the currently logged-in user
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Handle bulk user actions
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate,change_role,export',
            'selected_users' => 'required|array',
            'selected_users.*' => 'exists:users,id',
            'role' => 'required_if:action,change_role|exists:roles,id',
        ]);

        $selectedUsers = $request->input('selected_users');
        $currentUserId = auth()->id();
        
        // Prevent modifying self with bulk actions
        if (in_array($currentUserId, $selectedUsers)) {
            return back()->with('error', 'You cannot perform bulk actions on your own account.');
        }

        switch ($request->action) {
            case 'delete':
                return $this->bulkDestroy($request);
            case 'activate':
                return $this->bulkUpdateStatus($selectedUsers, true);
            case 'deactivate':
                return $this->bulkUpdateStatus($selectedUsers, false);
            case 'change_role':
                return $this->bulkChangeRole($selectedUsers, $request->role);
            case 'export':
                return $this->export($request);
            default:
                return back()->with('error', 'Invalid action.');
        }
    }

    /**
     * Bulk delete users
     */
    protected function bulkDestroy($request)
    {
        $selectedUsers = $request->input('selected_users');
        $currentUserId = auth()->id();
        
        // Filter out current user from deletion
        $usersToDelete = array_diff($selectedUsers, [$currentUserId]);
        
        if (empty($usersToDelete)) {
            return back()->with('error', 'No valid users selected for deletion.');
        }

        try {
            DB::beginTransaction();
            
            // Soft delete users
            $deletedCount = User::whereIn('id', $usersToDelete)->delete();
            
            // Log the bulk deletion
            ActivityLog::create([
                'user_id' => $currentUserId,
                'action' => 'bulk_delete',
                'model' => 'User',
                'model_id' => null,
                'properties' => ['count' => $deletedCount, 'user_ids' => $usersToDelete],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            DB::commit();
            
            return back()->with('success', "Successfully deleted {$deletedCount} users.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk user deletion failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete users. Please try again.');
        }
    }

    /**
     * Bulk update user status
     */
    protected function bulkUpdateStatus($userIds, $status)
    {
        try {
            $updatedCount = User::whereIn('id', $userIds)
                ->where('id', '!=', auth()->id())
                ->update(['is_active' => $status]);
                
            // Log the bulk status update
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'bulk_' . ($status ? 'activate' : 'deactivate'),
                'model' => 'User',
                'model_id' => null,
                'properties' => ['count' => $updatedCount, 'user_ids' => $userIds, 'status' => $status],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            $statusText = $status ? 'activated' : 'deactivated';
            return back()->with('success', "Successfully {$statusText} {$updatedCount} users.");
            
        } catch (\Exception $e) {
            Log::error('Bulk user status update failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to update user status. Please try again.');
        }
    }

    /**
     * Bulk change user role
     */
    protected function bulkChangeRole($userIds, $roleId)
    {
        try {
            $users = User::whereIn('id', $userIds)
                ->where('id', '!=', auth()->id())
                ->get();
                
            $updatedCount = 0;
            
            DB::beginTransaction();
            
            foreach ($users as $user) {
                // Only update if the user doesn't already have this role
                if (!$user->hasRole($roleId)) {
                    $user->syncRoles([$roleId]);
                    $updatedCount++;
                }
            }
            
            // Log the bulk role change
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'bulk_change_role',
                'model' => 'User',
                'model_id' => null,
                'properties' => [
                    'count' => $updatedCount, 
                    'user_ids' => $userIds, 
                    'role_id' => $roleId
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            DB::commit();
            
            return back()->with('success', "Successfully updated role for {$updatedCount} users.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk user role change failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to update user roles. Please try again.');
        }
    }

    /**
     * Display the user's activity log.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function activity(User $user)
    {
        $activities = ActivityLog::where('user_id', $user->id)
            ->with(['subject'])
            ->latest()
            ->paginate(20);
            
        return view('users.activity', compact('user', 'activities'));
    }

    /**
     * Display the user's assigned assets.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function assets(User $user)
    {
        $assets = $user->assets()
            ->with(['category', 'status', 'model'])
            ->orderBy('assigned_date', 'desc')
            ->paginate(20);
            
        return view('users.assets', compact('user', 'assets'));
    }

    /**
     * Export user data as PDF.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function exportPdf(User $user)
    {
        try {
            // Check if PDF facade is available
            if (!class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
                return redirect()->back()->with('error', 'PDF generation is not available. Please install the dompdf package.');
            }
            
            // Load user data with relationships
            $user->load([
                'roles', 
                'department', 
                'assets' => function($query) {
                    $query->with(['category', 'status']);
                }
            ]);
            
            // Generate PDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.users.pdf', compact('user'))
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'Arial',
                ]);
                
            $filename = 'user_' . Str::slug($user->name) . '_' . now()->format('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            Log::error('PDF Export Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Export user data as CSV.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function exportCsv(User $user)
    {
        try {
            // Load user data with relationships
            $user->load([
                'roles', 
                'department', 
                'location',
                'assets' => function($query) {
                    $query->with(['category', 'status']);
                }
            ]);
            
            $filename = 'user_' . Str::slug($user->name) . '_' . now()->format('Y-m-d') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];
            
            $callback = function() use ($user) {
                $file = fopen('php://output', 'w');
                
                // Add UTF-8 BOM for proper Excel encoding
                fwrite($file, "\xEF\xBB\xBF");
                
                // User Information Section
                fputcsv($file, ['USER INFORMATION']);
                fputcsv($file, ['Field', 'Value']);
                fputcsv($file, ['ID', $user->id]);
                fputcsv($file, ['Name', $user->name]);
                fputcsv($file, ['Email', $user->email]);
                fputcsv($file, ['Username', $user->username]);
                fputcsv($file, ['Employee ID', $user->employee_id]);
                fputcsv($file, ['Department', $user->department ? $user->department->name : 'N/A']);
                fputcsv($file, ['Location', $user->location ? $user->location->name : 'N/A']);
                fputcsv($file, ['Position', $user->position]);
                fputcsv($file, ['Designation', $user->designation]);
                fputcsv($file, ['Status', $user->is_active ? 'Active' : 'Inactive']);
                fputcsv($file, ['Email Notifications', $user->receive_email_notifications ? 'Enabled' : 'Disabled']);
                fputcsv($file, ['Last Login', $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : 'Never']);
                
                // Contact Information Section
                fputcsv($file, []);
                fputcsv($file, ['CONTACT INFORMATION']);
                fputcsv($file, ['Phone', $user->phone]);
                fputcsv($file, ['Work Phone', $user->phone_work]);
                fputcsv($file, ['Mobile', $user->phone_mobile]);
                fputcsv($file, ['Address', $user->full_address]);
                fputcsv($file, ['Website', $user->website]);
                
                // Roles and Permissions Section
                fputcsv($file, []);
                fputcsv($file, ['ROLES']);
                fputcsv($file, ['Role Name', 'Description']);
                foreach ($user->roles as $role) {
                    fputcsv($file, [$role->name, $role->description ?? 'N/A']);
                }
                
                // Assets Section
                fputcsv($file, []);
                fputcsv($file, ['ASSETS (Total: ' . $user->assets->count() . ')']);
                fputcsv($file, ['Asset Tag', 'Name', 'Category', 'Status', 'Assigned Date', 'Purchase Date', 'Purchase Cost']);
                
                foreach ($user->assets as $asset) {
                    fputcsv($file, [
                        $asset->asset_tag,
                        $asset->name,
                        $asset->category ? $asset->category->name : 'N/A',
                        $asset->status ? $asset->status->name : 'N/A',
                        $asset->assigned_date ? $asset->assigned_date->format('Y-m-d') : 'N/A',
                        $asset->purchase_date ? $asset->purchase_date->format('Y-m-d') : 'N/A',
                        $asset->purchase_cost ? '\'' . number_format($asset->purchase_cost, 2) : 'N/A',
                    ]);
                }
                
                // Activity Log Section
                fputcsv($file, []);
                fputcsv($file, ['RECENT ACTIVITY (Last 10 entries)']);
                fputcsv($file, ['Date', 'Activity', 'Description']);
                
                $activities = $user->activityLogs()
                    ->with('causer')
                    ->latest()
                    ->take(10)
                    ->get();
                    
                foreach ($activities as $activity) {
                    fputcsv($file, [
                        $activity->created_at->format('Y-m-d H:i:s'),
                        $activity->log_name,
                        $activity->description
                    ]);
                }
                
                fclose($file);
            };
            
            return response()->stream($callback, 200, $headers);
            
        } catch (\Exception $e) {
            Log::error('CSV Export Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate CSV: ' . $e->getMessage());
        }
    }
}
