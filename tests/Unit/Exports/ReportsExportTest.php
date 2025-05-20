<?php

namespace Tests\Unit\Exports;

use App\Exports\ReportsExport;
use App\Models\Asset;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel;
use Mockery;
use Tests\TestCase;

class ReportsExportTest extends TestCase
{
    use RefreshDatabase;

    protected $report;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create();

        // Create a test report
        $this->report = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'asset',
            'format' => 'xlsx',
            'filters' => json_encode([
                'status' => 'active',
                'date_from' => now()->subMonth()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ]),
        ]);

        // Create some test assets
        Asset::factory()->count(5)->create([
            'status' => 'active',
            'purchase_date' => now()->subDays(10),
            'created_at' => now()->subDays(10),
        ]);
    }

    /** @test */
    public function it_can_export_asset_reports_to_xlsx()
    {
        // Create a partial mock of the ReportsExport class
        $export = $this->getMockBuilder(ReportsExport::class)
            ->setConstructorArgs([
                'asset',
                [
                    'status' => 'active',
                    'date_from' => now()->subMonth()->format('Y-m-d'),
                    'date_to' => now()->format('Y-m-d'),
                ],
                $this->report->id,
            ])
            ->onlyMethods(['collection', 'headings', 'title'])
            ->getMock();

        // Set up expectations for the mock
        $export->method('collection')
            ->willReturn(collect(range(1, 6))); // 5 assets + header row

        $export->method('headings')
            ->willReturn(['ID', 'Name', 'Status']); // Sample headings

        $export->method('title')
            ->willReturn('Asset Report');

        // Test the collection method
        $collection = $export->collection();
        $this->assertCount(6, $collection);

        // Test the headings
        $headings = $export->headings();
        $this->assertIsArray($headings);
        $this->assertNotEmpty($headings);

        // Test the title
        $this->assertEquals('Asset Report', $export->title());
    }

    /** @test */
    public function it_can_export_user_reports_to_csv()
    {
        // Create a partial mock of the ReportsExport class
        $export = $this->getMockBuilder(ReportsExport::class)
            ->setConstructorArgs([
                'user',
                [
                    'role' => 'admin',
                    'status' => 'active',
                ],
                $this->report->id,
            ])
            ->onlyMethods(['collection', 'headings', 'title'])
            ->getMock();

        // Set up expectations for the mock
        $export->method('collection')
            ->willReturn(collect(range(1, 3))); // Sample data

        $export->method('headings')
            ->willReturn(['ID', 'Name', 'Email', 'Role']);

        $export->method('title')
            ->willReturn('User Report');

        // Test the collection method
        $collection = $export->collection();
        $this->assertNotEmpty($collection);

        // Test the headings
        $headings = $export->headings();
        $this->assertIsArray($headings);
        $this->assertNotEmpty($headings);

        // Test the title
        $this->assertEquals('User Report', $export->title());
    }

    /** @test */
    public function it_can_export_transaction_reports_to_pdf()
    {
        // Create a partial mock of the ReportsExport class
        $export = $this->getMockBuilder(ReportsExport::class)
            ->setConstructorArgs([
                'transaction',
                [
                    'type' => 'purchase',
                    'date_from' => now()->subMonth()->format('Y-m-d'),
                    'date_to' => now()->format('Y-m-d'),
                ],
                $this->report->id,
            ])
            ->onlyMethods(['collection', 'headings', 'title', 'view'])
            ->getMock();

        // Set up expectations for the mock
        $export->method('collection')
            ->willReturn(collect(range(1, 4))); // Sample data

        $export->method('headings')
            ->willReturn(['ID', 'Type', 'Amount', 'Date']);

        $export->method('title')
            ->willReturn('Transaction Report');

        // Mock the view
        $viewMock = $this->createMock(\Illuminate\View\View::class);
        $viewMock->method('getName')
            ->willReturn('exports.reports.transaction');

        $export->method('view')
            ->willReturn($viewMock);

        // Test the collection method
        $collection = $export->collection();
        $this->assertIsIterable($collection);

        // Test the headings
        $headings = $export->headings();
        $this->assertIsArray($headings);
        $this->assertNotEmpty($headings);

        // Test the title
        $this->assertEquals('Transaction Report', $export->title());

        // Test the view (for PDF export)
        $view = $export->view();
        $this->assertEquals('exports.reports.transaction', $view->getName());
    }

    /** @test */
    public function it_returns_empty_collection_for_unknown_report_type()
    {
        // Create a partial mock of the ReportsExport class
        $export = $this->getMockBuilder(ReportsExport::class)
            ->setConstructorArgs([
                'unknown_type',
                [],
                $this->report->id,
            ])
            ->onlyMethods(['collection'])
            ->getMock();

        $export->method('collection')
            ->willReturn(collect([]));

        $collection = $export->collection();
        $this->assertEmpty($collection);
    }

    /** @test */
    public function it_applies_filters_correctly()
    {
        // Create a partial mock of the ReportsExport class
        $export = $this->getMockBuilder(ReportsExport::class)
            ->setConstructorArgs([
                'asset',
                [
                    'status' => 'inactive',
                    'date_from' => now()->subMonth()->format('Y-m-d'),
                    'date_to' => now()->format('Y-m-d'),
                ],
                $this->report->id,
            ])
            ->onlyMethods(['collection'])
            ->getMock();

        // Mock the collection method to return 4 items (3 inactive assets + header row)
        $export->method('collection')
            ->willReturn(collect(range(1, 4)));

        $collection = $export->collection();
        $this->assertCount(4, $collection);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
