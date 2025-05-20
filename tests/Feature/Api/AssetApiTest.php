<?php

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssetApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $token;

    protected $category;

    protected $location;

    protected $department;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user and generate token
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Create test data
        $this->category = Category::factory()->create();
        $this->location = Location::factory()->create();
        $this->department = Department::factory()->create();

        Storage::fake('public');

        // Set the authentication token for subsequent requests
        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ]);
    }

    /** @test */
    public function it_can_list_assets()
    {
        // Create test assets
        $assets = Asset::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
        ]);

        // Make request
        $response = $this->getJson('/api/v1/assets');

        // Assert response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'asset_code',
                        'category' => ['id', 'name'],
                        'location' => ['id', 'name'],
                        'department' => ['id', 'name'],
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => [
                    'current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total',
                ],
            ]);

        // Verify we have at least 3 assets in the response
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    /** @test */
    public function it_can_create_an_asset()
    {
        $assetData = [
            'name' => 'Test Asset',
            'asset_code' => 'AST-'.rand(1000, 9999),
            'description' => 'Test description',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'status' => 'available',
            'purchase_date' => now()->format('Y-m-d'),
            'purchase_cost' => 1000.00,
            'warranty_months' => 12,
            'depreciation_years' => 3,
            'notes' => 'Test notes',
            'condition' => 'excellent',
            'serial_number' => 'SN'.$this->faker->uuid,
            'model' => 'Test Model X1',
            'manufacturer' => 'Test Manufacturer',
            'supplier' => 'Test Supplier',
            'order_number' => 'ORD'.$this->faker->randomNumber(6),
            'barcode' => 'BC'.$this->faker->randomNumber(8),
        ];

        $response = $this->postJson('/api/v1/assets', $assetData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'asset_code',
                    'description',
                    'status',
                    'category' => ['id', 'name'],
                    'location' => ['id', 'name'],
                    'department' => ['id', 'name'],
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('assets', [
            'name' => $assetData['name'],
            'asset_code' => $assetData['asset_code'],
            'category_id' => $assetData['category_id'],
            'location_id' => $assetData['location_id'],
            'department_id' => $assetData['department_id'],
            'status' => $assetData['status'],
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/assets', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name' => 'The name field is required.',
                'asset_code' => 'The asset code field is required.',
                'category_id' => 'The category id field is required.',
                'location_id' => 'The location id field is required.',
                'status' => 'The status field is required.',
                'purchase_date' => 'The purchase date field is required.',
                'purchase_cost' => 'The purchase cost field is required.',
            ]);
    }

    /** @test */
    public function it_can_retrieve_an_asset()
    {
        $asset = \App\Models\Asset::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'name' => 'Test Asset',
            'asset_code' => 'AST-'.rand(1000, 9999),
            'status' => 'available',
        ]);

        $response = $this->getJson("/api/v1/assets/{$asset->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'asset_code',
                    'status',
                    'category' => ['id', 'name'],
                    'location' => ['id', 'name'],
                    'department' => ['id', 'name'],
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $asset->id,
                    'name' => 'Test Asset',
                    'asset_code' => $asset->asset_code,
                    'status' => 'available',
                    'category' => ['id' => $this->category->id],
                    'location' => ['id' => $this->location->id],
                    'department' => ['id' => $this->department->id],
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_asset()
    {
        $response = $this->getJson('/api/v1/assets/99999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Resource not found',
            ]);
    }

    /** @test */
    public function it_can_update_an_asset()
    {
        $asset = \App\Models\Asset::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'name' => 'Original Asset Name',
            'asset_code' => 'AST-'.rand(1000, 9999),
            'status' => 'available',
            'condition' => 'excellent',
        ]);

        $updateData = [
            'name' => 'Updated Asset Name',
            'status' => 'in_maintenance',
            'notes' => 'Updated notes',
            'condition' => 'good',
        ];

        $response = $this->putJson("/api/v1/assets/{$asset->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'asset_code',
                    'status',
                    'notes',
                    'condition',
                    'category' => ['id', 'name'],
                    'location' => ['id', 'name'],
                    'department' => ['id', 'name'],
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $asset->id,
                    'name' => 'Updated Asset Name',
                    'status' => 'in_maintenance',
                    'notes' => 'Updated notes',
                    'condition' => 'good',
                ],
            ]);

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'name' => 'Updated Asset Name',
            'status' => 'in_maintenance',
            'notes' => 'Updated notes',
            'condition' => 'good',
        ]);
    }

    /** @test */
    public function it_validates_asset_code_uniqueness_on_update()
    {
        $asset1 = \App\Models\Asset::factory()->create([
            'asset_code' => 'UNIQUE001',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
        ]);

        $asset2 = \App\Models\Asset::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
        ]);

        $response = $this->putJson(
            "/api/v1/assets/{$asset2->id}",
            [
                'asset_code' => 'UNIQUE001',
                'name' => 'Test Asset',
                'category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'department_id' => $this->department->id,
                'status' => 'available',
                'purchase_date' => now()->format('Y-m-d'),
                'purchase_cost' => 1000.00,
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'asset_code' => 'The asset code has already been taken.',
            ]);
    }

    /** @test */
    public function it_can_delete_an_asset()
    {
        $asset = \App\Models\Asset::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'name' => 'Asset to Delete',
            'asset_code' => 'AST-'.rand(1000, 9999),
            'status' => 'available',
        ]);

        $response = $this->deleteJson("/api/v1/assets/{$asset->id}", [], $this->withAuthHeaders());

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Asset deleted successfully',
                'data' => [
                    'id' => $asset->id,
                    'name' => 'Asset to Delete',
                ],
            ]);

        $this->assertSoftDeleted('assets', [
            'id' => $asset->id,
            'name' => 'Asset to Delete',
        ]);
    }

    /** @test */
    public function it_can_search_assets()
    {
        $asset1 = \App\Models\Asset::factory()->create([
            'name' => 'Dell XPS 15 Laptop',
            'asset_code' => 'LAP-001',
            'serial_number' => 'SN12345',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'status' => 'available',
        ]);

        $asset2 = Asset::factory()->create([
            'name' => 'MacBook Pro',
            'asset_code' => 'LAP-002',
            'serial_number' => 'SN67890',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'status' => 'available',
        ]);

        // Search by name
        $response = $this->getJson('/api/v1/assets/search/Dell');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'name', 'asset_code', 'serial_number',
                        'status', 'category', 'location', 'department',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $asset1->id, 'name' => 'Dell XPS 15 Laptop']);

        // Search by asset code
        $response = $this->getJson('/api/v1/assets/search/LAP-002');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $asset2->id, 'asset_code' => 'LAP-002']);

        // Search by serial number
        $response = $this->getJson('/api/v1/assets/search/SN12345', $this->withAuthHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $asset1->id, 'serial_number' => 'SN12345']);
    }

    /** @test */
    public function it_can_export_assets()
    {
        // Create test assets
        \App\Models\Asset::factory(5)->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'status' => 'available',
        ]);

        // Test CSV export
        $response = $this->getJson('/api/v1/assets/export/csv');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="assets_'.date('Y-m-d').'.csv"');

        // Test Excel export
        $response = $this->getJson('/api/v1/assets/export/xlsx');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /** @test */
    public function it_requires_authentication()
    {
        // Clear the authentication headers
        $this->withHeaders(['Authorization' => '']);

        $asset = Asset::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
        ]);

        $endpoints = [
            ['method' => 'GET', 'url' => '/api/v1/assets'],
            ['method' => 'POST', 'url' => '/api/v1/assets'],
            ['method' => 'GET', 'url' => "/api/v1/assets/{$asset->id}"],
            ['method' => 'PUT', 'url' => "/api/v1/assets/{$asset->id}"],
            ['method' => 'DELETE', 'url' => "/api/v1/assets/{$asset->id}"],
            ['method' => 'GET', 'url' => '/api/v1/assets/export/csv'],
            ['method' => 'GET', 'url' => '/api/v1/assets/search/test'],
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->json($endpoint['method'], $endpoint['url']);

            $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Unauthenticated.',
                ]);
        }
    }

    /** @test */
    public function it_validates_invalid_asset_data()
    {
        $invalidData = [
            'name' => '', // Required
            'asset_code' => 'INVALID_CODE_'.str_repeat('A', 50), // Too long
            'category_id' => 9999, // Doesn't exist
            'location_id' => 'not_an_integer',
            'status' => 'invalid_status',
            'purchase_date' => 'not_a_date',
            'purchase_cost' => 'not_a_number',
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/assets', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name', 'asset_code', 'category_id', 'location_id',
                'status', 'purchase_date', 'purchase_cost',
            ]);
    }

    /** @test */
    public function it_handles_rate_limiting()
    {
        // This test assumes the rate limit is set to 60 requests per minute in config/api.php
        $headers = $this->getAuthHeaders();

        // Make 60 requests (the limit)
        for ($i = 0; $i < 60; $i++) {
            $response = $this->withHeaders($headers)
                ->getJson('/api/v1/assets');

            if ($i < 59) {
                $response->assertStatus(200);
            } else {
                // 60th request should be rate limited
                $response->assertStatus(429)
                    ->assertJson([
                        'message' => 'Too Many Attempts.',
                    ])
                    ->assertHeader('Retry-After');
            }
        }
    }

    /** @test */
    public function it_can_upload_an_asset_image()
    {
        $asset = Asset::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $file = UploadedFile::fake()->image('asset.jpg');

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson("/api/v1/assets/{$asset->id}/upload-image", [
                'image' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'image_url',
                ],
            ]);

        // Clean up test files
        if ($asset->fresh()->image_path) {
            Storage::disk('public')->delete($asset->fresh()->image_path);
        }
    }
}
