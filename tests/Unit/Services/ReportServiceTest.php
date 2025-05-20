<?php

namespace Tests\Unit\Services;

use App\Models\Report;
use App\Models\ReportFile;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel;
use Mockery;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $reportService;

    protected $excelMock;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake storage for report files
        Storage::fake('reports');

        // Create a test user
        $this->user = User::factory()->create();

        // Mock the Excel facade
        $this->excelMock = Mockery::mock('excel');
        $this->app->instance(Excel::class, $this->excelMock);

        // Create an instance of the ReportService
        $this->reportService = new ReportService($this->excelMock);
    }

    /** @test */
    public function it_generates_an_asset_report()
    {
        // Create a report
        $report = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'asset',
            'format' => 'xlsx',
            'filters' => json_encode([
                'status' => 'active',
                'date_from' => now()->subMonth()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ]),
        ]);

        // Mock the Excel facade to return a file path
        $filePath = 'reports/asset_report_'.now()->format('YmdHis').'.xlsx';

        $this->excelMock->shouldReceive('download')
            ->once()
            ->andReturnUsing(function ($export, $fileName, $writerType) use ($filePath) {
                // Simulate file download by creating a fake file
                Storage::disk('reports')->put($filePath, 'Test Excel Content');

                return response()->download(storage_path('app/'.$filePath));
            });

        // Generate the report
        $result = $this->reportService->generateReport(
            'asset',
            'xlsx',
            [
                'status' => 'active',
                'date_from' => now()->subMonth()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ],
            $report->id
        );

        // Assert the result contains the expected file information
        $this->assertIsArray($result);
        $this->assertArrayHasKey('file_path', $result);
        $this->assertArrayHasKey('file_name', $result);
        $this->assertArrayHasKey('file_size', $result);
        $this->assertArrayHasKey('mime_type', $result);

        // Assert the file was created
        $this->assertTrue(Storage::disk('reports')->exists($result['file_path']));
    }

    /** @test */
    public function it_generates_a_user_report()
    {
        // Create a report
        $report = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'user',
            'format' => 'csv',
            'filters' => json_encode([
                'role' => 'admin',
                'status' => 'active',
            ]),
        ]);

        // Mock the Excel facade to return a file path
        $filePath = 'reports/user_report_'.now()->format('YmdHis').'.csv';

        $this->excelMock->shouldReceive('download')
            ->once()
            ->andReturnUsing(function ($export, $fileName, $writerType) use ($filePath) {
                // Simulate file download by creating a fake file
                Storage::disk('reports')->put($filePath, 'Test CSV Content');

                return response()->download(storage_path('app/'.$filePath));
            });

        // Generate the report
        $result = $this->reportService->generateReport(
            'user',
            'csv',
            [
                'role' => 'admin',
                'status' => 'active',
            ],
            $report->id
        );

        // Assert the result contains the expected file information
        $this->assertIsArray($result);
        $this->assertArrayHasKey('file_path', $result);
        $this->assertStringEndsWith('.csv', $result['file_path']);
        $this->assertStringEndsWith('.csv', $result['file_name']);
        $this->assertEquals('text/csv', $result['mime_type']);
    }

    /** @test */
    public function it_generates_a_transaction_report()
    {
        // Create a report
        $report = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'transaction',
            'format' => 'pdf',
            'filters' => json_encode([
                'type' => 'purchase',
                'date_from' => now()->subMonth()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ]),
        ]);

        // Mock the Excel facade to return a file path
        $filePath = 'reports/transaction_report_'.now()->format('YmdHis').'.pdf';

        $this->excelMock->shouldReceive('download')
            ->once()
            ->andReturnUsing(function ($export, $fileName, $writerType) use ($filePath) {
                // Simulate file download by creating a fake file
                Storage::disk('reports')->put($filePath, 'Test PDF Content');

                return response()->download(storage_path('app/'.$filePath));
            });

        // Generate the report
        $result = $this->reportService->generateReport(
            'transaction',
            'pdf',
            [
                'type' => 'purchase',
                'date_from' => now()->subMonth()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ],
            $report->id
        );

        // Assert the result contains the expected file information
        $this->assertIsArray($result);
        $this->assertArrayHasKey('file_path', $result);
        $this->assertStringEndsWith('.pdf', $result['file_path']);
        $this->assertStringEndsWith('.pdf', $result['file_name']);
        $this->assertEquals('application/pdf', $result['mime_type']);
    }

    /** @test */
    public function it_throws_an_exception_for_invalid_report_type()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid report type: invalid_type');

        $this->reportService->generateReport(
            'invalid_type',
            'xlsx',
            [],
            1
        );
    }

    /** @test */
    public function it_throws_an_exception_for_invalid_export_format()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid export format: invalid_format');

        $this->reportService->generateReport(
            'asset',
            'invalid_format',
            [],
            1
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
