<?php

namespace Tests\Unit\Exports;

use App\Exports\ReportExport;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $report;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->report = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'assets',
            'columns' => ['id', 'name', 'created_at'],
            'filters' => [],
            'sorting' => ['created_at' => 'desc'],
        ]);
    }

    /** @test */
    public function it_can_export_to_pdf()
    {
        Excel::fake();

        $export = new ReportExport($this->report);
        $response = $export->download('test.pdf', \Maatwebsite\Excel\Excel::DOMPDF);

        $this->assertNotNull($response);
    }

    /** @test */
    public function it_can_export_to_excel()
    {
        Excel::fake();

        $export = new ReportExport($this->report);
        $response = $export->download('test.xlsx');

        $this->assertNotNull($response);
    }

    /** @test */
    public function it_has_expected_columns()
    {
        $export = new ReportExport($this->report);
        $headings = $export->headings();

        $expected = [
            'ID',
            'Name',
            'Created At',
        ];

        $this->assertEquals($expected, $headings);
    }

    /** @test */
    public function it_formats_dates_correctly()
    {
        $export = new ReportExport($this->report);
        $date = now();
        $formattedDate = $export->formatDate($date);

        $this->assertEquals($date->format('Y-m-d H:i:s'), $formattedDate);
    }

    /** @test */
    public function it_handles_empty_data()
    {
        // Create a report with no data
        $emptyReport = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'assets',
            'columns' => ['id', 'name'],
            'filters' => ['id' => -1], // This should return no results
        ]);

        $export = new ReportExport($emptyReport);
        $collection = $export->collection();

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(0, $collection);
    }
}
