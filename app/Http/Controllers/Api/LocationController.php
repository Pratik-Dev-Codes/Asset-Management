<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Http\Resources\LocationCollection;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * @OA\Info(
 *     title="Asset Management API",
 *     version="1.0.0",
 *     description="API for managing asset locations",
 *
 *     @OA\Contact(
 *         email="support@assetmanagement.test",
 *         name="API Support"
 *     ),
 *
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth",
 *     in="header",
 *     name="Authorization"
 * )
 */

/**
 * @OA\Schema(
 *     schema="Location",
 *     required={"name", "code", "type"},
 *
 *     @OA\Property(property="id", type="integer", format="int64", example=1, readOnly=true),
 *     @OA\Property(property="name", type="string", example="Head Office", maxLength=255),
 *     @OA\Property(property="code", type="string", example="HO", maxLength=50),
 *     @OA\Property(property="type", type="string", enum={"facility","plant","office","warehouse"}, example="office"),
 *     @OA\Property(property="parent_id", type="integer", format="int64", nullable=true, example=null),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="coordinates", type="object",
 *         @OA\Property(property="latitude", type="number", format="float", example=40.7128, nullable=true),
 *         @OA\Property(property="longitude", type="number", format="float", example=-74.0060, nullable=true)
 *     ),
 *     @OA\Property(property="contact", type="object",
 *         @OA\Property(property="person", type="string", example="John Doe", maxLength=255, nullable=true),
 *         @OA\Property(property="email", type="string", format="email", example="john@example.com", maxLength=255, nullable=true),
 *         @OA\Property(property="phone", type="string", example="+1234567890", maxLength=20, nullable=true)
 *     ),
 *     @OA\Property(property="address", type="object",
 *         @OA\Property(property="line1", type="string", example="123 Main St", maxLength=255, nullable=true),
 *         @OA\Property(property="city", type="string", example="New York", maxLength=100, nullable=true),
 *         @OA\Property(property="state", type="string", example="NY", maxLength=100, nullable=true),
 *         @OA\Property(property="postal_code", type="string", example="10001", maxLength=20, nullable=true),
 *         @OA\Property(property="country", type="string", example="USA", maxLength=100, nullable=true),
 *         @OA\Property(property="formatted", type="string", example="123 Main St, New York, NY 10001, USA", nullable=true)
 *     ),
 *     @OA\Property(property="metadata", type="object",
 *         @OA\Property(property="notes", type="string", example="Main office with warehouse", nullable=true),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T12:00:00Z")
 *     ),
 *     @OA\Property(property="relationships", type="object",
 *         @OA\Property(property="parent", type="object", nullable=true,
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="Corporate"),
 *             @OA\Property(property="code", type="string", example="CORP"),
 *             @OA\Property(property="type", type="string", example="office")
 *         ),
 *         @OA\Property(property="children_count", type="integer", example=5, description="Number of child locations"),
 *         @OA\Property(property="assets_count", type="integer", example=10, description="Number of assets at this location")
 *     ),
 *     @OA\Property(property="links", type="object",
 *         @OA\Property(property="self", type="string", format="uri", example="https://api.assetmanagement.test/api/v1/locations/1"),
 *         @OA\Property(property="parent", type="string", format="uri", nullable=true, example="https://api.assetmanagement.test/api/v1/locations/1"),
 *         @OA\Property(property="children", type="string", format="uri", example="https://api.assetmanagement.test/api/v1/locations?parent_id=1"),
 *         @OA\Property(property="assets", type="string", format="uri", example="https://api.assetmanagement.test/api/v1/assets?location_id=1")
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="LocationCollection",
 *     type="object",
 *
 *     @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Location")),
 *     @OA\Property(property="meta", type="object",
 *         @OA\Property(property="total", type="integer", example=100),
 *         @OA\Property(property="per_page", type="integer", example=15),
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="last_page", type="integer", example=7),
 *         @OA\Property(property="from", type="integer", example=1),
 *         @OA\Property(property="to", type="integer", example=15)
 *     ),
 *     @OA\Property(property="links", type="object",
 *         @OA\Property(property="first", type="string", format="uri", example="https://api.assetmanagement.test/api/v1/locations?page=1"),
 *         @OA\Property(property="last", type="string", format="uri", example="https://api.assetmanagement.test/api/v1/locations?page=7"),
 *         @OA\Property(property="prev", type="string", format="uri", nullable=true, example="https://api.assetmanagement.test/api/v1/locations?page=1"),
 *         @OA\Property(property="next", type="string", format="uri", nullable=true, example="https://api.assetmanagement.test/api/v1/locations?page=3")
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="LocationRequest",
 *     required={"name", "code", "type"},
 *
 *     @OA\Property(property="name", type="string", example="Head Office"),
 *     @OA\Property(property="code", type="string", example="HO"),
 *     @OA\Property(property="type", type="string", enum={"facility","plant","office","warehouse"}, example="office"),
 *     @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
 *     @OA\Property(property="address", type="string", example="123 Main St", nullable=true),
 *     @OA\Property(property="city", type="string", example="New York", nullable=true),
 *     @OA\Property(property="state", type="string", example="NY", nullable=true),
 *     @OA\Property(property="postal_code", type="string", example="10001", nullable=true),
 *     @OA\Property(property="country", type="string", example="USA", nullable=true),
 *     @OA\Property(property="latitude", type="number", format="float", example=40.7128, nullable=true),
 *     @OA\Property(property="longitude", type="number", format="float", example=-74.0060, nullable=true),
 *     @OA\Property(property="contact_person", type="string", example="John Doe", nullable=true),
 *     @OA\Property(property="contact_email", type="string", format="email", example="john@example.com", nullable=true),
 *     @OA\Property(property="contact_phone", type="string", example="+1234567890", nullable=true),
 *     @OA\Property(property="notes", type="string", example="Main office with warehouse", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", example=true)
 * )
 */

