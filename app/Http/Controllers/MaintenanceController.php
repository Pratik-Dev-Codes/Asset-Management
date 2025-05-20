<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceRequest;
use App\Models\SparePart;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class MaintenanceController extends Controller
{
    /**
     * Display a listing of the maintenance requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function indexRequests(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.request.view')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view maintenance requests.');
        }

        $query = MaintenanceRequest::with(['asset', 'requestUser', 'assignedTo']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to_user_id', $request->input('assigned_to'));
        }

        // Sort results
        $sortField = $request->input('sort', 'reported_date');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Paginate the results
        $requests = $query->paginate(15);

        // Get data for filters
        $users = User::orderBy('first_name')->get();
        $statuses = ['Open', 'Assigned', 'In Progress', 'On Hold', 'Resolved', 'Closed', 'Cancelled'];
        $priorities = ['Critical', 'High', 'Medium', 'Low'];

        return view('maintenance.requests.index', compact(
            'requests',
            'users',
            'statuses',
            'priorities'
        ));
    }

    /**
     * Show the form for creating a new maintenance request.
     *
     * @return \Illuminate\Http\Response
     */
    public function createRequest()
    {
        // Check permission
        if (!auth()->user()->can('maintenance.request.create')) {
            return redirect()->route('maintenance.requests')->with('error', 'You do not have permission to create maintenance requests.');
        }

        $assets = Asset::orderBy('name')->get();
        $priorities = ['Critical', 'High', 'Medium', 'Low'];
        $users = User::orderBy('first_name')->get();

        return view('maintenance.requests.create', compact('assets', 'priorities', 'users'));
    }

    /**
     * Store a newly created maintenance request in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeRequest(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.request.create')) {
            return redirect()->route('maintenance.requests')->with('error', 'You do not have permission to create maintenance requests.');
        }

        // Validate the request
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|string|in:Critical,High,Medium,Low',
            'required_completion_date' => 'nullable|date|after_or_equal:today',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'documents.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:10240',
        ]);

        // Begin transaction
        DB::beginTransaction();

        try {
            // Create the maintenance request
            $maintenanceRequest = new MaintenanceRequest();
            $maintenanceRequest->asset_id = $request->asset_id;
            $maintenanceRequest->request_user_id = Auth::id();
            $maintenanceRequest->title = $request->title;
            $maintenanceRequest->description = $request->description;
            $maintenanceRequest->priority = $request->priority;
            $maintenanceRequest->status = $request->assigned_to_user_id ? 'Assigned' : 'Open';
            $maintenanceRequest->reported_date = Carbon::now();
            $maintenanceRequest->required_completion_date = $request->required_completion_date;
            $maintenanceRequest->assigned_to_user_id = $request->assigned_to_user_id;
            $maintenanceRequest->save();

            // Handle document uploads
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $path = $file->store('maintenance/requests/documents', 'public');
                    $mimeType = $file->getMimeType();
                    
                    // Create thumbnail if it's an image
                    $thumbnailPath = null;
                    if (strpos($mimeType, 'image/') === 0) {
                        $thumbnailPath = 'maintenance/requests/thumbnails/' . pathinfo($path, PATHINFO_FILENAME) . '.jpg';
                        $thumb = Image::make($file)->resize(200, 200, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                        Storage::disk('public')->put($thumbnailPath, $thumb->stream());
                    }

                    // Save document record
                    $document = new Document();
                    $document->documentable_id = $maintenanceRequest->id;
                    $document->documentable_type = 'App\\Models\\MaintenanceRequest';
                    $document->file_path = $path;
                    $document->thumbnail_path = $thumbnailPath;
                    $document->original_filename = $file->getClientOriginalName();
                    $document->mime_type = $mimeType;
                    $document->file_size_kb = $file->getSize() / 1024;
                    $document->uploaded_by_user_id = Auth::id();
                    $document->save();
                }
            }

            // If assigned to a user, update the asset status
            if ($request->assigned_to_user_id) {
                $asset = Asset::find($request->asset_id);
                if ($asset && $asset->status === 'operational') {
                    $asset->status = 'under-maintenance';
                    $asset->save();
                }
            }

            DB::commit();

            return redirect()->route('maintenance.requests.show', $maintenanceRequest)
                ->with('success', 'Maintenance request created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating maintenance request: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified maintenance request.
     *
     * @param  \App\Models\MaintenanceRequest  $maintenanceRequest
     * @return \Illuminate\Http\Response
     */
    public function showRequest(MaintenanceRequest $maintenanceRequest)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.request.view')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view maintenance requests.');
        }

        // Load related data
        $maintenanceRequest->load([
            'asset', 
            'requestUser', 
            'assignedTo', 
            'documents'
        ]);

        // Get related maintenance logs
        $maintenanceLogs = MaintenanceLog::where('maintenance_request_id', $maintenanceRequest->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('maintenance.requests.show', compact('maintenanceRequest', 'maintenanceLogs'));
    }

    /**
     * Show the form for editing the specified maintenance request.
     *
     * @param  \App\Models\MaintenanceRequest  $maintenanceRequest
     * @return \Illuminate\Http\Response
     */
    public function editRequest(MaintenanceRequest $maintenanceRequest)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.request.edit')) {
            return redirect()->route('maintenance.requests.show', $maintenanceRequest)->with('error', 'You do not have permission to edit maintenance requests.');
        }

        $assets = Asset::orderBy('name')->get();
        $priorities = ['Critical', 'High', 'Medium', 'Low'];
        $statuses = ['Open', 'Assigned', 'In Progress', 'On Hold', 'Resolved', 'Closed', 'Cancelled'];
        $users = User::orderBy('first_name')->get();
        $documents = $maintenanceRequest->documents;

        return view('maintenance.requests.edit', compact(
            'maintenanceRequest',
            'assets',
            'priorities',
            'statuses',
            'users',
            'documents'
        ));
    }

    /**
     * Update the specified maintenance request in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MaintenanceRequest  $maintenanceRequest
     * @return \Illuminate\Http\Response
     */
    public function updateRequest(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.request.edit')) {
            return redirect()->route('maintenance.requests.show', $maintenanceRequest)->with('error', 'You do not have permission to edit maintenance requests.');
        }

        // Validate the request
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|string|in:Critical,High,Medium,Low',
            'status' => 'required|string|in:Open,Assigned,In Progress,On Hold,Resolved,Closed,Cancelled',
            'required_completion_date' => 'nullable|date',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'resolution_details' => 'nullable|string',
            'documents.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:10240',
        ]);

        // Begin transaction
        DB::beginTransaction();

        try {
            // Check for status changes
            $statusChanged = $maintenanceRequest->status !== $request->status;
            $oldStatus = $maintenanceRequest->status;
            $newStatus = $request->status;
            $resolvedNow = false;

            // Update the maintenance request
            $maintenanceRequest->asset_id = $request->asset_id;
            $maintenanceRequest->title = $request->title;
            $maintenanceRequest->description = $request->description;
            $maintenanceRequest->priority = $request->priority;
            $maintenanceRequest->status = $request->status;
            $maintenanceRequest->required_completion_date = $request->required_completion_date;
            $maintenanceRequest->assigned_to_user_id = $request->assigned_to_user_id;
            $maintenanceRequest->resolution_details = $request->resolution_details;

            // If status changed to Resolved, set resolved_at timestamp
            if ($statusChanged && $newStatus === 'Resolved' && $oldStatus !== 'Resolved') {
                $maintenanceRequest->resolved_at = Carbon::now();
                $resolvedNow = true;
            }

            $maintenanceRequest->save();

            // Handle document uploads
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $path = $file->store('maintenance/requests/documents', 'public');
                    $mimeType = $file->getMimeType();
                    
                    // Create thumbnail if it's an image
                    $thumbnailPath = null;
                    if (strpos($mimeType, 'image/') === 0) {
                        $thumbnailPath = 'maintenance/requests/thumbnails/' . pathinfo($path, PATHINFO_FILENAME) . '.jpg';
                        $thumb = Image::make($file)->resize(200, 200, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                        Storage::disk('public')->put($thumbnailPath, $thumb->stream());
                    }

                    // Save document record
                    $document = new Document();
                    $document->documentable_id = $maintenanceRequest->id;
                    $document->documentable_type = 'App\\Models\\MaintenanceRequest';
                    $document->file_path = $path;
                    $document->thumbnail_path = $thumbnailPath;
                    $document->original_filename = $file->getClientOriginalName();
                    $document->mime_type = $mimeType;
                    $document->file_size_kb = $file->getSize() / 1024;
                    $document->uploaded_by_user_id = Auth::id();
                    $document->save();
                }
            }

            // Handle asset status updates based on request status
            $asset = Asset::find($request->asset_id);
            
            if ($asset) {
                // If status changed to Resolved or Closed and was previously In Progress or Assigned
                if ($statusChanged && ($newStatus === 'Resolved' || $newStatus === 'Closed') && 
                    ($oldStatus === 'In Progress' || $oldStatus === 'Assigned')) {
                    // Check if there are other open maintenance requests for this asset
                    $otherOpenRequests = MaintenanceRequest::where('asset_id', $asset->id)
                        ->where('id', '!=', $maintenanceRequest->id)
                        ->whereIn('status', ['Open', 'Assigned', 'In Progress'])
                        ->count();
                    
                    if ($otherOpenRequests === 0) {
                        $asset->status = 'operational';
                        $asset->last_maintenance_date = Carbon::now();
                        $asset->save();
                    }
                }
                // If status changed to In Progress or Assigned
                elseif ($statusChanged && ($newStatus === 'In Progress' || $newStatus === 'Assigned') &&
                    $asset->status === 'operational') {
                    $asset->status = 'under-maintenance';
                    $asset->save();
                }
            }

            // Create a maintenance log if resolved now
            if ($resolvedNow && $request->filled('resolution_details')) {
                $maintenanceLog = new MaintenanceLog();
                $maintenanceLog->asset_id = $request->asset_id;
                $maintenanceLog->maintenance_request_id = $maintenanceRequest->id;
                $maintenanceLog->maintenance_type = 'Corrective';
                $maintenanceLog->title = 'Resolved: ' . $request->title;
                $maintenanceLog->summary = $request->resolution_details;
                $maintenanceLog->start_datetime = $maintenanceRequest->reported_date;
                $maintenanceLog->completion_datetime = Carbon::now();
                $maintenanceLog->performed_by_user_id = Auth::id();
                $maintenanceLog->save();
            }

            DB::commit();

            return redirect()->route('maintenance.requests.show', $maintenanceRequest)
                ->with('success', 'Maintenance request updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating maintenance request: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified maintenance request from storage.
     *
     * @param  \App\Models\MaintenanceRequest  $maintenanceRequest
     * @return \Illuminate\Http\Response
     */
    public function destroyRequest(MaintenanceRequest $maintenanceRequest)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.request.delete')) {
            return redirect()->route('maintenance.requests')->with('error', 'You do not have permission to delete maintenance requests.');
        }

        // Check if there are maintenance logs linked to this request
        $logsCount = MaintenanceLog::where('maintenance_request_id', $maintenanceRequest->id)->count();
        
        if ($logsCount > 0) {
            return redirect()->route('maintenance.requests.show', $maintenanceRequest)
                ->with('error', 'Cannot delete maintenance request that has linked maintenance logs.');
        }

        // Begin transaction
        DB::beginTransaction();

        try {
            // Delete associated documents
            foreach ($maintenanceRequest->documents as $document) {
                Storage::disk('public')->delete($document->file_path);
                if ($document->thumbnail_path) {
                    Storage::disk('public')->delete($document->thumbnail_path);
                }
                $document->delete();
            }

            // Delete the maintenance request
            $maintenanceRequest->delete();

            DB::commit();

            return redirect()->route('maintenance.requests')
                ->with('success', 'Maintenance request deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting maintenance request: ' . $e->getMessage());
        }
    }

    /**
     * Display a listing of the maintenance logs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function indexLogs(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.log.view')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view maintenance logs.');
        }

        $query = MaintenanceLog::with(['asset', 'performedBy', 'maintenanceRequest']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        if ($request->filled('maintenance_type')) {
            $query->where('maintenance_type', $request->input('maintenance_type'));
        }

        if ($request->filled('performed_by')) {
            $query->where('performed_by_user_id', $request->input('performed_by'));
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('completion_datetime', [
                $request->input('date_from') . ' 00:00:00',
                $request->input('date_to') . ' 23:59:59'
            ]);
        }

        // Sort results
        $sortField = $request->input('sort', 'completion_datetime');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Paginate the results
        $logs = $query->paginate(15);

        // Get data for filters
        $users = User::orderBy('first_name')->get();
        $maintenanceTypes = ['Preventive', 'Corrective', 'Predictive', 'Inspection', 'Calibration', 'Upgrade', 'Emergency'];

        return view('maintenance.logs.index', compact(
            'logs',
            'users',
            'maintenanceTypes'
        ));
    }

    /**
     * Show the form for creating a new maintenance log.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createLog(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.log.create')) {
            return redirect()->route('maintenance.logs')->with('error', 'You do not have permission to create maintenance logs.');
        }

        $assets = Asset::orderBy('name')->get();
        $maintenanceTypes = ['Preventive', 'Corrective', 'Predictive', 'Inspection', 'Calibration', 'Upgrade', 'Emergency'];
        $users = User::orderBy('first_name')->get();
        $spareParts = SparePart::orderBy('name')->get();

        // Check if this is linked to a request
        $requestId = $request->input('request_id');
        $maintenanceRequest = null;
        
        if ($requestId) {
            $maintenanceRequest = MaintenanceRequest::with('asset')->find($requestId);
        }

        return view('maintenance.logs.create', compact(
            'assets',
            'maintenanceTypes',
            'users',
            'spareParts',
            'maintenanceRequest'
        ));
    }

    /**
     * Store a newly created maintenance log in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeLog(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.log.create')) {
            return redirect()->route('maintenance.logs')->with('error', 'You do not have permission to create maintenance logs.');
        }

        // Validate the request
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'maintenance_request_id' => 'nullable|exists:maintenance_requests,id',
            'maintenance_schedule_id' => 'nullable|exists:maintenance_schedules,id',
            'maintenance_type' => 'required|string|in:Preventive,Corrective,Predictive,Inspection,Calibration,Upgrade,Emergency',
            'title' => 'required|string|max:255',
            'summary' => 'required|string',
            'start_datetime' => 'required|date',
            'completion_datetime' => 'required|date|after_or_equal:start_datetime',
            'performed_by_user_id' => 'nullable|exists:users,id',
            'external_technician_name' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'downtime_hours' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'spare_parts' => 'nullable|array',
            'spare_parts.*.id' => 'required|exists:spare_parts,id',
            'spare_parts.*.quantity' => 'required|integer|min:1',
            'spare_parts.*.cost' => 'nullable|numeric|min:0',
            'documents.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:10240',
        ]);

        // Begin transaction
        DB::beginTransaction();

        try {
            // Create the maintenance log
            $maintenanceLog = new MaintenanceLog();
            $maintenanceLog->asset_id = $request->asset_id;
            $maintenanceLog->maintenance_request_id = $request->maintenance_request_id;
            $maintenanceLog->maintenance_schedule_id = $request->maintenance_schedule_id;
            $maintenanceLog->maintenance_type = $request->maintenance_type;
            $maintenanceLog->title = $request->title;
            $maintenanceLog->summary = $request->summary;
            $maintenanceLog->start_datetime = $request->start_datetime;
            $maintenanceLog->completion_datetime = $request->completion_datetime;
            $maintenanceLog->performed_by_user_id = $request->performed_by_user_id ?? Auth::id();
            $maintenanceLog->external_technician_name = $request->external_technician_name;
            $maintenanceLog->cost = $request->cost;
            $maintenanceLog->downtime_hours = $request->downtime_hours;
            $maintenanceLog->notes = $request->notes;
            $maintenanceLog->save();

            // Handle spare parts
            if ($request->has('spare_parts')) {
                foreach ($request->spare_parts as $partData) {
                    if (!isset($partData['id']) || !isset($partData['quantity'])) {
                        continue;
                    }

                    $sparePart = SparePart::find($partData['id']);
                    
                    if ($sparePart) {
                        // Record usage
                        DB::table('maintenance_log_spare_parts')->insert([
                            'maintenance_log_id' => $maintenanceLog->id,
                            'spare_part_id' => $sparePart->id,
                            'quantity_used' => $partData['quantity'],
                            'cost_at_time_of_use' => $partData['cost'] ?? $sparePart->unit_price,
                            'notes' => $partData['notes'] ?? null,
                            'created_at' => Carbon::now(),
                        ]);

                        // Update inventory
                        $newQuantity = max(0, $sparePart->quantity_on_hand - $partData['quantity']);
                        $sparePart->quantity_on_hand = $newQuantity;
                        $sparePart->save();
                    }
                }
            }

            // Handle document uploads
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $path = $file->store('maintenance/logs/documents', 'public');
                    $mimeType = $file->getMimeType();
                    
                    // Create thumbnail if it's an image
                    $thumbnailPath = null;
                    if (strpos($mimeType, 'image/') === 0) {
                        $thumbnailPath = 'maintenance/logs/thumbnails/' . pathinfo($path, PATHINFO_FILENAME) . '.jpg';
                        $thumb = Image::make($file)->resize(200, 200, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                        Storage::disk('public')->put($thumbnailPath, $thumb->stream());
                    }

                    // Save document record
                    $document = new Document();
                    $document->documentable_id = $maintenanceLog->id;
                    $document->documentable_type = 'App\\Models\\MaintenanceLog';
                    $document->file_path = $path;
                    $document->thumbnail_path = $thumbnailPath;
                    $document->original_filename = $file->getClientOriginalName();
                    $document->mime_type = $mimeType;
                    $document->file_size_kb = $file->getSize() / 1024;
                    $document->uploaded_by_user_id = Auth::id();
                    $document->save();
                }
            }

            // Update asset's last maintenance date
            $asset = Asset::find($request->asset_id);
            if ($asset) {
                $asset->last_maintenance_date = $request->completion_datetime;
                
                // If this is preventive maintenance, schedule the next one
                if ($request->maintenance_type === 'Preventive' && $asset->expected_lifetime_years > 0) {
                    // Simple logic - schedule next maintenance based on asset type
                    // Real implementation would be more sophisticated
                    $months = 3; // Default quarterly
                    
                    if ($asset->expected_lifetime_years >= 10) {
                        $months = 6; // Bi-annual for long-lived assets
                    } elseif ($asset->expected_lifetime_years <= 2) {
                        $months = 1; // Monthly for short-lived assets
                    }
                    
                    $asset->next_maintenance_date = Carbon::parse($request->completion_datetime)->addMonths($months);
                }
                
                // If there was a request, update its status
                if ($request->maintenance_request_id) {
                    $maintenanceRequest = MaintenanceRequest::find($request->maintenance_request_id);
                    if ($maintenanceRequest && $maintenanceRequest->status !== 'Closed') {
                        $maintenanceRequest->status = 'Resolved';
                        $maintenanceRequest->resolution_details = $request->summary;
                        $maintenanceRequest->resolved_at = Carbon::now();
                        $maintenanceRequest->save();
                    }
                    
                    // Check if there are other open maintenance requests for this asset
                    $otherOpenRequests = MaintenanceRequest::where('asset_id', $asset->id)
                        ->where('id', '!=', $request->maintenance_request_id)
                        ->whereIn('status', ['Open', 'Assigned', 'In Progress'])
                        ->count();
                    
                    if ($otherOpenRequests === 0) {
                        $asset->status = 'operational';
                    }
                }
                
                // If there was a schedule, update its next due date
                if ($request->maintenance_schedule_id) {
                    $schedule = MaintenanceSchedule::find($request->maintenance_schedule_id);
                    if ($schedule) {
                        $this->updateMaintenanceSchedule($schedule, $request->completion_datetime);
                    }
                }
                
                $asset->save();
            }

            DB::commit();

            // Redirect based on context
            if ($request->maintenance_request_id) {
                return redirect()->route('maintenance.requests.show', $request->maintenance_request_id)
                    ->with('success', 'Maintenance log created successfully.');
            } else {
                return redirect()->route('maintenance.logs.show', $maintenanceLog)
                    ->with('success', 'Maintenance log created successfully.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating maintenance log: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified maintenance log.
     *
     * @param  \App\Models\MaintenanceLog  $maintenanceLog
     * @return \Illuminate\Http\Response
     */
    public function showLog(MaintenanceLog $maintenanceLog)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.log.view')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view maintenance logs.');
        }

        // Load related data
        $maintenanceLog->load([
            'asset', 
            'performedBy', 
            'maintenanceRequest', 
            'maintenanceSchedule',
            'documents'
        ]);

        // Get spare parts used
        $spareParts = DB::table('maintenance_log_spare_parts')
            ->join('spare_parts', 'maintenance_log_spare_parts.spare_part_id', '=', 'spare_parts.id')
            ->where('maintenance_log_spare_parts.maintenance_log_id', $maintenanceLog->id)
            ->select(
                'spare_parts.id',
                'spare_parts.name',
                'spare_parts.part_number',
                'maintenance_log_spare_parts.quantity_used',
                'maintenance_log_spare_parts.cost_at_time_of_use',
                'maintenance_log_spare_parts.notes'
            )
            ->get();

        return view('maintenance.logs.show', compact('maintenanceLog', 'spareParts'));
    }

    /**
     * Show the form for editing the specified maintenance log.
     *
     * @param  \App\Models\MaintenanceLog  $maintenanceLog
     * @return \Illuminate\Http\Response
     */
    public function editLog(MaintenanceLog $maintenanceLog)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.log.edit')) {
            return redirect()->route('maintenance.logs.show', $maintenanceLog)->with('error', 'You do not have permission to edit maintenance logs.');
        }

        $assets = Asset::orderBy('name')->get();
        $maintenanceTypes = ['Preventive', 'Corrective', 'Predictive', 'Inspection', 'Calibration', 'Upgrade', 'Emergency'];
        $users = User::orderBy('first_name')->get();
        $spareParts = SparePart::orderBy('name')->get();
        $documents = $maintenanceLog->documents;

        // Get spare parts already used
        $usedSpareParts = DB::table('maintenance_log_spare_parts')
            ->join('spare_parts', 'maintenance_log_spare_parts.spare_part_id', '=', 'spare_parts.id')
            ->where('maintenance_log_spare_parts.maintenance_log_id', $maintenanceLog->id)
            ->select(
                'spare_parts.id',
                'spare_parts.name',
                'spare_parts.part_number',
                'maintenance_log_spare_parts.quantity_used',
                'maintenance_log_spare_parts.cost_at_time_of_use',
                'maintenance_log_spare_parts.notes'
            )
            ->get();

        return view('maintenance.logs.edit', compact(
            'maintenanceLog',
            'assets',
            'maintenanceTypes',
            'users',
            'spareParts',
            'documents',
            'usedSpareParts'
        ));
    }

    /**
     * Update the specified maintenance log in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MaintenanceLog  $maintenanceLog
     * @return \Illuminate\Http\Response
     */
    public function updateLog(Request $request, MaintenanceLog $maintenanceLog)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.log.edit')) {
            return redirect()->route('maintenance.logs.show', $maintenanceLog)->with('error', 'You do not have permission to edit maintenance logs.');
        }

        // Validate the request
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'maintenance_type' => 'required|string|in:Preventive,Corrective,Predictive,Inspection,Calibration,Upgrade,Emergency',
            'title' => 'required|string|max:255',
            'summary' => 'required|string',
            'start_datetime' => 'required|date',
            'completion_datetime' => 'required|date|after_or_equal:start_datetime',
            'performed_by_user_id' => 'nullable|exists:users,id',
            'external_technician_name' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'downtime_hours' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'spare_parts' => 'nullable|array',
            'spare_parts.*.id' => 'required|exists:spare_parts,id',
            'spare_parts.*.quantity' => 'required|integer|min:1',
            'spare_parts.*.cost' => 'nullable|numeric|min:0',
            'documents.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:10240',
        ]);

        // Begin transaction
        DB::beginTransaction();

        try {
            // Update the maintenance log
            $maintenanceLog->asset_id = $request->asset_id;
            $maintenanceLog->maintenance_type = $request->maintenance_type;
            $maintenanceLog->title = $request->title;
            $maintenanceLog->summary = $request->summary;
            $maintenanceLog->start_datetime = $request->start_datetime;
            $maintenanceLog->completion_datetime = $request->completion_datetime;
            $maintenanceLog->performed_by_user_id = $request->performed_by_user_id;
            $maintenanceLog->external_technician_name = $request->external_technician_name;
            $maintenanceLog->cost = $request->cost;
            $maintenanceLog->downtime_hours = $request->downtime_hours;
            $maintenanceLog->notes = $request->notes;
            $maintenanceLog->save();

            // Handle document uploads
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $path = $file->store('maintenance/logs/documents', 'public');
                    $mimeType = $file->getMimeType();
                    
                    // Create thumbnail if it's an image
                    $thumbnailPath = null;
                    if (strpos($mimeType, 'image/') === 0) {
                        $thumbnailPath = 'maintenance/logs/thumbnails/' . pathinfo($path, PATHINFO_FILENAME) . '.jpg';
                        $thumb = Image::make($file)->resize(200, 200, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                        Storage::disk('public')->put($thumbnailPath, $thumb->stream());
                    }

                    // Save document record
                    $document = new Document();
                    $document->documentable_id = $maintenanceLog->id;
                    $document->documentable_type = 'App\\Models\\MaintenanceLog';
                    $document->file_path = $path;
                    $document->thumbnail_path = $thumbnailPath;
                    $document->original_filename = $file->getClientOriginalName();
                    $document->mime_type = $mimeType;
                    $document->file_size_kb = $file->getSize() / 1024;
                    $document->uploaded_by_user_id = Auth::id();
                    $document->save();
                }
            }

            // Update asset's last maintenance date
            $asset = Asset::find($request->asset_id);
            if ($asset) {
                $asset->last_maintenance_date = $request->completion_datetime;
                $asset->save();
            }

            DB::commit();

            return redirect()->route('maintenance.logs.show', $maintenanceLog)
                ->with('success', 'Maintenance log updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating maintenance log: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified maintenance log from storage.
     *
     * @param  \App\Models\MaintenanceLog  $maintenanceLog
     * @return \Illuminate\Http\Response
     */
    public function destroyLog(MaintenanceLog $maintenanceLog)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.log.delete')) {
            return redirect()->route('maintenance.logs')->with('error', 'You do not have permission to delete maintenance logs.');
        }

        // Begin transaction
        DB::beginTransaction();

        try {
            // Delete associated documents
            foreach ($maintenanceLog->documents as $document) {
                Storage::disk('public')->delete($document->file_path);
                if ($document->thumbnail_path) {
                    Storage::disk('public')->delete($document->thumbnail_path);
                }
                $document->delete();
            }

            // Return spare parts to inventory
            $spareParts = DB::table('maintenance_log_spare_parts')
                ->where('maintenance_log_id', $maintenanceLog->id)
                ->get();
            
            foreach ($spareParts as $part) {
                $sparePart = SparePart::find($part->spare_part_id);
                if ($sparePart) {
                    $sparePart->quantity_on_hand += $part->quantity_used;
                    $sparePart->save();
                }
            }

            // Delete spare part associations
            DB::table('maintenance_log_spare_parts')
                ->where('maintenance_log_id', $maintenanceLog->id)
                ->delete();

            // Delete the maintenance log
            $maintenanceLog->delete();

            DB::commit();

            return redirect()->route('maintenance.logs')
                ->with('success', 'Maintenance log deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting maintenance log: ' . $e->getMessage());
        }
    }

    /**
     * Display a listing of maintenance schedules.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function indexSchedules(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.schedule.view')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view maintenance schedules.');
        }

        $query = MaintenanceSchedule::with(['asset', 'assignedTo']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('maintenance_type')) {
            $query->where('maintenance_type', $request->input('maintenance_type'));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to_user_id', $request->input('assigned_to'));
        }

        if ($request->filled('due_date_from') && $request->filled('due_date_to')) {
            $query->whereBetween('next_due_date', [
                $request->input('due_date_from'),
                $request->input('due_date_to')
            ]);
        }

        if ($request->filled('status')) {
            switch ($request->input('status')) {
                case 'upcoming':
                    $query->where('next_due_date', '>', Carbon::now());
                    break;
                case 'overdue':
                    $query->where('next_due_date', '<', Carbon::now());
                    break;
                case 'today':
                    $query->whereDate('next_due_date', Carbon::today());
                    break;
            }
        }

        // Sort results
        $sortField = $request->input('sort', 'next_due_date');
        $sortDirection = $request->input('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Paginate the results
        $schedules = $query->paginate(15);

        // Get data for filters
        $users = User::orderBy('first_name')->get();
        $maintenanceTypes = ['Preventive', 'Predictive', 'Inspection', 'Calibration'];
        $statuses = [
            'all' => 'All Schedules',
            'upcoming' => 'Upcoming',
            'overdue' => 'Overdue',
            'today' => 'Due Today'
        ];

        return view('maintenance.schedules.index', compact(
            'schedules',
            'users',
            'maintenanceTypes',
            'statuses'
        ));
    }

    /**
     * Show the form for creating a new maintenance schedule.
     *
     * @return \Illuminate\Http\Response
     */
    public function createSchedule()
    {
        // Check permission
        if (!auth()->user()->can('maintenance.schedule.create')) {
            return redirect()->route('maintenance.schedules')->with('error', 'You do not have permission to create maintenance schedules.');
        }

        $assets = Asset::where('status', '!=', 'retired')->orderBy('name')->get();
        $maintenanceTypes = ['Preventive', 'Predictive', 'Inspection', 'Calibration'];
        $users = User::orderBy('first_name')->get();
        $recurrenceTypes = [
            'Once' => 'One-time only',
            'Daily' => 'Daily',
            'Weekly' => 'Weekly',
            'Monthly' => 'Monthly',
            'Yearly' => 'Yearly',
            'UsageBased' => 'Based on usage'
        ];

        return view('maintenance.schedules.create', compact(
            'assets',
            'maintenanceTypes',
            'users',
            'recurrenceTypes'
        ));
    }

    /**
     * Store a newly created maintenance schedule in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeSchedule(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.schedule.create')) {
            return redirect()->route('maintenance.schedules')->with('error', 'You do not have permission to create maintenance schedules.');
        }

        // Validate the request
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'title' => 'required|string|max:255',
            'maintenance_type' => 'required|string|in:Preventive,Predictive,Inspection,Calibration',
            'description' => 'nullable|string',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'estimated_duration_hours' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'recurrence_type' => 'required|string|in:Once,Daily,Weekly,Monthly,Yearly,UsageBased',
            'recurrence_interval' => 'nullable|integer|min:1',
            'day_of_week' => 'nullable|string',
            'day_of_month' => 'nullable|integer|between:1,31',
            'month_of_year' => 'nullable|integer|between:1,12',
            'usage_threshold' => 'nullable|numeric|min:0',
            'usage_unit' => 'nullable|string|max:50',
            'last_service_usage_reading' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        // Begin transaction
        DB::beginTransaction();

        try {
            // Create the maintenance schedule
            $schedule = new MaintenanceSchedule();
            $schedule->asset_id = $request->asset_id;
            $schedule->title = $request->title;
            $schedule->maintenance_type = $request->maintenance_type;
            $schedule->description = $request->description;
            $schedule->assigned_to_user_id = $request->assigned_to_user_id;
            $schedule->estimated_duration_hours = $request->estimated_duration_hours;
            $schedule->start_date = $request->start_date;
            $schedule->recurrence_type = $request->recurrence_type;
            $schedule->recurrence_interval = $request->recurrence_interval;
            $schedule->day_of_week = $request->day_of_week;
            $schedule->day_of_month = $request->day_of_month;
            $schedule->month_of_year = $request->month_of_year;
            $schedule->usage_threshold = $request->usage_threshold;
            $schedule->usage_unit = $request->usage_unit;
            $schedule->last_service_usage_reading = $request->last_service_usage_reading;
            $schedule->is_active = $request->is_active ?? true;

            // Calculate next due date
            $schedule->next_due_date = $this->calculateNextDueDate($schedule);
            $schedule->save();

            DB::commit();

            return redirect()->route('maintenance.schedules.show', $schedule)
                ->with('success', 'Maintenance schedule created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating maintenance schedule: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified maintenance schedule.
     *
     * @param  \App\Models\MaintenanceSchedule  $maintenanceSchedule
     * @return \Illuminate\Http\Response
     */
    public function showSchedule(MaintenanceSchedule $maintenanceSchedule)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.schedule.view')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view maintenance schedules.');
        }

        // Load related data
        $maintenanceSchedule->load(['asset', 'assignedTo']);

        // Get maintenance logs related to this schedule
        $maintenanceLogs = MaintenanceLog::with(['performedBy'])
            ->where('maintenance_schedule_id', $maintenanceSchedule->id)
            ->orderBy('completion_datetime', 'desc')
            ->get();

        return view('maintenance.schedules.show', compact('maintenanceSchedule', 'maintenanceLogs'));
    }

    /**
     * Show the form for editing the specified maintenance schedule.
     *
     * @param  \App\Models\MaintenanceSchedule  $maintenanceSchedule
     * @return \Illuminate\Http\Response
     */
    public function editSchedule(MaintenanceSchedule $maintenanceSchedule)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.schedule.edit')) {
            return redirect()->route('maintenance.schedules.show', $maintenanceSchedule)->with('error', 'You do not have permission to edit maintenance schedules.');
        }

        $assets = Asset::where('status', '!=', 'retired')->orderBy('name')->get();
        $maintenanceTypes = ['Preventive', 'Predictive', 'Inspection', 'Calibration'];
        $users = User::orderBy('first_name')->get();
        $recurrenceTypes = [
            'Once' => 'One-time only',
            'Daily' => 'Daily',
            'Weekly' => 'Weekly',
            'Monthly' => 'Monthly',
            'Yearly' => 'Yearly',
            'UsageBased' => 'Based on usage'
        ];

        return view('maintenance.schedules.edit', compact(
            'maintenanceSchedule',
            'assets',
            'maintenanceTypes',
            'users',
            'recurrenceTypes'
        ));
    }

    /**
     * Update the specified maintenance schedule in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MaintenanceSchedule  $maintenanceSchedule
     * @return \Illuminate\Http\Response
     */
    public function updateSchedule(Request $request, MaintenanceSchedule $maintenanceSchedule)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.schedule.edit')) {
            return redirect()->route('maintenance.schedules.show', $maintenanceSchedule)->with('error', 'You do not have permission to edit maintenance schedules.');
        }

        // Validate the request
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'title' => 'required|string|max:255',
            'maintenance_type' => 'required|string|in:Preventive,Predictive,Inspection,Calibration',
            'description' => 'nullable|string',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'estimated_duration_hours' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'recurrence_type' => 'required|string|in:Once,Daily,Weekly,Monthly,Yearly,UsageBased',
            'recurrence_interval' => 'nullable|integer|min:1',
            'day_of_week' => 'nullable|string',
            'day_of_month' => 'nullable|integer|between:1,31',
            'month_of_year' => 'nullable|integer|between:1,12',
            'usage_threshold' => 'nullable|numeric|min:0',
            'usage_unit' => 'nullable|string|max:50',
            'last_service_usage_reading' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'next_due_date' => 'nullable|date',
        ]);

        // Begin transaction
        DB::beginTransaction();

        try {
            // Check if we need to recalculate next due date
            $recalculateNextDue = $maintenanceSchedule->recurrence_type !== $request->recurrence_type ||
                $maintenanceSchedule->recurrence_interval !== $request->recurrence_interval ||
                $maintenanceSchedule->day_of_week !== $request->day_of_week ||
                $maintenanceSchedule->day_of_month !== $request->day_of_month ||
                $maintenanceSchedule->month_of_year !== $request->month_of_year;

            // Update the maintenance schedule
            $maintenanceSchedule->asset_id = $request->asset_id;
            $maintenanceSchedule->title = $request->title;
            $maintenanceSchedule->maintenance_type = $request->maintenance_type;
            $maintenanceSchedule->description = $request->description;
            $maintenanceSchedule->assigned_to_user_id = $request->assigned_to_user_id;
            $maintenanceSchedule->estimated_duration_hours = $request->estimated_duration_hours;
            $maintenanceSchedule->start_date = $request->start_date;
            $maintenanceSchedule->recurrence_type = $request->recurrence_type;
            $maintenanceSchedule->recurrence_interval = $request->recurrence_interval;
            $maintenanceSchedule->day_of_week = $request->day_of_week;
            $maintenanceSchedule->day_of_month = $request->day_of_month;
            $maintenanceSchedule->month_of_year = $request->month_of_year;
            $maintenanceSchedule->usage_threshold = $request->usage_threshold;
            $maintenanceSchedule->usage_unit = $request->usage_unit;
            $maintenanceSchedule->last_service_usage_reading = $request->last_service_usage_reading;
            $maintenanceSchedule->is_active = $request->is_active ?? true;

            // If explicitly set, use the provided next_due_date
            if ($request->filled('next_due_date')) {
                $maintenanceSchedule->next_due_date = $request->next_due_date;
            } 
            // Otherwise recalculate if needed
            elseif ($recalculateNextDue) {
                $maintenanceSchedule->next_due_date = $this->calculateNextDueDate($maintenanceSchedule);
            }

            $maintenanceSchedule->save();

            DB::commit();

            return redirect()->route('maintenance.schedules.show', $maintenanceSchedule)
                ->with('success', 'Maintenance schedule updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating maintenance schedule: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified maintenance schedule from storage.
     *
     * @param  \App\Models\MaintenanceSchedule  $maintenanceSchedule
     * @return \Illuminate\Http\Response
     */
    public function destroySchedule(MaintenanceSchedule $maintenanceSchedule)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.schedule.delete')) {
            return redirect()->route('maintenance.schedules')->with('error', 'You do not have permission to delete maintenance schedules.');
        }

        // Check if there are maintenance logs linked to this schedule
        $logsCount = MaintenanceLog::where('maintenance_schedule_id', $maintenanceSchedule->id)->count();
        
        if ($logsCount > 0) {
            return redirect()->route('maintenance.schedules.show', $maintenanceSchedule)
                ->with('error', 'Cannot delete maintenance schedule that has linked maintenance logs.');
        }

        // Delete the maintenance schedule
        $maintenanceSchedule->delete();

        return redirect()->route('maintenance.schedules')
            ->with('success', 'Maintenance schedule deleted successfully.');
    }

    /**
     * Generate a work order for a maintenance schedule.
     *
     * @param  \App\Models\MaintenanceSchedule  $maintenanceSchedule
     * @return \Illuminate\Http\Response
     */
    public function generateWorkOrder(MaintenanceSchedule $maintenanceSchedule)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.work_order.create')) {
            return redirect()->route('maintenance.schedules.show', $maintenanceSchedule)->with('error', 'You do not have permission to generate work orders.');
        }

        // Load related data
        $maintenanceSchedule->load(['asset', 'assignedTo', 'asset.location', 'asset.category']);

        // Generate work order (for now, this just displays a page with the details)
        // In a real application, this might generate a PDF or send notifications
        return view('maintenance.work_orders.show', compact('maintenanceSchedule'));
    }

    /**
     * Complete a scheduled maintenance.
     *
     * @param  \App\Models\MaintenanceSchedule  $maintenanceSchedule
     * @return \Illuminate\Http\Response
     */
    public function completeScheduledMaintenance(MaintenanceSchedule $maintenanceSchedule)
    {
        // Check permission
        if (!auth()->user()->can('maintenance.log.create')) {
            return redirect()->route('maintenance.schedules.show', $maintenanceSchedule)->with('error', 'You do not have permission to complete maintenance.');
        }

        // Pre-fill the maintenance log form with schedule details
        return view('maintenance.logs.create', [
            'assets' => Asset::orderBy('name')->get(),
            'maintenanceTypes' => ['Preventive', 'Corrective', 'Predictive', 'Inspection', 'Calibration', 'Upgrade', 'Emergency'],
            'users' => User::orderBy('first_name')->get(),
            'spareParts' => SparePart::orderBy('name')->get(),
            'schedule' => $maintenanceSchedule,
            'asset' => $maintenanceSchedule->asset,
        ]);
    }

    /**
     * Calculate the next due date for a maintenance schedule.
     *
     * @param  \App\Models\MaintenanceSchedule  $schedule
     * @return \Carbon\Carbon
     */
    private function calculateNextDueDate($schedule)
    {
        $startDate = Carbon::parse($schedule->start_date);
        $now = Carbon::now();

        // For the first occurrence, use the start date
        if ($startDate->isFuture()) {
            return $startDate;
        }

        // For one-time schedules that haven't occurred yet
        if ($schedule->recurrence_type === 'Once') {
            return $startDate;
        }

        // For recurring schedules, calculate based on recurrence type
        switch ($schedule->recurrence_type) {
            case 'Daily':
                $interval = $schedule->recurrence_interval ?? 1;
                $nextDue = $now->copy()->addDays($interval);
                break;

            case 'Weekly':
                $interval = $schedule->recurrence_interval ?? 1;
                // If day of week is specified, find the next occurrence of that day
                if ($schedule->day_of_week) {
                    $dayOfWeek = $schedule->day_of_week;
                    $nextDue = $now->copy()->next($dayOfWeek);
                    // Add additional weeks based on interval
                    if ($interval > 1) {
                        $nextDue->addWeeks($interval - 1);
                    }
                } else {
                    $nextDue = $now->copy()->addWeeks($interval);
                }
                break;

            case 'Monthly':
                $interval = $schedule->recurrence_interval ?? 1;
                $dayOfMonth = $schedule->day_of_month ?? $startDate->day;
                
                // Start with current month
                $nextDue = $now->copy()->day(1)->addMonths($interval);
                
                // Adjust to the specified day, but cap at the last day of the month
                $daysInMonth = $nextDue->daysInMonth;
                $nextDue->day(min($dayOfMonth, $daysInMonth));
                break;

            case 'Yearly':
                $interval = $schedule->recurrence_interval ?? 1;
                $monthOfYear = $schedule->month_of_year ?? $startDate->month;
                $dayOfMonth = $schedule->day_of_month ?? $startDate->day;
                
                // Start with current year
                $nextDue = $now->copy()->month($monthOfYear)->day(1)->addYears($interval);
                
                // Adjust to the specified day, but cap at the last day of the month
                $daysInMonth = $nextDue->daysInMonth;
                $nextDue->day(min($dayOfMonth, $daysInMonth));
                break;

            case 'UsageBased':
                // For usage-based, we don't automatically calculate next due date
                // It will be updated when usage is recorded
                $nextDue = null;
                break;

            default:
                $nextDue = $now->copy()->addMonths(1);
                break;
        }

        // Ensure the next due date is in the future
        if ($nextDue && $nextDue->isPast()) {
            return $this->calculateNextDueDate(new MaintenanceSchedule([
                'start_date' => $nextDue->toDateTimeString(),
                'recurrence_type' => $schedule->recurrence_type,
                'recurrence_interval' => $schedule->recurrence_interval,
                'day_of_week' => $schedule->day_of_week,
                'day_of_month' => $schedule->day_of_month,
                'month_of_year' => $schedule->month_of_year,
            ]));
        }

        return $nextDue;
    }

    /**
     * Update a maintenance schedule after completion.
     *
     * @param  \App\Models\MaintenanceSchedule  $schedule
     * @param  string  $completionDate
     * @return void
     */
    private function updateMaintenanceSchedule($schedule, $completionDate)
    {
        // If it's a one-time schedule, mark it as inactive
        if ($schedule->recurrence_type === 'Once') {
            $schedule->is_active = false;
            $schedule->save();
            return;
        }

        // For recurring schedules, calculate the next due date
        $completionDateTime = Carbon::parse($completionDate);
        
        // For usage-based maintenance, we don't update the next due date here
        // It will be updated when asset usage is recorded
        if ($schedule->recurrence_type === 'UsageBased') {
            $schedule->last_service_usage_reading = $schedule->usage_threshold; // Reset the counter
            $schedule->save();
            return;
        }

        // For time-based schedules, calculate the next due date
        $schedule->start_date = $completionDateTime->toDateTimeString();
        $schedule->next_due_date = $this->calculateNextDueDate($schedule);
        $schedule->save();
    }

    /**
     * Update asset usage for usage-based maintenance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateAssetUsage(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('asset.usage.update')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to update asset usage.');
        }

        // Validate the request
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'usage_reading' => 'required|numeric|min:0',
            'usage_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // Begin transaction
        DB::beginTransaction();

        try {
            // Get the asset
            $asset = Asset::findOrFail($request->asset_id);

            // Record the usage
            $usage = DB::table('asset_usage_logs')->insert([
                'asset_id' => $asset->id,
                'reading' => $request->usage_reading,
                'reading_date' => $request->usage_date,
                'notes' => $request->notes,
                'recorded_by_user_id' => Auth::id(),
                'created_at' => Carbon::now(),
            ]);

            // Check if any usage-based maintenance schedules are due
            $usageSchedules = MaintenanceSchedule::where('asset_id', $asset->id)
                ->where('recurrence_type', 'UsageBased')
                ->where('is_active', true)
                ->get();

            foreach ($usageSchedules as $schedule) {
                // Skip if usage threshold or last reading is not set
                if (!$schedule->usage_threshold || !$schedule->last_service_usage_reading) {
                    continue;
                }

                // Calculate usage since last service
                $usageSinceLastService = $request->usage_reading - $schedule->last_service_usage_reading;

                // If usage exceeds the threshold, schedule is due
                if ($usageSinceLastService >= $schedule->usage_threshold) {
                    $schedule->next_due_date = Carbon::now();
                    $schedule->save();
                }
            }

            DB::commit();

            return redirect()->route('assets.show', $asset)
                ->with('success', 'Asset usage updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating asset usage: ' . $e->getMessage())
                ->withInput();
        }
    }
}