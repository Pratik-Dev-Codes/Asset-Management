<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\User;
use App\Models\Document;
use App\Services\AssetService;
use App\Notifications\NewAssetNotification;
use App\Notifications\BulkOperationNotification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Intervention\Image\Facades\Image;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Jobs\ProcessBulkAssetUpdate;
use App\Jobs\ProcessBulkAssetDelete;
use App\Jobs\ProcessBulkAssetStatusChange;
use App\Http\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    /**
     * The asset service instance.
     *
     * @var AssetService
     */
    protected $assetService;

    // Allowed file types and sizes
    private const ALLOWED_IMAGE_TYPES = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
    private const ALLOWED_DOCUMENT_TYPES = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];
    private const MAX_FILE_SIZE = 5120; // 5MB in KB
    
    // Upload paths
    private const TEMP_UPLOAD_PATH = 'public/temp';
    private const ASSET_UPLOAD_PATH = 'public/assets';
    private const DOCUMENT_UPLOAD_PATH = 'public/documents';
    
    use HasPagination;

    /**
     * Create a new controller instance.
     *
     * @param AssetService $assetService
     * @return void
     */
    public function __construct(AssetService $assetService)
    {
        $this->assetService = $assetService;
        
        // Apply middleware
        $this->middleware('auth');
        $this->middleware('permission:view-assets', ['only' => ['index', 'show']]);
        $this->middleware('permission:create-assets', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit-assets', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete-assets', ['only' => ['destroy']]);
    }
    
    /**
     * Display the assets landing page.
     *
     * @return \Illuminate\Http\Response
     */
    public function landing()
    {
        return view('assets.landing');
    }

    /**
     * Display a listing of the assets with advanced search and filtering.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Get pagination settings
        $perPage = $this->getPaginationLimit($request->input('per_page', 15));
        
        // Prepare filters
        $filters = $request->only([
            'search', 'category_id', 'status', 'location_id', 'department_id',
            'purchase_date_from', 'purchase_date_to', 'sort_by', 'order'
        ]);
        
        // Get assets using the service
        $assets = $this->assetService->getAllAssets($filters, $perPage);
        
        // Get filter options for the view
        $categories = AssetCategory::orderBy('name')->get(['id', 'name']);
        $locations = Location::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get(['id', 'name']);
        
        // Prepare status options
        $statusOptions = [
            'available' => 'Available',
            'in_use' => 'In Use',
            'maintenance' => 'Under Maintenance',
            'retired' => 'Retired',
            'disposed' => 'Disposed',
            'lost' => 'Lost'
        ];

        // For API requests, return JSON
        if ($request->wantsJson()) {
            return response()->json([
                'data' => $assets->items(),
                'meta' => [
                    'current_page' => $assets->currentPage(),
                    'from' => $assets->firstItem(),
                    'last_page' => $assets->lastPage(),
                    'per_page' => $assets->perPage(),
                    'to' => $assets->lastItem(),
                    'total' => $assets->total(),
                ],
                'filters' => [
                    'categories' => $categories,
                    'locations' => $locations,
                    'departments' => $departments,
                    'statuses' => $statusOptions
                ]
            ]);
        }

        // For web requests, return view with data
        return view('assets.index', [
            'assets' => $assets,
            'categories' => $categories,
            'locations' => $locations,
            'departments' => $departments,
            'filters' => array_merge($filters, [
                'statuses' => $statusOptions
            ])
        ]);
    }

    /**
     * Show the form for creating a new asset.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Check permission
        if (Gate::denies('create-assets')) {
            return redirect()->route('assets.index')
                ->with('error', 'You do not have permission to create assets.');
        }

        $categories = AssetCategory::all();
        $locations = Location::all();
        $departments = Department::all();
        $users = User::all();
        $statuses = [
            'available' => 'Available',
            'in_use' => 'In Use',
            'maintenance' => 'Under Maintenance',
            'retired' => 'Retired',
            'disposed' => 'Disposed',
            'lost' => 'Lost'
        ];

        return view('assets.create', compact(
            'categories', 
            'locations', 
            'departments', 
            'users', 
            'statuses'
        ));
    }

    /**
     * Store a newly created asset in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check permission
        if (Gate::denies('create-assets')) {
            return redirect()->route('assets.index')
                ->with('error', 'You do not have permission to create assets.');
        }

        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'asset_code' => 'required|string|unique:assets,asset_code',
            'category_id' => 'required|exists:asset_categories,id',
            'status' => 'required|in:available,in_use,maintenance,retired,disposed,lost',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:0',
            'location_id' => 'required|exists:locations,id',
            'department_id' => 'nullable|exists:departments,id',
            'assigned_to' => 'nullable|exists:users,id',
            'serial_number' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'warranty_months' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        try {
            // Handle file upload
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store(self::ASSET_UPLOAD_PATH);
                $validated['image_path'] = $path;
            }

            // Create the asset using the service
            $asset = $this->assetService->createAsset($validated);

            return redirect()->route('assets.show', $asset->id)
                ->with('success', 'Asset created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating asset: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'Failed to create asset. Please try again.');
        }
    }

    /**
     * Display the specified asset.
     *
     * @param  \App\Models\Asset  $asset
     * @return \Illuminate\Http\Response
     */
    public function show(Asset $asset)
    {
        // Check permission
        if (Gate::denies('view-assets')) {
            return redirect()->route('assets.index')
                ->with('error', 'You do not have permission to view assets.');
        }

        // Eager load relationships
        $asset->load(['category', 'location', 'department', 'assignedTo']);
        
        return view('assets.show', compact('asset'));
    }

    /**
     * Show the form for editing the specified asset.
     *
     * @param  \App\Models\Asset  $asset
     * @return \Illuminate\Http\Response
     */
    public function edit(Asset $asset)
    {
        // Check permission
        if (Gate::denies('edit-assets')) {
            return redirect()->route('assets.index')
                ->with('error', 'You do not have permission to edit assets.');
        }

        $categories = AssetCategory::all();
        $locations = Location::all();
        $departments = Department::all();
        $users = User::all();
        $statuses = [
            'available' => 'Available',
            'in_use' => 'In Use',
            'maintenance' => 'Under Maintenance',
            'retired' => 'Retired',
            'disposed' => 'Disposed',
            'lost' => 'Lost'
        ];

        return view('assets.edit', compact(
            'asset',
            'categories', 
            'locations', 
            'departments', 
            'users', 
            'statuses'
        ));
    }

    /**
     * Update the specified asset in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Asset  $asset
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Asset $asset)
    {
        // Check permission
        if (Gate::denies('edit-assets')) {
            return redirect()->route('assets.index')
                ->with('error', 'You do not have permission to edit assets.');
        }

        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'asset_code' => 'required|string|unique:assets,asset_code,' . $asset->id,
            'category_id' => 'required|exists:asset_categories,id',
            'status' => 'required|in:available,in_use,maintenance,retired,disposed,lost',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:0',
            'location_id' => 'required|exists:locations,id',
            'department_id' => 'nullable|exists:departments,id',
            'assigned_to' => 'nullable|exists:users,id',
            'serial_number' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'warranty_months' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        try {
            // Handle file upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($asset->image_path) {
                    Storage::delete($asset->image_path);
                }
                $path = $request->file('image')->store(self::ASSET_UPLOAD_PATH);
                $validated['image_path'] = $path;
            }

            // Update the asset using the service
            $this->assetService->updateAsset($asset, $validated);

            return redirect()->route('assets.show', $asset->id)
                ->with('success', 'Asset updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating asset: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'Failed to update asset. Please try again.');
        }
    }

    /**
     * Remove the specified asset from storage.
     *
     * @param  \App\Models\Asset  $asset
     * @return \Illuminate\Http\Response
     */
    public function destroy(Asset $asset)
    {
        // Check permission
        if (Gate::denies('delete-assets')) {
            return redirect()->route('assets.index')
                ->with('error', 'You do not have permission to delete assets.');
        }

        try {
            // Delete the asset using the service
            $this->assetService->deleteAsset($asset);

            return redirect()->route('assets.index')
                ->with('success', 'Asset deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting asset: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete asset. Please try again.');
        }
    }

    /**
     * Generate QR code for the asset.
     *
     * @param  \App\Models\Asset  $asset
     * @return \Illuminate\Http\Response
     */
    public function generateQrCode(Asset $asset)
    {
        // Generate QR code with asset details
        $qrCode = QrCode::format('png')
            ->size(200)
            ->generate(route('assets.show', $asset->id));

        return response($qrCode)->header('Content-Type', 'image/png');
    }

    /**
     * Print asset details.
     *
     * @param  \App\Models\Asset  $asset
     * @return \Illuminate\Http\Response
     */
    public function printDetails(Asset $asset)
    {
        $asset->load(['category', 'location', 'department', 'assignedTo']);
        return view('assets.print', compact('asset'));
    }

    /**
     * Export assets to CSV/Excel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        return Excel::download(new \App\Exports\AssetsExport, 'assets-' . date('Y-m-d') . '.xlsx');
    }
    
    /**
     * Remove the image from an asset.
     *
     * @param  \App\Models\Asset  $asset
     * @return \Illuminate\Http\Response
     */
    public function removeImage(Asset $asset)
    {
        try {
            if ($asset->image_path) {
                Storage::delete($asset->image_path);
                $asset->update(['image_path' => null]);
                return back()->with('success', 'Image removed successfully.');
            }
            return back()->with('error', 'No image found for this asset.');
        } catch (\Exception $e) {
            Log::error('Error removing image: ' . $e->getMessage());
            return back()->with('error', 'Failed to remove image. Please try again.');
        }
    }

    /**
     * Bulk update assets.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'asset_ids' => 'required|array',
            'asset_ids.*' => 'exists:assets,id',
            'field' => 'required|string|in:status,location_id,department_id,assigned_to',
            'value' => 'required',
        ]);

        $assetIds = $request->input('asset_ids');
        $field = $request->input('field');
        $value = $request->input('value');
        $user = $request->user();

        // Dispatch the bulk update job
        $batch = Bus::batch([
            new ProcessBulkAssetUpdate($assetIds, $field, $value, $user)
        ])->then(function () use ($user) {
            // All jobs completed successfully
            $user->notify(new BulkOperationNotification([
                'title' => 'Bulk Update Completed',
                'message' => 'The bulk update operation has been completed successfully.',
                'type' => 'success'
            ]));
            
            // Clear the cache
            Cache::tags(['assets'])->flush();
        })->catch(function (\Throwable $e) use ($user) {
            // Handle job failure
            Log::error('Bulk update failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            
            $user->notify(new BulkOperationNotification([
                'title' => 'Bulk Update Failed',
                'message' => 'An error occurred during the bulk update operation. Please try again.',
                'type' => 'error'
            ]));
        })->dispatch();

        return response()->json([
            'message' => 'Bulk update has been queued.',
            'batch_id' => $batch->id,
        ]);
    }

    /**
     * Bulk delete assets.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'asset_ids' => 'required|array',
            'asset_ids.*' => 'exists:assets,id',
        ]);

        $assetIds = $request->input('asset_ids');
        $user = $request->user();

        // Dispatch the bulk delete job
        $batch = Bus::batch([
            new ProcessBulkAssetDelete($assetIds, $user)
        ])->then(function () use ($user) {
            // All jobs completed successfully
            $user->notify(new BulkOperationNotification([
                'title' => 'Bulk Delete Completed',
                'message' => 'The bulk delete operation has been completed successfully.',
                'type' => 'success'
            ]));
            
            // Clear the cache
            Cache::tags(['assets'])->flush();
        })->catch(function (\Throwable $e) use ($user) {
            // Handle job failure
            Log::error('Bulk delete failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            
            $user->notify(new BulkOperationNotification([
                'title' => 'Bulk Delete Failed',
                'message' => 'An error occurred during the bulk delete operation. Please try again.',
                'type' => 'error'
            ]));
        })->dispatch();

        return response()->json([
            'message' => 'Bulk delete has been queued.',
            'batch_id' => $batch->id,
        ]);
    }

    /**
     * Bulk update asset status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkStatusUpdate(Request $request)
    {
        $request->validate([
            'asset_ids' => 'required|array',
            'asset_ids.*' => 'exists:assets,id',
            'status' => 'required|string|in:available,in_use,under_maintenance,retired,disposed',
            'notes' => 'nullable|string',
        ]);

        $assetIds = $request->input('asset_ids');
        $status = $request->input('status');
        $notes = $request->input('notes');
        $user = $request->user();

        // Dispatch the bulk status update job
        $batch = Bus::batch([
            new ProcessBulkAssetStatusChange($assetIds, $status, $notes, $user)
        ])->then(function () use ($user) {
            // All jobs completed successfully
            $user->notify(new BulkOperationNotification([
                'title' => 'Status Update Completed',
                'message' => 'The bulk status update operation has been completed successfully.',
                'type' => 'success'
            ]));
            
            // Clear the cache
            Cache::tags(['assets'])->flush();
        })->catch(function (\Throwable $e) use ($user) {
            // Handle job failure
            Log::error('Bulk status update failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            
            $user->notify(new BulkOperationNotification([
                'title' => 'Status Update Failed',
                'message' => 'An error occurred during the bulk status update operation. Please try again.',
                'type' => 'error'
            ]));
        })->dispatch();

        return response()->json([
            'message' => 'Bulk status update has been queued.',
            'batch_id' => $batch->id,
        ]);
    }

    /**
     * Get the status of a batch operation.
     *
     * @param  string  $batchId
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchStatus($batchId)
    {
        $batch = Bus::findBatch($batchId);

        if (!$batch) {
            return response()->json([
                'message' => 'Batch not found',
            ], 404);
        }

        return response()->json([
            'id' => $batch->id,
            'total_jobs' => $batch->totalJobs,
            'pending_jobs' => $batch->pendingJobs,
            'failed_jobs' => $batch->failedJobs,
            'processed_jobs' => $batch->processedJobs(),
            'progress' => $batch->progress(),
            'finished' => $batch->finished(),
            'cancelled' => $batch->cancelled(),
            'created_at' => $batch->createdAt,
            'cancelled_at' => $batch->cancelledAt,
            'finished_at' => $batch->finishedAt,
        ]);
    }

    /**
     * Scan a file for viruses using the system's antivirus software.
     * This is a placeholder that should be implemented based on your antivirus solution.
     *
     * @param string $filePath Path to the file to scan
     * @return bool True if the file is clean, false if infected
     */
    protected function scanFileForViruses($filePath)
    {
        // This is a placeholder implementation
        // You should replace this with your actual virus scanning logic
        
        // For example, if you're using ClamAV:
        // $output = shell_exec("clamscan --no-summary --infected " . escapeshellarg($filePath));
        // return strpos($output, 'Infected files: 0') !== false;
        
        // For now, we'll just return true to allow the file
        return true;
    }

    /**
     * Handle file upload via AJAX
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:5120', // 5MB max
                'type' => 'required|in:image,document',
            ]);

            $file = $request->file('file');
            $type = $request->input('type');

            // Validate file type
            $extension = strtolower($file->getClientOriginalExtension());
            
            if ($type === 'image' && !in_array($extension, self::ALLOWED_IMAGE_TYPES)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image file type. Allowed types: ' . implode(', ', self::ALLOWED_IMAGE_TYPES),
                ], 400);
            }
            
            if ($type === 'document' && !in_array($extension, self::ALLOWED_DOCUMENT_TYPES)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid document file type. Allowed types: ' . implode(', ', self::ALLOWED_DOCUMENT_TYPES),
                ], 400);
            }

            // Generate a unique filename
            $filename = uniqid() . '_' . preg_replace('/[^A-Za-z0-9\-\.]/', '_', $file->getClientOriginalName());
            
            // Store the file in the appropriate directory
            $path = $file->storeAs('temp', $filename);
            
            // Scan for viruses
            if (!$this->scanFileForViruses(storage_path('app/' . $path))) {
                Storage::delete($path);
                return response()->json([
                    'success' => false,
                    'message' => 'File failed virus scan and was deleted.',
                ], 400);
            }

            // Create a thumbnail for images
            $thumbnailPath = null;
            if ($type === 'image') {
                $thumbnailPath = $this->createThumbnail(storage_path('app/' . $path));
            }

            return response()->json([
                'success' => true,
                'path' => $path,
                'filename' => $file->getClientOriginalName(),
                'thumbnail' => $thumbnailPath ? basename($thumbnailPath) : null,
            ]);

        } catch (\Exception $e) {
            Log::error('File upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an uploaded file
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteUpload(Request $request)
    {
        try {
            $request->validate([
                'path' => 'required|string',
            ]);

            $path = $request->input('path');
            
            // Only allow deleting files from the temp directory
            if (!Storage::exists($path) || !str_starts_with($path, 'temp/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found or invalid path.',
                ], 404);
            }

            // Delete the file
            Storage::delete($path);

            // Also delete the thumbnail if it exists
            $thumbnailPath = 'temp/thumbnails/' . basename($path);
            if (Storage::exists($thumbnailPath)) {
                Storage::delete($thumbnailPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('File deletion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'File deletion failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a thumbnail for the uploaded image
     *
     * @param string $path Path to the original image
     * @param int $width Thumbnail width
     * @param int $height Thumbnail height
     * @return string|null Path to the thumbnail or null if creation failed
     */
    protected function createThumbnail($path, $width = 200, $height = 200)
    {
        try {
            $filename = basename($path);
            $thumbnailDir = dirname($path) . '/thumbnails';
            $thumbnailPath = $thumbnailDir . '/' . $filename;
            
            // Create thumbnails directory if it doesn't exist
            if (!file_exists($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }
            
            // Create and save the thumbnail
            $img = Image::make($path);
            $img->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            $img->save($thumbnailPath);
            
            return $thumbnailPath;
            
        } catch (\Exception $e) {
            Log::error('Thumbnail creation failed: ' . $e->getMessage());
            return null;
        }
    }
}
