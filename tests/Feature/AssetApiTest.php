<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // Seed necessary data
    }

    /** @test */
    public function it_returns_paginated_assets()
    {
        // Create test assets
        Asset::factory()->count(15)->create();

        $user = User::first();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/assets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links' => [
                    'first', 'last', 'prev', 'next',
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/v1/assets');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_asset_creation()
    {
        $user = User::first();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/assets', [
                'name' => '',
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'status']);
    }

    /** @test */
    public function it_creates_an_asset()
    {
        $user = User::first();

        $assetData = [
            'name' => 'Test Asset',
            'description' => 'Test Description',
            'status' => 'available',
            'purchase_date' => now()->toDateString(),
            'purchase_cost' => 1000.50,
            'serial_number' => 'SN12345678',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/assets', $assetData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'status',
                    'purchase_date',
                    'purchase_cost',
                    'serial_number',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('assets', [
            'name' => 'Test Asset',
            'serial_number' => 'SN12345678',
        ]);
    }
}
