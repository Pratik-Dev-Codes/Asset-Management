<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ApiDocumentationController extends Controller
{
    /**
     * Display API documentation.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Asset Management API Documentation',
            'version' => '1.0.0',
            'endpoints' => [
                'authentication' => [
                    'POST /api/v1/login' => 'Authenticate user and get access token',
                    'POST /api/v1/register' => 'Register a new user',
                    'POST /api/v1/password/email' => 'Request password reset link',
                    'POST /api/v1/password/reset' => 'Reset password',
                    'GET /api/v1/user' => 'Get authenticated user (requires auth)',
                ],
                'assets' => [
                    'GET /api/v1/assets' => 'List all assets (paginated)',
                    'POST /api/v1/assets' => 'Create a new asset',
                    'GET /api/v1/assets/{id}' => 'Get a specific asset',
                    'PUT /api/v1/assets/{id}' => 'Update an asset',
                    'DELETE /api/v1/assets/{id}' => 'Delete an asset',
                    'POST /api/v1/assets/{id}/upload-image' => 'Upload an image for an asset',
                    'GET /api/v1/assets/export/{type?}' => 'Export assets (csv, xlsx, pdf)',
                    'POST /api/v1/assets/import' => 'Import assets from file',
                    'GET /api/v1/assets/search/{query}' => 'Search assets',
                ],
                'status' => [
                    'GET /api/v1/status' => 'Check API status',
                ],
            ],
            'authentication' => [
                'type' => 'Bearer Token',
                'description' => 'Include the token in the Authorization header for protected routes',
                'example' => 'Authorization: Bearer your_token_here',
            ],
            'pagination' => [
                'per_page' => 'Number of items per page (default: 15)',
                'page' => 'Page number',
            ],
            'rate_limiting' => [
                'max_attempts' => '60 requests per minute',
                'headers' => [
                    'X-RateLimit-Limit' => 'The maximum number of requests allowed',
                    'X-RateLimit-Remaining' => 'The number of requests remaining',
                    'Retry-After' => 'The number of seconds to wait before making another request (when rate limited)',
                ],
            ],
        ]);
    }
}
