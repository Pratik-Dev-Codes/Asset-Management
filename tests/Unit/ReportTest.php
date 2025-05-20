<?php

namespace Tests\Unit;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_required_fields()
    {
        $report = Report::factory()->create([
            'name' => 'Test Report',
            'type' => 'assets',
            'columns' => ['id', 'name']
        ]);

        $this->assertDatabaseHas('reports', [
            'name' => 'Test Report',
            'type' => 'assets'
        ]);
    }

    /** @test */
    public function it_requires_name_and_type()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Report::create([
            'columns' => ['id']
        ]);
    }

    /** @test */
    public function it_validates_columns_against_available_columns()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Report::create([
            'name' => 'Invalid Report',
            'type' => 'assets',
            'columns' => ['invalid_column']
        ]);
    }

    /** @test */
    public function it_can_be_accessed_by_owner()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create(['created_by' => $user->id]);
        
        $this->assertTrue($report->isAccessibleBy($user));
    }

    /** @test */
    public function it_can_be_public()
    {
        $report = Report::factory()->create(['is_public' => true]);
        $user = User::factory()->create();
        
        $this->assertTrue($report->isAccessibleBy($user));
    }

    /** @test */
    public function it_clears_caches_on_save()
    {
        $report = Report::factory()->create();
        $cacheKey = "report.{$report->id}";
        
        // Prime the cache
        \Illuminate\Support\Facades\Cache::put($cacheKey, $report, now()->addHour());
        
        // Update the report
        $report->update(['name' => 'Updated Name']);
        
        $this->assertFalse(\Illuminate\Support\Facades\Cache::has($cacheKey));
    }
}
