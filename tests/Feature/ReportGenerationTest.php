<?php

namespace Tests\Feature;

use App\Jobs\GenerateReport;
use App\Models\Report;
use App\Models\ReportFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportGenerationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Fake the storage
        Storage::fake('reports');
    }

    /** @test */
    public function it_can_generate_a_report()
    {
        // Fake the queue
        Queue::fake();

        // Authenticate as admin
        $this->actingAs($this->admin);

        // Create a report
        $response = $this->post(route('reports.store'), [
            'name' => 'Test Report',
            'description' => 'Test report description',
            'type' => 'asset',
            'format' => 'xlsx',
            'filters' => [
                'status' => 'active',
            ],
        ]);

        // Assert the report was created
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('reports', [
            'name' => 'Test Report',
            'status' => 'pending',
        ]);

        // Get the created report
        $report = Report::where('name', 'Test Report')->first();

        // Assert the job was dispatched
        Queue::assertPushed(GenerateReport::class, function ($job) use ($report) {
            return $job->report->id === $report->id;
        });
    }

    /** @test */
    public function it_can_download_a_generated_report()
    {
        // Create a test report
        $report = Report::factory()->create([
            'status' => 'completed',
            'created_by' => $this->admin->id,
        ]);

        // Create a test file
        $fileName = 'test_report_' . time() . '.xlsx';
        $filePath = 'reports/' . $fileName;
        
        Storage::disk('reports')->put($filePath, 'Test file content');

        $reportFile = ReportFile::create([
            'report_id' => $report->id,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => 1024,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'generated_by' => $this->admin->id,
            'expires_at' => now()->addDays(7),
        ]);

        // Update the report with file info
        $report->update([
            'file_path' => $filePath,
            'file_generated_at' => now(),
        ]);

        // Authenticate as admin
        $this->actingAs($this->admin);

        // Download the report
        $response = $this->get(route('reports.download', $report));

        // Assert the file was downloaded
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    /** @test */
    public function it_can_generate_a_report_via_console_command()
    {
        $this->artisan('reports:test')
             ->expectsQuestion('Select report type', 0) // Select first option (Asset Report)
             ->expectsQuestion('Select export format', 0) // Select first format (XLSX)
             ->expectsConfirmation('Would you like to apply any filters?', 'no')
             ->expectsQuestion('Enter email address to send notification (leave empty to skip email)', 'test@example.com')
             ->assertExitCode(0);

        // Assert the report was created
        $this->assertDatabaseHas('reports', [
            'status' => 'pending',
            'notify_email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function it_requires_authentication_to_access_reports()
    {
        // Try to access reports without authentication
        $response = $this->get(route('reports.index'));
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_requires_permission_to_view_reports()
    {
        // Authenticate as a user without permissions
        $this->actingAs($this->user);

        // Try to access reports
        $response = $this->get(route('reports.index'));
        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_list_reports()
    {
        // Create some test reports
        $reports = Report::factory()->count(3)->create([
            'created_by' => $this->admin->id,
        ]);

        // Authenticate as admin
        $this->actingAs($this->admin);

        // Access the reports index
        $response = $this->get(route('reports.index'));

        // Assert the response was successful
        $response->assertStatus(200);
        
        // Assert the reports are visible
        foreach ($reports as $report) {
            $response->assertSee($report->name);
        }
    }
}
