<?php

namespace Tests\Unit\Services;

use App\Models\Report;
use App\Services\ReportDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ReportDataServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @var ReportDataService */
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReportDataService::class);
    }

    /** @test */
    public function it_gets_report_data_with_filters()
    {
        // Create a test report
        $report = Report::factory()->create([
            'type' => 'asset',
            'filters' => [
                ['field' => 'status', 'operator' => 'equals', 'value' => 'active'],
            ],
            'columns' => ['id', 'name', 'status'],
            'sorting' => ['field' => 'name', 'direction' => 'asc'],
        ]);

        // Mock the database query
        $mockBuilder = $this->mock(\Illuminate\Database\Query\Builder::class, function ($mock) {
            $mock->shouldReceive('where')->andReturnSelf();
            $mock->shouldReceive('orderBy')->andReturnSelf();
            $mock->shouldReceive('count')->andReturn(2);
            $mock->shouldReceive('paginate')->andReturn(
                new \Illuminate\Pagination\LengthAwarePaginator(
                    [
                        ['id' => 1, 'name' => 'Asset 1', 'status' => 'active'],
                        ['id' => 2, 'name' => 'Asset 2', 'status' => 'active'],
                    ],
                    2, 15, 1
                )
            );
        });

        // Get report data
        $data = $this->service->getReportData($report);

        // Assert the response structure
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertCount(2, $data['data']);
        $this->assertEquals(2, $data['meta']['total']);
    }

    /** @test */
    public function it_caches_report_data()
    {
        $report = Report::factory()->create([
            'type' => 'asset',
            'columns' => ['id', 'name'],
        ]);

        // Mock the query builder
        $this->mock(\Illuminate\Database\Query\Builder::class, function ($mock) {
            $mock->shouldReceive('where')->andReturnSelf();
            $mock->shouldReceive('orderBy')->andReturnSelf();
            $mock->shouldReceive('count')->andReturn(1);
            $mock->shouldReceive('paginate')->once()->andReturn(
                new \Illuminate\Pagination\LengthAwarePaginator(
                    [['id' => 1, 'name' => 'Test Asset']],
                    1, 15, 1
                )
            );
        });

        // First call - should hit the database
        $data1 = $this->service->getReportData($report);

        // Second call - should come from cache
        $data2 = $this->service->getReportData($report);

        $this->assertEquals($data1, $data2);
    }

    /** @test */
    public function it_clears_report_cache()
    {
        $report = Report::factory()->create([
            'type' => 'asset',
            'columns' => ['id', 'name'],
        ]);

        // Mock the query builder
        $this->mock(\Illuminate\Database\Query\Builder::class, function ($mock) {
            $mock->shouldReceive('where')->andReturnSelf();
            $mock->shouldReceive('orderBy')->andReturnSelf();
            $mock->shouldReceive('count')->andReturn(1);
            $mock->shouldReceive('paginate')->twice()->andReturn(
                new \Illuminate\Pagination\LengthAwarePaginator(
                    [['id' => 1, 'name' => 'Test Asset']],
                    1, 15, 1
                )
            );
        });

        // First call - should hit the database
        $data1 = $this->service->getReportData($report);

        // Clear the cache
        $this->service->clearCache($report);

        // Second call - should hit the database again
        $data2 = $this->service->getReportData($report);

        $this->assertEquals($data1, $data2);
    }

    /** @test */
    public function it_validates_report_type()
    {
        $this->expectException(\App\Exceptions\ReportGenerationException::class);

        $report = Report::factory()->create([
            'type' => 'invalid_type',
            'columns' => ['id'],
        ]);

        $this->service->getReportData($report);
    }

    /** @test */
    public function it_applies_pagination()
    {
        $report = Report::factory()->create([
            'type' => 'asset',
            'columns' => ['id', 'name'],
            'per_page' => 5,
        ]);

        // Mock the query builder
        $this->mock(\Illuminate\Database\Query\Builder::class, function ($mock) {
            $mock->shouldReceive('where')->andReturnSelf();
            $mock->shouldReceive('orderBy')->andReturnSelf();
            $mock->shouldReceive('count')->andReturn(10);
            $mock->shouldReceive('paginate')
                ->with(5, ['*'], 'page', 2)
                ->andReturn(
                    new \Illuminate\Pagination\LengthAwarePaginator(
                        [['id' => 6, 'name' => 'Asset 6']],
                        10, 5, 2
                    )
                );
        });

        $data = $this->service->getReportData($report, [], ['page' => 2]);

        $this->assertEquals(2, $data['meta']['current_page']);
        $this->assertEquals(5, $data['meta']['per_page']);
        $this->assertEquals(10, $data['meta']['total']);
    }
}
