<?php

namespace Tests\Feature\Api;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LocationApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @var User */
    protected $user;
    
    /** @var array */
    protected $locationData;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        // Generate test location data
        $this->locationData = [
            'name' => $this->faker->company . ' Office',
            'code' => strtoupper($this->faker->unique()->lexify('LOC????')),
            'address' => $this->faker->address,
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'postal_code' => $this->faker->postcode,
            'country' => $this->faker->country,
            'contact_person' => $this->faker->name,
            'contact_email' => $this->faker->unique()->safeEmail,
            'contact_phone' => $this->faker->phoneNumber,
            'notes' => $this->faker->paragraph,
            'is_active' => true,
        ];
    }

    /** @test */
    public function unauthenticated_users_cannot_access_protected_endpoints()
    {
        $location = Location::factory()->create();
        
        $endpoints = [
            ['method' => 'GET', 'url' => '/api/v1/locations'],
            ['method' => 'POST', 'url' => '/api/v1/locations'],
            ['method' => 'GET', 'url' => "/api/v1/locations/{$location->id}"],
            ['method' => 'PUT', 'url' => "/api/v1/locations/{$location->id}"],
            ['method' => 'DELETE', 'url' => "/api/v1/locations/{$location->id}"],
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->json($endpoint['method'], $endpoint['url']);
            $response->assertStatus(401);
        }
    }

    /** @test */
    public function can_retrieve_all_locations()
    {
        $locations = Location::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/locations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    }

    /** @test */
    public function can_create_a_location()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/locations', $this->locationData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'code',
                    'is_active',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $this->assertDatabaseHas('locations', [
            'name' => $this->locationData['name'],
            'code' => $this->locationData['code'],
            'is_active' => true,
        ]);
    }

    /** @test */
    public function can_retrieve_a_single_location()
    {
        $location = Location::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'code' => $location->code,
                    'is_active' => $location->is_active,
                ]
            ]);
    }

    /** @test */
    public function can_update_a_location()
    {
        $location = Location::factory()->create();
        $updateData = [
            'name' => 'Updated Location Name',
            'code' => 'UPDATED',
            'is_active' => false,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/locations/{$location->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => $updateData['name'],
                    'code' => $updateData['code'],
                    'is_active' => $updateData['is_active'],
                ]
            ]);

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'name' => $updateData['name'],
            'code' => $updateData['code'],
            'is_active' => $updateData['is_active'],
        ]);
    }

    /** @test */
    public function can_delete_a_location()
    {
        $location = Location::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
    }

    /** @test */
    public function validates_required_fields_when_creating_location()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/locations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function can_retrieve_location_hierarchy()
    {
        $parent = Location::factory()->create();
        $child1 = Location::factory()->create(['parent_id' => $parent->id]);
        $child2 = Location::factory()->create(['parent_id' => $parent->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/locations/hierarchy');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'children' => [
                            '*' => [
                                'id',
                                'name'
                            ]
                        ]
                    ]
                ]
            ]);
    }

    /** @test */
    public function can_filter_locations_by_status()
    {
        $activeLocation = Location::factory()->create(['is_active' => true]);
        $inactiveLocation = Location::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/locations?filter[is_active]=true');

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $activeLocation->id])
            ->assertJsonMissing(['id' => $inactiveLocation->id]);
    }
}
