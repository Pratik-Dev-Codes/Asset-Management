<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class QueryOptimizationServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_registers_query_macros()
    {
        // Test the withRelations macro
        $query = Asset::query();
        $this->assertTrue(method_exists($query, 'withRelations'));

        // Test the eagerLoadCountWithConstraints macro
        $this->assertTrue(method_exists($query, 'eagerLoadCountWithConstraints'));
    }

    /** @test */
    public function with_relations_macro_works_correctly()
    {
        // Create test data
        $category = AssetCategory::factory()->create();
        $asset = Asset::factory()->create(['category_id' => $category->id]);

        // Test with string relation
        $query = Asset::withRelations('category');
        $result = $query->first();
        $this->assertTrue($result->relationLoaded('category'));

        // Test with array of relations
        $query = Asset::withRelations(['category']);
        $result = $query->first();
        $this->assertTrue($result->relationLoaded('category'));

        // Test with constraints
        $query = Asset::withRelations([
            'category' => function ($q) {
                $q->where('id', '>', 0);
            },
        ]);
        $result = $query->first();
        $this->assertTrue($result->relationLoaded('category'));
    }

    /** @test */
    public function eager_load_count_with_constraints_works_correctly()
    {
        // Create test data
        $category = AssetCategory::factory()->create();
        $asset = Asset::factory()->create(['category_id' => $category->id]);

        // Test with string relation
        $query = Asset::query()->eagerLoadCountWithConstraints('category');
        $result = $query->first();
        $this->assertArrayHasKey('category_count', $result->getAttributes());

        // Test with array of relations
        $query = Asset::query()->eagerLoadCountWithConstraints(['category']);
        $result = $query->first();
        $this->assertArrayHasKey('category_count', $result->getAttributes());

        // Test with constraints (constraints are ignored for count)
        $query = Asset::query()->eagerLoadCountWithConstraints([
            'category' => function ($q) {
                $q->where('id', '>', 0);
            },
        ]);
        $result = $query->first();
        $this->assertArrayHasKey('category_count', $result->getAttributes());
    }

    /** @test */
    public function slow_query_logging_works()
    {
        // Mock the logger
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Slow query detected' &&
                       isset($context['sql']) &&
                       isset($context['time']);
            });

        // Set a very low threshold to trigger slow query logging
        config(['query.slow_query_threshold' => 0]);

        // Execute a query that will be considered slow
        DB::table('users')->get();
    }

    /** @test */
    public function query_logging_can_be_disabled()
    {
        // Disable query logging
        config(['query.enable_query_logging' => false]);

        // Mock the logger
        Log::shouldReceive('debug')->never();

        // Execute a query
        DB::table('users')->get();
    }

    /** @test */
    public function slow_query_logging_can_be_disabled()
    {
        // Disable slow query logging
        config(['query.log_slow_queries' => false]);

        // Mock the logger
        Log::shouldReceive('warning')->never();

        // Set a very low threshold that would normally trigger slow query logging
        config(['query.slow_query_threshold' => 0]);

        // Execute a query
        DB::table('users')->get();
    }
}
