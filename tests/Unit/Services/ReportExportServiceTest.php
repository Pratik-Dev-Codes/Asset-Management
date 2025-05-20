<?php

namespace Tests\Unit\Services;

use App\Models\Report;
use App\Models\ReportFile;
use App\Models\User;
use App\Services\ReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportExportServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @var ReportExportService */
    protected $service;

    /** @var Report */
    protected $report;

    /** @var User */
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake the storage
        Storage::fake('public');

        // Create a test user
        $this->user = User::factory()->create();

        // Create a test report
        $this->report = Report::factory()->create([
            'name' => 'Test Report',
            'description' => 'A test report',
            'columns' => ['id', 'name', 'created_at'],
            'filters' => [],
            'sorting' => ['field' => 'id', 'direction' => 'asc'],
            'created_by' => $this->user->id,
        ]);

        // Initialize the service
        $this->service = app(ReportExportService::class);
    }

    /** @test */
    public function it_can_export_a_report_to_excel()
    {
        // Mock the report data
        $data = [
            ['id' => 1, 'name' => 'Test Item 1', 'created_at' => now()],
            ['id' => 2, 'name' => 'Test Item 2', 'created_at' => now()],
        ];

        // Mock the report's generateData method
        $this->mock(Report::class, function ($mock) use ($data) {
            $mock->shouldReceive('generateData')->andReturn($data);
        });

        // Export the report
        $file = $this->service->export($this->report, 'xlsx', $this->user, false);

        // Assert the file was created
        Storage::disk('public')->assertExists($file->file_path);
        $this->assertEquals('xlsx', $file->file_type);
        $this->assertEquals($this->user->id, $file->generated_by);
    }

    /** @test */
    public function it_can_export_a_report_to_csv()
    {
        // Mock the report data
        $data = [
            ['id' => 1, 'name' => 'Test Item 1', 'created_at' => now()],
            ['id' => 2, 'name' => 'Test Item 2', 'created_at' => now()],
        ];

        // Mock the report's generateData method
        $this->mock(Report::class, function ($mock) use ($data) {
            $mock->shouldReceive('generateData')->andReturn($data);
        });

        // Export the report
        $file = $this->service->export($this->report, 'csv', $this->user, false);

        // Assert the file was created
        Storage::disk('public')->assertExists($file->file_path);
        $this->assertEquals('csv', $file->file_type);
    }

    /** @test */
    public function it_queues_export_when_requested()
    {
        // Mock the job dispatch
        $this->mock(\Illuminate\Bus\Dispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch');
        });

        // Export the report with queue=true
        $result = $this->service->export($this->report, 'xlsx', $this->user, true);

        // Assert the job was dispatched
        $this->assertTrue($result);
    }

    /** @test */
    public function it_cleans_up_old_reports()
    {
        // Create some test report files
        $files = [];
        for ($i = 0; $i < 10; $i++) {
            $files[] = ReportFile::factory()->create([
                'report_id' => $this->report->id,
                'file_name' => "report_{$i}.xlsx",
                'file_path' => "reports/{$this->report->id}/report_{$i}.xlsx",
                'file_type' => 'xlsx',
                'file_size' => 1024,
                'generated_by' => $this->user->id,
            ]);

            // Create the file in storage
            Storage::disk('public')->put("reports/{$this->report->id}/report_{$i}.xlsx", 'test');
        }

        // Clean up old reports, keeping only 5
        $this->service->cleanupOldReports($this->report, 5);

        // Assert only 5 files remain
        $remainingFiles = $this->report->files()->count();
        $this->assertEquals(5, $remainingFiles);

        // Assert the oldest files were deleted
        $this->assertDatabaseMissing('report_files', ['id' => $files[0]->id]);
        $this->assertDatabaseMissing('report_files', ['id' => $files[4]->id]);
        $this->assertDatabaseHas('report_files', ['id' => $files[5]->id]);
        $this->assertDatabaseHas('report_files', ['id' => $files[9]->id]);

        // Assert the files were deleted from storage
        Storage::disk('public')->assertMissing($files[0]->file_path);
        Storage::disk('public')->assertExists($files[9]->file_path);
    }

    /** @test */
    public function it_handles_export_errors_gracefully()
    {
        // Mock the report to throw an exception
        $this->mock(Report::class, function ($mock) {
            $mock->shouldReceive('generateData')->andThrow(new \RuntimeException('Test error'));
        });

        // Expect an exception
        $this->expectException(\RuntimeException::class);

        // Try to export the report
        $this->service->export($this->report, 'xlsx', $this->user, false);
    }
}
