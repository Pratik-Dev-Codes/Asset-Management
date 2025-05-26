<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * @OA\Tag(
 *     name="Assets",
 *     description="Operations about assets"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Schema(
 *     schema="AssetResource",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Dell XPS 15"),
 *     @OA\Property(property="description", type="string", example="High-performance laptop"),
 *     @OA\Property(property="serial_number", type="string", example="SN123456"),
 *     @OA\Property(property="status", type="string", example="available"),
 *     @OA\Property(property="purchase_date", type="string", format="date", example="2023-01-15"),
 *     @OA\Property(property="purchase_cost", type="number", format="float", example=1299.99),
 *     @OA\Property(
 *         property="category",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Laptops")
 *     ),
 *     @OA\Property(
 *         property="location",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Head Office")
 *     ),
 *     @OA\Property(
 *         property="assigned_to",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="John Doe")
 *     )
 * )
 */
class AssetController extends BaseApiController
{
    /**
     * The asset service instance.
     */
    protected AssetService $assetService;

    /**
     * Create a new controller instance.
     */
    public function __construct(AssetService $assetService)
    {
        $this->assetService = $assetService;

        // Apply middleware
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *     path="/api/assets",
     *     summary="Get list of assets",
     *     description="Returns a paginated list of assets",
     *     operationId="getAssets",
     *     tags={"Assets"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term to filter assets by name, description, or serial number",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by asset status",
     *         required=false,
     *
     *         @OA\Schema(
     *             type="string",
     *             enum={"available", "assigned", "maintenance", "retired"}
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category ID",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="location_id",
     *         in="query",
     *         description="Filter by location ID",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page (default: 15)",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Assets retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/AssetResource")
     *             ),
     *
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="to", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="You do not have permission to view assets.")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $assets = $this->assetService->getAllAssets($request->all(), $perPage);

        return $this->success(
            AssetResource::collection($assets),
            'Assets retrieved successfully'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/assets",
     *     summary="Create a new asset",
     *     description="Store a newly created asset in storage",
     *     operationId="createAsset",
     *     tags={"Assets"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Asset data",
     *
     *         @OA\JsonContent(
     *             required={"name", "serial_number", "purchase_date", "purchase_cost", "status", "category_id", "location_id"},
     *
     *             @OA\Property(property="name", type="string", example="Dell XPS 15", description="Name of the asset"),
     *             @OA\Property(property="description", type="string", example="High-performance laptop with 16GB RAM", description="Description of the asset"),
     *             @OA\Property(property="serial_number", type="string", example="SN123456789", description="Unique serial number of the asset"),
     *             @OA\Property(property="purchase_date", type="string", format="date", example="2023-01-15", description="Date when the asset was purchased"),
     *             @OA\Property(property="purchase_cost", type="number", format="float", example=1299.99, description="Purchase cost of the asset"),
     *             @OA\Property(property="status", type="string", enum={"available", "assigned", "maintenance", "retired"}, example="available", description="Current status of the asset"),
     *             @OA\Property(property="category_id", type="integer", example=1, description="ID of the category this asset belongs to"),
     *             @OA\Property(property="location_id", type="integer", example=1, description="ID of the location where the asset is stored")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Asset created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Asset created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/AssetResource"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="field_name",
     *                     type="array",
     *
     *                     @OA\Items(type="string")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="You do not have permission to create assets.")
     *         )
     *     )
     * )
     *
     * @throws ValidationException
     * @throws \Exception
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'serial_number' => 'required|string|unique:assets,serial_number',
                'purchase_date' => 'required|date',
                'purchase_cost' => 'required|numeric|min:0',
                'status' => 'required|in:available,assigned,maintenance,retired',
                'category_id' => 'required|exists:categories,id',
                'location_id' => 'required|exists:locations,id',
            ]);

            $asset = $this->assetService->createAsset($validated);

            return $this->success(
                new AssetResource($asset->load(['category', 'location', 'department', 'assignedTo'])),
                'Asset created successfully',
                Response::HTTP_CREATED
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to create asset: '.$e->getMessage());

            return $this->error('Failed to create asset', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Asset $asset)
    {
        return Cache::remember('assets.'.$asset->id, 60, function () use ($asset) {
            return $asset->load(['category', 'location', 'department']);
        });
    }

    /**
     * @OA\Delete(
     *     path="/api/assets/{asset}",
     *     summary="Delete an asset",
     *     description="Delete the specified asset",
     *     operationId="deleteAsset",
     *     tags={"Assets"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="asset",
     *         in="path",
     *         description="ID of the asset to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Asset deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Asset deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Asset not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Asset not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to delete assets.")
     *         )
     *     )
     * )
     */
    public function destroy(Asset $asset)
    {
        try {
            $this->authorize('delete', $asset);
            
            $asset->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Asset deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete asset: '.$e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete asset'
            ], 500);
        }
    }
}
