<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_list_assets()
    {
        $assets = Asset::factory()->count(3)->create();

        $response = $this->getJson('/api/assets');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_create_an_asset()
    {
        $assetData = [
            'name' => 'Test Asset',
            'asset_tag' => 'AST-001',
            'status_id' => 1,
            'model_id' => 1,
        ];

        $response = $this->postJson('/api/assets', $assetData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Test Asset']);

        $this->assertDatabaseHas('assets', ['name' => 'Test Asset']);
    }

    // Add more test methods for update, delete, etc.
}
