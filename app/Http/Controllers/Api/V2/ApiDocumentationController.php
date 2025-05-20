<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="Asset Management System API",
 *     version="2.0.0",
 *     description="API documentation for the Asset Management System (v2.0)",
 *     @OA\Contact(
 *         email="support@example.com",
 *         name="API Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="/api/v2",
 *     description="API v2 Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for Authentication"
 * )
 * @OA\Tag(
 *     name="Assets",
 *     description="API Endpoints for Asset Management"
 * )
 * @OA\Tag(
 *     name="Reports",
 *     description="API Endpoints for Reports"
 * )
 * @OA\Tag(
 *     name="Documentation",
 *     description="API Documentation"
 * )
 */
class ApiDocumentationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v2/documentation",
     *     summary="Get API documentation",
     *     tags={"Documentation"},
     *     @OA\Response(
     *         response=200,
     *         description="API documentation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="API documentation is available at /api/v2/documentation.json"
     *             ),
     *             @OA\Property(
     *                 property="documentation",
     *                 type="string",
     *                 example="http://example.com/api/v2/documentation.json"
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        return response()->json([
            'message' => 'API documentation is available at /api/v2/documentation.json',
            'documentation' => url('/api/v2/documentation.json'),
            'endpoints' => [
                'auth' => [
                    'login' => url('/api/v2/auth/login'),
                    'refresh' => url('/api/v2/auth/refresh'),
                    'logout' => url('/api/v2/auth/logout'),
                    'me' => url('/api/v2/auth/me'),
                ],
                'assets' => [
                    'index' => url('/api/v2/assets'),
                    'store' => url('/api/v2/assets'),
                    'show' => url('/api/v2/assets/{id}'),
                ],
                'reports' => [
                    'index' => url('/api/v2/reports'),
                    'export' => url('/api/v2/reports'),
                    'status' => url('/api/v2/reports/{report}/status'),
                    'download' => url('/api/v2/reports/{report}/download'),
                ]
            ]
        ]);
    }
}
