<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Report;
use App\Models\ReportFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake storage for file uploads
        Storage::fake('reports');

        // Create a test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_view_index_page()
    {
        $response = $this->get(route('reports.index'));
        $response->assertStatus(200);
        $response->assertViewIs('reports.index');
    }

    /** @test */
    public function it_can_view_create_page()
    {
        $response = $this->get(route('reports.create'));
        $response->assertStatus(200);
        $response->assertViewIs('reports.create');
    }

    /** @test */
    public function it_can_store_a_new_report()
    {
        $data = [
            'name' => 'Test Report',
            'description' => 'This is a test report',
            'type' => 'asset',
            'format' => 'xlsx',
            'filters' => [
                'status' => 'active',
                'date_from' => now()->subMonth()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ],
        ];

        $response = $this->post(route('reports.store'), $data);

        $response->assertRedirect(route('reports.index'));
        $this->assertDatabaseHas('reports', [
            'name' => 'Test Report',
            'type' => 'asset',
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_report()
    {
        $response = $this->post(route('reports.store'), []);

        $response->assertSessionHasErrors(['name', 'type', 'format']);
    }

    /** @test */
    public function it_can_show_a_report()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);

        $response = $this->get(route('reports.show', $report));

        $response->assertStatus(200);
        $response->assertViewIs('reports.show');
        $response->assertViewHas('report', $report);
    }

    /** @test */
    public function it_can_edit_a_report()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);

        $response = $this->get(route('reports.edit', $report));

        $response->assertStatus(200);
        $response->assertViewIs('reports.edit');
        $response->assertViewHas('report', $report);
    }

    /** @test */
    public function it_can_update_a_report()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);

        $data = [
            'name' => 'Updated Report Name',
            'description' => 'Updated description',
            'type' => 'user',
            'format' => 'csv',
            'filters' => [
                'status' => 'inactive',
                'date_from' => now()->subYear()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ],
        ];

        $response = $this->put(route('reports.update', $report), $data);

        $response->assertRedirect(route('reports.show', $report));
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'name' => 'Updated Report Name',
            'type' => 'user',
        ]);
    }

    /** @test */
    public function it_can_delete_a_report()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);

        $response = $this->delete(route('reports.destroy', $report));

        $response->assertRedirect(route('reports.index'));
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }

    /** @test */
    public function it_can_generate_a_report()
    {
        Notification::fake();

        $report = Report::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->post(route('reports.generate', $report));

        $response->assertRedirect(route('reports.show', $report));
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'processing',
        ]);

        Notification::assertNothingSent();
    }

    /** @test */
    public function it_can_download_a_report_file()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);
        $reportFile = ReportFile::factory()->create([
            'report_id' => $report->id,
            'file_path' => 'reports/test_report.xlsx',
            'file_name' => 'test_report.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);

        Storage::disk('reports')->put('test_report.xlsx', 'Test file content');

        $response = $this->get(route('reports.download', $reportFile));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition', 'attachment; filename=test_report.xlsx');
    }

    /** @test */
    public function it_can_preview_a_report_file()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);
        $reportFile = ReportFile::factory()->create([
            'report_id' => $report->id,
            'file_path' => 'reports/test_report.pdf',
            'file_name' => 'test_report.pdf',
            'mime_type' => 'application/pdf',
        ]);

        Storage::disk('reports')->put('test_report.pdf', 'Test PDF content');

        $response = $this->get(route('reports.preview', $reportFile));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename=test_report.pdf');
    }

    /** @test */
    public function it_can_export_reports()
    {
        Report::factory()->count(5)->create(['created_by' => $this->user->id]);

        $response = $this->get(route('reports.export', ['format' => 'xlsx']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /** @test */
    public function it_can_import_reports()
    {
        $file = UploadedFile::fake()->create('reports.xlsx', 1024, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->post(route('reports.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('reports.index'));
        $response->assertSessionHas('success', 'Reports imported successfully.');
    }

    /** @test */
    public function it_can_search_reports()
    {
        $report1 = Report::factory()->create([
            'name' => 'Monthly Asset Report',
            'created_by' => $this->user->id,
        ]);

        $report2 = Report::factory()->create([
            'name' => 'User Activity Log',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('reports.search', ['q' => 'Asset']));

        $response->assertStatus(200);
        $response->assertViewHas('reports', function ($reports) use ($report1, $report2) {
            return $reports->contains($report1) && ! $reports->contains($report2);
        });
    }

    /** @test */
    public function it_can_filter_reports_by_type()
    {
        $assetReport = Report::factory()->create([
            'type' => 'asset',
            'created_by' => $this->user->id,
        ]);

        $userReport = Report::factory()->create([
            'type' => 'user',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('reports.index', ['type' => 'asset']));

        $response->assertStatus(200);
        $response->assertViewHas('reports', function ($reports) use ($assetReport, $userReport) {
            return $reports->contains($assetReport) && ! $reports->contains($userReport);
        });
    }
}
