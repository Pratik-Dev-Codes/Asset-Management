<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class FileUploadController extends Controller
{
    use HasRoles;

    // Maximum file size in bytes (50MB)
    const MAX_FILE_SIZE = 50 * 1024 * 1024;

    // Allowed MIME types
    const ALLOWED_MIME_TYPES = [
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        // Archives
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'application/x-tar',
        'application/gzip',
    ];

    /**
     * Get all attachments for an asset
     *
     * @param  int  $assetId
     */
    public function index($assetId): JsonResponse
    {
        $asset = Asset::findOrFail($assetId);
        $attachments = $asset->attachments()
            ->with('user:id,name,email')
            ->latest()
            ->get()
            ->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'filename' => $attachment->filename,
                    'title' => $attachment->title,
                    'description' => $attachment->description,
                    'mime_type' => $attachment->mime_type,
                    'size' => (int) $attachment->size,
                    'url' => $attachment->url,
                    'formatted_size' => $attachment->formatted_size,
                    'created_at' => $attachment->created_at->format('Y-m-d H:i:s'),
                    'user' => [
                        'name' => $attachment->user->name,
                        'email' => $attachment->user->email,
                    ],
                ];
            });

        return response()->json($attachments);
    }

    /**
     * Store a new file attachment
     *
     * @param  int  $assetId
     */
    /**
     * Initiate a chunked upload
     *
     * @param  int  $assetId
     */
    public function initiateChunkedUpload(Request $request, $assetId): JsonResponse
    {
        $request->validate([
            'filename' => 'required|string|max:255',
            'file_size' => 'required|integer|max:'.self::MAX_FILE_SIZE,
            'mime_type' => 'required|string',
            'chunk_size' => 'required|integer',
            'total_chunks' => 'required|integer|min:1',
        ]);

        // Validate MIME type
        if (! in_array($request->mime_type, self::ALLOWED_MIME_TYPES)) {
            return response()->json([
                'success' => false,
                'message' => 'File type not allowed.',
            ], 400);
        }

        $asset = Asset::findOrFail($assetId);
        $userId = auth()->id();

        // Generate a unique upload ID
        $uploadId = md5($userId.'_'.$assetId.'_'.$request->filename.'_'.time());

        // Store upload metadata in cache for 24 hours
        Cache::put("upload.{$uploadId}", [
            'asset_id' => $asset->id,
            'user_id' => $userId,
            'filename' => $request->filename,
            'file_size' => $request->file_size,
            'mime_type' => $request->mime_type,
            'chunk_size' => $request->chunk_size,
            'total_chunks' => $request->total_chunks,
            'uploaded_chunks' => [],
            'created_at' => now(),
            'title' => $request->input('title', pathinfo($request->filename, PATHINFO_FILENAME)),
            'description' => $request->input('description', ''),
        ], now()->addDay());

        // Initialize progress tracking
        Redis::set("upload:{$uploadId}:progress", 0);

        return response()->json([
            'success' => true,
            'upload_id' => $uploadId,
            'chunk_size' => $request->chunk_size,
        ]);
    }

    /**
     * Upload a chunk of a file
     */
    public function uploadChunk(Request $request, string $uploadId): JsonResponse
    {
        $request->validate([
            'chunk_index' => 'required|integer|min:0',
            'chunk' => 'required|file',
        ]);

        $upload = Cache::get("upload.{$uploadId}");

        if (! $upload) {
            return response()->json([
                'success' => false,
                'message' => 'Upload session expired or invalid.',
            ], 404);
        }

        $chunkIndex = $request->chunk_index;
        $chunkFile = $request->file('chunk');

        // Validate chunk size (except possibly the last chunk)
        if ($chunkIndex < $upload['total_chunks'] - 1 && $chunkFile->getSize() != $upload['chunk_size']) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid chunk size.',
            ], 400);
        }

        // Store chunk in temporary storage
        $chunkPath = "chunks/{$uploadId}";
        $chunkFilename = "{$chunkIndex}";

        Storage::disk('local')->putFileAs($chunkPath, $chunkFile, $chunkFilename);

        // Update uploaded chunks
        $upload['uploaded_chunks'][] = $chunkIndex;
        $upload['uploaded_chunks'] = array_unique($upload['uploaded_chunks']);

        // Update progress
        $progress = (count($upload['uploaded_chunks']) / $upload['total_chunks']) * 100;
        Redis::set("upload:{$uploadId}:progress", $progress);

        // Update upload metadata
        Cache::put("upload.{$uploadId}", $upload, now()->addDay());

        return response()->json([
            'success' => true,
            'chunk_index' => $chunkIndex,
            'progress' => $progress,
        ]);
    }

    /**
     * Complete a chunked upload
     */
    public function completeChunkedUpload(Request $request, string $uploadId): JsonResponse
    {
        $upload = Cache::get("upload.{$uploadId}");

        if (! $upload) {
            return response()->json([
                'success' => false,
                'message' => 'Upload session expired or invalid.',
            ], 404);
        }

        // Verify all chunks are uploaded
        $expectedChunks = range(0, $upload['total_chunks'] - 1);
        $missingChunks = array_diff($expectedChunks, $upload['uploaded_chunks']);

        if (! empty($missingChunks)) {
            return response()->json([
                'success' => false,
                'message' => 'Missing chunks: '.implode(', ', $missingChunks),
            ], 400);
        }

        // Combine chunks
        $chunkPath = storage_path("app/chunks/{$uploadId}");
        $outputPath = storage_path("app/temp/{$uploadId}_".$upload['filename']);

        try {
            $output = fopen($outputPath, 'wb');

            for ($i = 0; $i < $upload['total_chunks']; $i++) {
                $chunkFile = "{$chunkPath}/{$i}";
                $chunk = fopen($chunkFile, 'rb');
                stream_copy_to_stream($chunk, $output);
                fclose($chunk);
            }

            fclose($output);

            // Verify file size
            if (filesize($outputPath) !== $upload['file_size']) {
                throw new \Exception('File size mismatch');
            }

            // Create a new uploaded file instance
            $file = new UploadedFile(
                $outputPath,
                $upload['filename'],
                $upload['mime_type'],
                null,
                true // Mark as test to prevent moving
            );

            // Store the file
            $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('attachments', $filename, 'public');

            // Create attachment record
            $attachment = new AssetAttachment([
                'asset_id' => $upload['asset_id'],
                'user_id' => $upload['user_id'],
                'filename' => $upload['filename'],
                'storage_path' => $path,
                'mime_type' => $upload['mime_type'],
                'size' => $upload['file_size'],
                'title' => $upload['title'],
                'description' => $upload['description'] ?? null,
            ]);

            $attachment->save();

            // Cleanup
            Storage::disk('local')->deleteDirectory("chunks/{$uploadId}");
            unlink($outputPath);
            Cache::forget("upload.{$uploadId}");
            Redis::del("upload:{$uploadId}:progress");

            return response()->json([
                'success' => true,
                'attachment' => $attachment,
                'url' => Storage::url($path),
            ]);

        } catch (\Exception $e) {
            Log::error('Chunked upload failed: '.$e->getMessage(), [
                'upload_id' => $uploadId,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to combine chunks: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get upload progress
     */
    public function getUploadProgress(string $uploadId): JsonResponse
    {
        $upload = Cache::get("upload.{$uploadId}");

        if (! $upload) {
            return response()->json([
                'success' => false,
                'message' => 'Upload session not found.',
            ], 404);
        }

        $progress = (int) Redis::get("upload:{$uploadId}:progress") ?: 0;

        return response()->json([
            'success' => true,
            'progress' => $progress,
            'uploaded_chunks' => $upload['uploaded_chunks'],
            'total_chunks' => $upload['total_chunks'],
        ]);
    }

    /**
     * Store a new file attachment (legacy single file upload)
     *
     * @param  int  $assetId
     */
    public function store(Request $request, $assetId): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:'.(self::MAX_FILE_SIZE / 1024), // Convert to KB
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $asset = Asset::findOrFail($assetId);
        $file = $request->file('file');

        // Validate MIME type
        if (! in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            return response()->json([
                'success' => false,
                'message' => 'File type not allowed.',
            ], 400);
        }

        try {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $filename = Str::random(40).'.'.$extension;
            $path = $file->storeAs('attachments', $filename, 'public');

            $attachment = new AssetAttachment([
                'asset_id' => $asset->id,
                'user_id' => auth()->id(),
                'filename' => $originalName,
                'storage_path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'title' => $request->input('title', $originalName),
                'description' => $request->input('description'),
            ]);

            $attachment->save();

            return response()->json([
                'success' => true,
                'attachment' => $attachment,
                'url' => Storage::url($path),
            ]);

        } catch (\Exception $e) {
            Log::error('File upload failed: '.$e->getMessage(), [
                'asset_id' => $assetId,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an attachment
     *
     * @param  int  $id
     */
    public function destroy($id): JsonResponse
    {
        $attachment = AssetAttachment::findOrFail($id);

        // Check permission - either the owner or an admin can delete
        $user = Auth::user();

        // Check if user is admin or super-admin using roles relationship
        $isAdmin = false;
        if ($user->roles) {
            $isAdmin = $user->roles->contains('name', 'admin') ||
                      $user->roles->contains('name', 'super-admin');
        }

        if ($attachment->user_id !== $user->id && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this file.',
            ], 403);
        }

        Storage::disk('public')->delete($attachment->storage_path);
        $attachment->delete();

        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully.',
        ]);
    }
}