/**
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *
 *     @OA\Property(property="message", type="string", example="Error message"),
 *     @OA\Property(property="code", type="integer", example=400),
 *     @OA\Property(property="errors", type="object", nullable=true,
 *
 *         @OA\AdditionalProperties(type="array", @OA\Items(type="string"))
 *     )
 * )
 */

/**
 * @OA\Tag(
 *     name="Locations",
 *     description="Operations about locations"
 * )
 */
class LocationController extends Controller
{
    /**
     * Number of items per page for pagination
     */
    protected int $perPage = 15;

    /**
     * Allowed relationships for eager loading
     */
    protected array $allowedIncludes = ['parent', 'children', 'assets'];

    /**
     * @OA\Get(
     *     path="/api/v1/locations",
     *     operationId="getLocations",
     *     tags={"Locations"},
     *     summary="Get list of locations",
     *     description="Returns a paginated list of locations with filtering and sorting options",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page (max 100)",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort field (prefix with - for descending)",
     *         required=false,
     *
     *         @OA\Schema(type="string", default="name")
     *     ),
     *
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Comma-separated relationships to include (e.g., parent,children,assets)",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[is_active]",
     *         in="query",
     *         description="Filter by active status",
     *         required=false,
     *
     *         @OA\Schema(type="boolean")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[type]",
     *         in="query",
     *         description="Filter by location type",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"facility","plant","office","warehouse"})
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[parent_id]",
     *         in="query",
     *         description="Filter by parent location ID",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term to filter by name or code",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Location")),
     *             @OA\Property(property="links", type="object", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", type="object", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthenticatedError")
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenError")
     *     )
     * )
     *
     * Display a listing of the locations.
     *
     * @return \App\Http\Resources\LocationCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Location::query();

            // Apply filters
            $this->applyFilters($query, $request);

            // Apply search
            $this->applySearch($query, $request->input('search'));

            // Handle sorting
            $this->applySorting($query, $request->input('sort', 'name'));

            // Eager load relationships
            $this->loadRelationships($query, $request->input('include'));

            // Paginate results
            $perPage = min($request->input('per_page', $this->perPage), 100);
            $locations = $query->paginate($perPage);

            return new LocationCollection($locations);

        } catch (Exception $e) {
            Log::error('Failed to fetch locations: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to retrieve locations',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/locations/{id}",
     *     operationId="getLocationById",
     *     tags={"Locations"},
     *     summary="Get a specific location",
     *     description="Returns a single location by ID with optional relationships",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the location to retrieve",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Comma-separated relationships to include (e.g., parent,children,assets)",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/Location")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthenticatedError")
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenError")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Location not found",
     *
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundError")
     *     )
     * )
     *
     * Display the specified location.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id, Request $request)
    {
        try {
            $query = Location::query();

            // Eager load relationships if specified
            $this->loadRelationships($query, $request->input('include'));

            // Find the location or fail with 404
            $location = $query->findOrFail($id);

            return response()->json([
                'data' => new LocationResource($location),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Location not found.',
                'error' => 'The requested location does not exist.',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Exception $e) {
            Log::error('Failed to fetch location: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to retrieve location',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Apply filters to the query
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function applyFilters($query, $request)
    {
        // Filter by status if provided
        if ($request->has('filter.is_active')) {
            $query->where('is_active', $request->boolean('filter.is_active'));
        }

        // Filter by type if provided
        if ($request->filled('filter.type')) {
            $query->where('type', $request->input('filter.type'));
        }

        // Filter by parent_id if provided, otherwise get root locations
        if ($request->filled('filter.parent_id')) {
            $query->where('parent_id', $request->input('filter.parent_id'));
        } elseif (! $request->has('filter.parent_id')) {
            $query->whereNull('parent_id');
        }
    }

    /**
     * Apply search to the query
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $searchTerm
     * @return void
     */
    protected function applySearch($query, $searchTerm)
    {
        if (empty($searchTerm)) {
            return;
        }

        $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('code', 'like', "%{$searchTerm}%");
        });
    }

    /**
     * Apply sorting to the query
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $sort
     * @return void
     */
    protected function applySorting($query, $sort)
    {
        $orderBy = ltrim($sort, '-');
        $direction = Str::startsWith($sort, '-') ? 'desc' : 'asc';

        $query->orderBy($orderBy, $direction);
    }

    /**
     * Eager load relationships
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $includes
     * @return void
     */
    protected function loadRelationships($query, $includes)
    {
        if (empty($includes)) {
            return;
        }

        $includes = array_intersect(
            explode(',', $includes),
            $this->allowedIncludes
        );

        if (! empty($includes)) {
            $query->with($includes);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/locations",
     *     operationId="createLocation",
     *     tags={"Locations"},
     *     summary="Create a new location",
     *     description="Creates a new location with the provided data",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Location data",
     *
     *         @OA\JsonContent(
     *             required={"name", "code", "type"},
     *
     *             @OA\Property(property="name", type="string", example="Head Office", maxLength=255),
     *             @OA\Property(property="code", type="string", example="HO", maxLength=50),
     *             @OA\Property(property="type", type="string", enum={"facility","plant","office","warehouse"}, example="office"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=null, description="ID of the parent location"),
     *             @OA\Property(property="address", type="string", nullable=true, example="123 Main St", maxLength=255),
     *             @OA\Property(property="city", type="string", nullable=true, example="New York", maxLength=100),
     *             @OA\Property(property="state", type="string", nullable=true, example="NY", maxLength=100),
     *             @OA\Property(property="postal_code", type="string", nullable=true, example="10001", maxLength=20),
     *             @OA\Property(property="country", type="string", nullable=true, example="USA", maxLength=100),
     *             @OA\Property(property="latitude", type="number", format="float", nullable=true, example=40.7128),
     *             @OA\Property(property="longitude", type="number", format="float", nullable=true, example=-74.0060),
     *             @OA\Property(property="contact_person", type="string", nullable=true, example="John Doe", maxLength=255),
     *             @OA\Property(property="contact_email", type="string", format="email", nullable=true, example="john@example.com", maxLength=255),
     *             @OA\Property(property="contact_phone", type="string", nullable=true, example="+1234567890", maxLength=20),
     *             @OA\Property(property="notes", type="string", nullable=true, example="Main office with warehouse"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Location created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/Location"),
     *             @OA\Property(property="message", type="string", example="Location created successfully.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthenticatedError")
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenError")
     *     ),
     *
     *     @OA\Response(
    /**
     * @OA\Put(
     *     path="/api/v1/locations/{id}",
     *     operationId="updateLocation",
     *     tags={"Locations"},
     *     summary="Update an existing location",
     *     description="Updates an existing location with the provided data",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the location to update",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Location data to update",
     *
     *         @OA\JsonContent(ref="#/components/schemas/LocationRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Location updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/Location"),
     *             @OA\Property(property="message", type="string", example="Location updated successfully.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Validation error",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthenticatedError")
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenError")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Location not found",
     *
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundError")
     *     )
     * )
     *
     * Update the specified location in storage.
     */
    public function update(Request $request, Location $location): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'code' => 'sometimes|required|string|max:50|unique:locations,code,'.$location->id,
                'type' => 'sometimes|required|string|in:facility,plant,office,warehouse',
                'parent_id' => [
                    'nullable',
                    'exists:locations,id',
                    function ($attribute, $value, $fail) use ($location) {
                        if ($value === $location->id) {
                            $fail('A location cannot be its own parent.');
                        }

                        // Check for circular reference
                        $childIds = $location->children()->pluck('id')->toArray();
                        if (in_array($value, $childIds)) {
                            $fail('Cannot set a child location as parent.');
                        }
                    },
                ],
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'contact_person' => 'nullable|string|max:255',
                'contact_email' => 'nullable|email|max:255',
                'contact_phone' => 'nullable|string|max:20',
                'notes' => 'nullable|string',
                'is_active' => 'sometimes|boolean',
            ]);

            // Start database transaction
            DB::beginTransaction();

            // Update the location
            $location->update($validated);

            // If parent_id was changed, move the node in the tree
            if (array_key_exists('parent_id', $validated) && $validated['parent_id'] != $location->getOriginal('parent_id')) {
                if ($validated['parent_id']) {
                    $parent = Location::findOrFail($validated['parent_id']);
                    $location->appendToNode($parent)->save();
                } else {
                    $location->saveAsRoot();
                }
            }

            DB::commit();

            // Reload the location with relationships
            $location->load('parent', 'children');

            return response()->json([
                'data' => new LocationResource($location),
                'message' => 'Location updated successfully.',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Parent location not found.',
                'error' => 'The specified parent location does not exist.',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update location: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to update location.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/locations/{id}",
     *     summary="Delete a location",
     *     description="Soft deletes the specified location if it has no associated assets or active children",
     *     operationId="deleteLocation",
     *     tags={"Locations"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the location to delete",
     *         required=true,
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\Parameter(
     *         name="force",
     *         in="query",
     *         description="Force delete even if location has children or assets (admin only)",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", default=false)
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="Location deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - cannot delete location with children or assets",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthenticatedError")
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Insufficient permissions",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenError")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Location not found",
     *
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundError")
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     *
     * Remove the specified location from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     *
     * @throws \Exception
     */
    /**
     * Remove the specified location from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     *
     * @throws \Exception
     */
    public function destroy($id, Request $request)
    {
        try {
            DB::beginTransaction();

            $location = Location::withCount(['children', 'assets'])->findOrFail($id);
            $force = $request->boolean('force', false);

            // Check if location has children or assets
            if ($force) {
                // Admin-only force delete
                if (! $request->user() || ! $request->user()->hasRole('admin')) {
                    return response()->json([
                        'message' => 'Insufficient permissions to force delete.',
                    ], 403);
                }

                // If force deleting, we need to handle children recursively
                $this->deleteLocationRecursively($location);
            } else {
                // Regular soft delete - check for children and assets first
                if ($location->children_count > 0) {
                    return response()->json([
                        'message' => 'Cannot delete location with child locations. Use force=true to override.',
                        'children_count' => $location->children_count,
                    ], 400);
                }

                if ($location->assets_count > 0) {
                    return response()->json([
                        'message' => 'Cannot delete location with associated assets. Reassign or delete assets first.',
                        'assets_count' => $location->assets_count,
                    ], 400);
                }

                // Perform the soft delete
                $location->delete();
            }

            // Log the deletion
            Log::info('Location deleted', [
                'location_id' => $location->id,
                'location_name' => $location->name,
                'deleted_by' => $request->user() ? $request->user()->id : null,
                'force' => $force,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return response()->noContent();

        } catch (\Exception $e) {
            DB::rollBack();

            // Log the error for debugging
            Log::error('Failed to delete location: '.$e->getMessage(), [
                'location_id' => $id,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to delete location. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Recursively delete a location and its children.
     */
    protected function deleteLocationRecursively(Location $location): void
    {
        // First delete all children recursively
        foreach ($location->children as $child) {
            $this->deleteLocationRecursively($child);
        }

        // Detach all assets from this location
        $location->assets()->update(['location_id' => null]);

        // Now delete the location itself
        $location->forceDelete();
    }

    /**
     * @OA\Get(
     *     path="/api/v1/locations/hierarchy",
     *     summary="Get location hierarchy",
     *     description="Returns a hierarchical tree of locations",
     *     operationId="getLocationHierarchy",
     *     tags={"Locations"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/LocationHierarchy")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthenticatedError")
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Insufficient permissions",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenError")
     *     )
     * )
     *
     * Get a hierarchical tree of locations.
     */
    public function hierarchy(): JsonResponse
    {
        $locations = Location::with('children')
            ->whereNull('parent_id')
            ->get();

        return response()->json([
            'data' => $this->formatHierarchy($locations),
        ]);
    }

    /**
     * Format location hierarchy recursively.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $locations
     */
    protected function formatHierarchy($locations): array
    {
        return $locations->map(function ($location) {
            return [
                'id' => $location->id,
                'name' => $location->name,
                'code' => $location->code,
                'is_active' => $location->is_active,
                'children' => $this->formatHierarchy($location->children),
            ];
        })->toArray();
    }
}
