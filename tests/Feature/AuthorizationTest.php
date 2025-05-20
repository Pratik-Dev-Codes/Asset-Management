<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Asset;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    protected $user;
    protected $admin;
    protected $asset;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test roles and permissions
        $viewAssets = Permission::findOrCreate('view assets');
        $createAssets = Permission::findOrCreate('create assets');
        $updateAssets = Permission::findOrCreate('update assets');
        $deleteAssets = Permission::findOrCreate('delete assets');

        $adminRole = Role::findOrCreate('admin');
        $userRole = Role::findOrCreate('user');

        // Assign permissions to roles
        $adminRole->syncPermissions([$viewAssets, $createAssets, $updateAssets, $deleteAssets]);
        $userRole->syncPermissions([$viewAssets]);

        // Create test users
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        $this->user = User::factory()->create();
        $this->user->assignRole($userRole);

        // Create a test asset
        $this->asset = Asset::factory()->create();
    }

    /** @test */
    public function admin_can_access_all_asset_routes()
    {
        $this->actingAs($this->admin);

        // Test index
        $response = $this->get(route('assets.index'));
        $response->assertStatus(200);

        // Test create
        $response = $this->get(route('assets.create'));
        $response->assertStatus(200);

        // Test store
        $response = $this->post(route('assets.store'), [
            'name' => 'Test Asset',
            'asset_category_id' => 1,
            'status' => 'active',
        ]);
        $response->assertStatus(302);

        // Test edit
        $response = $this->get(route('assets.edit', $this->asset));
        $response->assertStatus(200);

        // Test update
        $response = $this->put(route('assets.update', $this->asset), [
            'name' => 'Updated Asset Name',
            'asset_category_id' => 1,
            'status' => 'inactive',
        ]);
        $response->assertStatus(302);

        // Test delete
        $response = $this->delete(route('assets.destroy', $this->asset));
        $response->assertStatus(302);
    }

    /** @test */
    public function regular_user_can_only_view_assets()
    {
        $this->actingAs($this->user);

        // Test index - should be allowed
        $response = $this->get(route('assets.index'));
        $response->assertStatus(200);

        // Test create - should be denied
        $response = $this->get(route('assets.create'));
        $response->assertStatus(403);

        // Test store - should be denied
        $response = $this->post(route('assets.store'), [
            'name' => 'Test Asset',
            'asset_category_id' => 1,
            'status' => 'active',
        ]);
        $response->assertStatus(403);

        // Test edit - should be denied
        $response = $this->get(route('assets.edit', $this->asset));
        $response->assertStatus(403);

        // Test update - should be denied
        $response = $this->put(route('assets.update', $this->asset), [
            'name' => 'Updated Asset Name',
            'asset_category_id' => 1,
            'status' => 'inactive',
        ]);
        $response->assertStatus(403);

        // Test delete - should be denied
        $response = $this->delete(route('assets.destroy', $this->asset));
        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_users_cannot_access_protected_routes()
    {
        // Test index - should redirect to login
        $response = $this->get(route('assets.index'));
        $response->assertRedirect(route('login'));

        // Test create - should redirect to login
        $response = $this->get(route('assets.create'));
        $response->assertRedirect(route('login'));

        // Test store - should redirect to login
        $response = $this->post(route('assets.store'), [
            'name' => 'Test Asset',
            'asset_category_id' => 1,
            'status' => 'active',
        ]);
        $response->assertRedirect(route('login'));
    }
}
