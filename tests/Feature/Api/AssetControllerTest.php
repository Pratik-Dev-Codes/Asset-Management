<?php

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and generate a token
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Create test data
        $this->category = AssetCategory::factory()->create();
        $this->location = Location::factory()->create();

        // Mock storage
        Storage::fake('public');
    }

    protected function getHeaders()
    {
        return [
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ];
    }

    /** @test */
    public function it_can_list_assets()
    {
        $assets = Asset::factory()->count(3)->create();

        $response = $this->getJson('/api/assets', $this->getHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'asset_code',
                        'name',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    /** @test */
    public function it_can_create_an_asset()
    {
        $data = [
            'asset_code' => 'AST-'.rand(1000, 9999),
            'name' => 'Test Asset',
            'description' => 'Test Description',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'status' => 'operational',
            'purchase_date' => now()->format('Y-m-d'),
            'purchase_cost' => 1000.50,
        ];

        $response = $this->postJson('/api/assets', $data, $this->getHeaders());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'asset_code',
                    'name',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('assets', [
            'asset_code' => $data['asset_code'],
            'name' => $data['name'],
            'status' => $data['status'],
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/assets', [], $this->getHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'asset_code',
                'name',
                'category_id',
                'location_id',
                'status',
            ]);
    }

    /** @test */
    public function it_can_upload_asset_image()
    {
        $asset = Asset::factory()->create();

        $file = UploadedFile::fake()->image('asset.jpg');

        $response = $this->postJson("/api/assets/{$asset->id}/upload-image", [
            'image' => $file,
        ], $this->getHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'image_url',
                    'thumbnail_url',
                ],
            ]);

        // Assert the file was stored
        Storage::disk('public')->assertExists("assets/images/{$file->hashName()}");
        Storage::disk('public')->assertExists('assets/thumbnails/'.pathinfo($file->hashName(), PATHINFO_FILENAME).'.jpg');
    }

    /** @test */
    public function it_can_show_an_asset()
    {
        $asset = Asset::factory()->create();

        $response = $this->getJson("/api/assets/{$asset->id}", $this->getHeaders());

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $asset->id,
                    'asset_code' => $asset->asset_code,
                    'name' => $asset->name,
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_asset()
    {
        $response = $this->getJson('/api/assets/9999', $this->getHeaders());

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_update_an_asset()
    {
        $asset = Asset::factory()->create();

        $data = [
            'name' => 'Updated Asset Name',
            'description' => 'Updated Description',
            'status' => 'under-maintenance',
        ];

        $response = $this->putJson("/api/assets/{$asset->id}", $data, $this->getHeaders());

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Asset Name',
                    'description' => 'Updated Description',
                    'status' => 'under-maintenance',
                ],
            ]);

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'name' => 'Updated Asset Name',
            'status' => 'under-maintenance',
        ]);
    }

    /** @test */
    public function it_can_delete_an_asset()
    {
        $asset = Asset::factory()->create();

        $response = $this->deleteJson("/api/assets/{$asset->id}", [], $this->getHeaders());

        $response->assertStatus(204);
        $this->assertSoftDeleted('assets', ['id' => $asset->id]);
    }
}
