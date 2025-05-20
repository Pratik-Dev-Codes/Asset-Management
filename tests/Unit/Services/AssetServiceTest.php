<?php

namespace Tests\Unit\Services;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Department;
use App\Models\Location;
use App\Models\Maintenance;
use App\Models\User;
use App\Repositories\AssetRepository;
use App\Services\AssetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class AssetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $assetService;

    protected $assetRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->category = AssetCategory::factory()->create();
        $this->location = Location::factory()->create();
        $this->department = Department::factory()->create();
        $this->user = User::factory()->create();

        // Create assets
        $this->assets = Asset::factory()->count(10)->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'assigned_to' => $this->user->id,
            'status' => 'in_use',
            'purchase_cost' => 1000.00,
        ]);

        // Create maintenance records
        foreach ($this->assets->take(3) as $asset) {
            Maintenance::factory()->create([
                'asset_id' => $asset->id,
                'next_maintenance_date' => now()->addDays(5),
                'is_active' => true,
            ]);
        }

        $this->assetRepository = Mockery::mock(AssetRepository::class);
        $this->assetService = new AssetService($this->assetRepository);

        // Clear cache before each test
        Cache::flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    /** @test */
    public function it_can_get_all_assets()
    {
        // Mock the repository
        $this->assetRepository->shouldReceive('newQuery')
            ->andReturn(Asset::query());

        $result = $this->assetService->getAllAssets([], 10);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertCount(10, $result->items());
    }

    /** @test */
    public function it_can_get_asset_by_id()
    {
        $asset = $this->assets->first();

        $this->assetRepository->shouldReceive('with')
            ->andReturnSelf()
            ->shouldReceive('findOrFail')
            ->with($asset->id)
            ->andReturn($asset);

        $result = $this->assetService->getAssetById($asset->id);

        $this->assertInstanceOf(Asset::class, $result);
        $this->assertEquals($asset->id, $result->id);
    }

    /** @test */
    public function it_can_create_an_asset()
    {
        $data = [
            'name' => 'New Test Asset',
            'asset_code' => 'TEST-001',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'status' => 'in_stock',
            'purchase_cost' => 1500.00,
            'purchase_date' => now()->toDateString(),
        ];

        $this->assetRepository->shouldReceive('create')
            ->with(Mockery::on(function ($arg) use ($data) {
                return $arg['name'] === $data['name'];
            }))
            ->andReturn(Asset::make($data));

        $result = $this->assetService->createAsset($data);

        $this->assertInstanceOf(Asset::class, $result);
        $this->assertEquals($data['name'], $result->name);
    }

    /** @test */
    public function it_can_update_an_asset()
    {
        $asset = $this->assets->first();
        $newName = 'Updated Asset Name';

        $this->assetRepository->shouldReceive('with')
            ->andReturnSelf()
            ->shouldReceive('findOrFail')
            ->with($asset->id)
            ->andReturn($asset);

        $result = $this->assetService->updateAsset($asset->id, ['name' => $newName]);

        $this->assertEquals($newName, $result->name);
    }

    /** @test */
    public function it_can_delete_an_asset()
    {
        $asset = $this->assets->first();

        $this->assetRepository->shouldReceive('with')
            ->andReturnSelf()
            ->shouldReceive('findOrFail')
            ->with($asset->id)
            ->andReturn($asset);

        $result = $this->assetService->deleteAsset($asset->id);

        $this->assertTrue($result);
        $this->assertNull(Asset::find($asset->id));
    }

    /** @test */
    public function it_can_get_asset_statistics()
    {
        $this->assetRepository->shouldReceive('newQuery')
            ->andReturn(Asset::query());

        $this->assetRepository->shouldReceive('count')
            ->andReturn(10);

        $this->assetRepository->shouldReceive('sum')
            ->with('purchase_cost')
            ->andReturn(10000.00);

        $this->assetRepository->shouldReceive('groupBy')
            ->andReturnSelf();

        $this->assetRepository->shouldReceive('select')
            ->andReturnSelf();

        $this->assetRepository->shouldReceive('pluck')
            ->andReturn(collect(['in_use' => 10]));

        $this->assetRepository->shouldReceive('join')
            ->andReturnSelf();

        $this->assetRepository->shouldReceive('orderBy')
            ->andReturnSelf();

        $this->assetRepository->shouldReceive('with')
            ->andReturnSelf();

        $this->assetRepository->shouldReceive('limit')
            ->andReturnSelf();

        $this->assetRepository->shouldReceive('get')
            ->andReturn(collect([]));

        $stats = $this->assetService->getAssetStats();

        $this->assertArrayHasKey('total_assets', $stats);
        $this->assertArrayHasKey('total_value', $stats);
        $this->assertArrayHasKey('by_status', $stats);
        $this->assertArrayHasKey('by_category', $stats);
        $this->assertArrayHasKey('recently_added', $stats);
        $this->assertArrayHasKey('due_for_maintenance', $stats);
    }

    /** @test */
    public function it_can_get_assets_due_for_maintenance()
    {
        $this->assetRepository->shouldReceive('whereHas')
            ->andReturnSelf();

        $this->assetRepository->shouldReceive('with')
            ->andReturnSelf();

        $this->assetRepository->shouldReceive('limit')
            ->andReturnSelf();

        $this->assetRepository->shouldReceive('get')
            ->andReturn(collect($this->assets->take(3)));

        $result = $this->assetService->getDueForMaintenance(7, 5);

        $this->assertCount(3, $result);
    }

    /** @test */
    public function it_can_get_recently_added_assets()
    {
        $this->assetRepository->shouldReceive('with')
            ->andReturnSelf();

        $this->assetRepository->shouldReceive('orderBy')
            ->andReturnSelf();

        $this->assetRepository->shouldReceive('limit')
            ->andReturnSelf();

        $this->assetRepository->shouldReceive('get')
            ->andReturn(collect($this->assets->take(5)));

        $result = $this->assetService->getRecentlyAdded(5);

        $this->assertCount(5, $result);
    }

    /** @test */
    public function it_clears_cache_when_asset_is_created()
    {
        $data = [
            'name' => 'New Test Asset',
            'asset_code' => 'TEST-001',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'status' => 'in_stock',
            'purchase_cost' => 1500.00,
            'purchase_date' => now()->toDateString(),
        ];

        $this->assetRepository->shouldReceive('create')
            ->andReturn(Asset::make($data));

        // Set up cache
        Cache::tags(['assets'])->put('test_key', 'test_value', 60);
        $this->assertTrue(Cache::tags(['assets'])->has('test_key'));

        // Create asset should clear cache
        $this->assetService->createAsset($data);

        // Cache should be cleared
        $this->assertFalse(Cache::tags(['assets'])->has('test_key'));
    }
}
