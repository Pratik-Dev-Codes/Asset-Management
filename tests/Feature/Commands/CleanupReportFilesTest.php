<?php

namespace Tests\Feature\Commands;

use App\Models\Report;
use App\Models\ReportFile;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanupReportFilesTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_it_deletes_old_report_files()
    {
        Storage::fake('reports');

        // Create a report file that should be kept (less than 7 days old)
        $recentReport = Report::factory()->create();
        $recentFile = ReportFile::factory()->create([
            'report_id' => $recentReport->id,
            'created_at' => now()->subDays(3),
        ]);

        // Create a report file that should be deleted (more than 7 days old)
        $oldReport = Report::factory()->create();
        $oldFile = ReportFile::factory()->create([
            'report_id' => $oldReport->id,
            'created_at' => now()->subDays(10),
        ]);

        // Create test files in storage
        Storage::put($recentFile->file_path, 'test content');
        Storage::put($oldFile->file_path, 'test content');

        // Run the cleanup command
        $this->artisan('reports:cleanup', ['--days' => 7])
            ->expectsQuestion('Are you sure you want to delete 1 report file(s)?', 'yes')
            ->assertExitCode(0);

        // Assert the old file was deleted
        $this->assertDatabaseMissing('report_files', ['id' => $oldFile->id]);
        Storage::assertMissing($oldFile->file_path);

        // Assert the recent file still exists
        $this->assertDatabaseHas('report_files', ['id' => $recentFile->id]);
        Storage::assertExists($recentFile->file_path);
    }

    public function test_it_handles_dry_run()
    {
        Storage::fake('reports');

        $oldReport = Report::factory()->create();
        $oldFile = ReportFile::factory()->create([
            'report_id' => $oldReport->id,
            'created_at' => now()->subDays(10),
        ]);

        Storage::put($oldFile->file_path, 'test content');

        $this->artisan('reports:cleanup', [
            '--days' => 7,
            '--dry-run' => true,
        ])
            ->expectsOutput('Dry run: No files will be deleted.')
            ->assertExitCode(0);

        // Assert the file was not actually deleted
        $this->assertDatabaseHas('report_files', ['id' => $oldFile->id]);
        Storage::assertExists($oldFile->file_path);
    }

    public function test_it_handles_cancellation()
    {
        Storage::fake('reports');

        $oldReport = Report::factory()->create();
        $oldFile = ReportFile::factory()->create([
            'report_id' => $oldReport->id,
            'created_at' => now()->subDays(10),
        ]);

        Storage::put($oldFile->file_path, 'test content');

        $this->artisan('reports:cleanup', ['--days' => 7])
            ->expectsQuestion('Are you sure you want to delete 1 report file(s)?', 'no')
            ->expectsOutput('Cleanup cancelled.')
            ->assertExitCode(0);

        // Assert the file was not deleted
        $this->assertDatabaseHas('report_files', ['id' => $oldFile->id]);
        Storage::assertExists($oldFile->file_path);
    }

    public function test_it_handles_nonexistent_files()
    {
        Storage::fake('reports');

        $oldReport = Report::factory()->create();
        $oldFile = ReportFile::factory()->create([
            'report_id' => $oldReport->id,
            'created_at' => now()->subDays(10),
        ]);

        // Don't create the actual file in storage

        $this->artisan('reports:cleanup', ['--days' => 7])
            ->expectsQuestion('Are you sure you want to delete 1 report file(s)?', 'yes')
            ->assertExitCode(0);

        // The database record should still be deleted
        $this->assertDatabaseMissing('report_files', ['id' => $oldFile->id]);
    }

    public function test_it_handles_deletion_errors()
    {
        Storage::fake('reports');

        // Mock the Storage facade to throw an exception when deleting
        Storage::shouldReceive('delete')
            ->andThrow(new \Exception('Deletion failed'));

        $oldReport = Report::factory()->create();
        $oldFile = ReportFile::factory()->create([
            'report_id' => $oldReport->id,
            'created_at' => now()->subDays(10),
        ]);

        $this->artisan('reports:cleanup', ['--days' => 7])
            ->expectsQuestion('Are you sure you want to delete 1 report file(s)?', 'yes')
            ->assertExitCode(1);

        // The database record should still exist due to the error
        $this->assertDatabaseHas('report_files', ['id' => $oldFile->id]);
    }
}
