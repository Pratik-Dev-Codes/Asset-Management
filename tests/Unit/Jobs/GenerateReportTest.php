<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GenerateReport;
use App\Models\Report;
use App\Models\ReportFile;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class GenerateReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake storage for report files
        Storage::fake('reports');
        
        // Create a test user
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_generates_a_report_successfully()
    {
        // Create a report
        $report = Report::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'format' => 'xlsx',
            'filters' => json_encode([
                'status' => 'active',
                'date_from' => now()->subMonth()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ]),
        ]);
        
        // Mock the ReportService
        $reportService = Mockery::mock(ReportService::class);
        $reportService->shouldReceive('generateReport')
            ->once()
            ->with(
                $report->type,
                $report->format,
                json_decode($report->filters, true),
                $report->id
            )
            ->andReturn([
                'file_path' => 'reports/test_report.xlsx',
                'file_name' => 'test_report.xlsx',
                'file_size' => 1024,
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        
        // Bind the mock to the service container
        $this->app->instance(ReportService::class, $reportService);
        
        // Dispatch the job
        $job = new GenerateReport($report);
        $job->handle($reportService);
        
        // Assert the report was updated
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'completed',
            'file_path' => 'reports/test_report.xlsx',
            'file_name' => 'test_report.xlsx',
            'file_size' => 1024,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'error_message' => null,
        ], 'mysql');
        
        // Assert the report file was created
        $this->assertDatabaseHas('report_files', [
            'report_id' => $report->id,
            'file_path' => 'reports/test_report.xlsx',
            'file_name' => 'test_report.xlsx',
            'file_size' => 1024,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'generated_by' => $this->user->id,
        ], 'mysql');
    }
    
    /** @test */
    public function it_handles_report_generation_failure()
    {
        // Create a report
        $report = Report::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'format' => 'xlsx',
            'filters' => json_encode([
                'status' => 'active',
                'date_from' => now()->subMonth()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ]),
        ]);
        
        // Mock the ReportService to throw an exception
        $reportService = Mockery::mock(ReportService::class);
        $reportService->shouldReceive('generateReport')
            ->once()
            ->andThrow(new \Exception('Failed to generate report'));
        
        // Bind the mock to the service container
        $this->app->instance(ReportService::class, $reportService);
        
        // Dispatch the job
        $job = new GenerateReport($report);
        $job->handle($reportService);
        
        // Assert the report was marked as failed
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'failed',
            'error_message' => 'Failed to generate report',
        ], 'mysql');
    }
    
    /** @test */
    public function it_can_generate_different_report_formats()
    {
        $formats = [
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'pdf' => 'application/pdf',
        ];
        
        foreach ($formats as $format => $mimeType) {
            // Create a report with the current format
            $report = Report::factory()->create([
                'created_by' => $this->user->id,
                'status' => 'pending',
                'format' => $format,
                'filters' => json_encode([
                    'status' => 'active',
                    'date_from' => now()->subMonth()->format('Y-m-d'),
                    'date_to' => now()->format('Y-m-d'),
                ]),
            ]);
            
            // Mock the ReportService
            $reportService = Mockery::mock(ReportService::class);
            $reportService->shouldReceive('generateReport')
                ->once()
                ->andReturn([
                    'file_path' => "reports/test_report.{$format}",
                    'file_name' => "test_report.{$format}",
                    'file_size' => 1024,
                    'mime_type' => $mimeType,
                ]);
            
            // Bind the mock to the service container
            $this->app->instance(ReportService::class, $reportService);
            
            // Dispatch the job
            $job = new GenerateReport($report);
            $job->handle($reportService);
            
            // Assert the report was updated with the correct format
            $this->assertDatabaseHas('reports', [
                'id' => $report->id,
                'status' => 'completed',
                'format' => $format,
                'mime_type' => $mimeType,
            ], 'mysql');
        }
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
