<?php

namespace Tests\Unit\Models;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->category = AssetCategory::factory()->create();
        $this->location = Location::factory()->create();
        $this->department = Department::factory()->create();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_has_required_fields()
    {
        $asset = Asset::create([
            'asset_code' => 'AST-001',
            'name' => 'Test Asset',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'status' => 'operational',
        ]);

        $this->assertDatabaseHas('assets', [
            'asset_code' => 'AST-001',
            'name' => 'Test Asset',
            'status' => 'operational',
        ]);
    }

    /** @test */
    public function it_belongs_to_category()
    {
        $asset = Asset::factory()->create([
            'category_id' => $this->category->id,
        ]);

        $this->assertInstanceOf(AssetCategory::class, $asset->category);
        $this->assertEquals($this->category->id, $asset->category->id);
    }

    /** @test */
    public function it_belongs_to_location()
    {
        $asset = Asset::factory()->create([
            'location_id' => $this->location->id,
        ]);

        $this->assertInstanceOf(Location::class, $asset->location);
        $this->assertEquals($this->location->id, $asset->location->id);
    }

    /** @test */
    public function it_can_be_assigned_to_department()
    {
        $asset = Asset::factory()->create([
            'department_id' => $this->department->id,
        ]);

        $this->assertInstanceOf(Department::class, $asset->department);
        $this->assertEquals($this->department->id, $asset->department->id);
    }

    /** @test */
    public function it_can_be_assigned_to_user()
    {
        $asset = Asset::factory()->create([
            'assigned_to' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $asset->assignedTo);
        $this->assertEquals($this->user->id, $asset->assignedTo->id);
    }

    /** @test */
    public function it_has_documents_relationship()
    {
        $asset = Asset::factory()->create();
        
        $this->assertCount(0, $asset->documents);
        
        // Test with documents would require Document factory
    }

    /** @test */
    public function it_has_maintenance_logs_relationship()
    {
        $asset = Asset::factory()->create();
        
        $this->assertCount(0, $asset->maintenanceLogs);
    }

    /** @test */
    public function it_has_maintenance_schedules_relationship()
    {
        $asset = Asset::factory()->create();
        
        $this->assertCount(0, $asset->maintenanceSchedules);
    }
}
