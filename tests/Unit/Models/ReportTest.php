<?php

namespace Tests\Unit\Models;

use App\Models\Report;
use App\Models\ReportFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_report()
    {
        $report = Report::factory()->create([
            'name' => 'Test Report',
            'type' => 'asset',
            'format' => 'xlsx',
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Report::class, $report);
        $this->assertEquals('Test Report', $report->name);
        $this->assertEquals('asset', $report->type);
        $this->assertEquals('xlsx', $report->format);
        $this->assertEquals('pending', $report->status);
    }

    /** @test */
    public function it_has_a_creator()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $report->creator);
        $this->assertEquals($user->id, $report->creator->id);
    }

    /** @test */
    public function it_has_files()
    {
        $report = Report::factory()->create();
        $file = ReportFile::factory()->create(['report_id' => $report->id]);

        $this->assertTrue($report->files->contains($file));
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $report->files);
    }

    /** @test */
    public function it_can_have_latest_file()
    {
        $report = Report::factory()->create();
        $file = ReportFile::factory()->create([
            'report_id' => $report->id,
            'created_at' => now()->subDay(),
        ]);

        $latestFile = ReportFile::factory()->create([
            'report_id' => $report->id,
            'created_at' => now(),
        ]);

        $this->assertInstanceOf(ReportFile::class, $report->latestFile);
        $this->assertEquals($latestFile->id, $report->latestFile->id);
    }

    /** @test */
    public function it_can_scope_by_status()
    {
        $pendingReport = Report::factory()->create(['status' => 'pending']);
        $completedReport = Report::factory()->create(['status' => 'completed']);
        $failedReport = Report::factory()->create(['status' => 'failed']);

        $pendingReports = Report::status('pending')->get();
        $completedReports = Report::status('completed')->get();
        $failedReports = Report::status('failed')->get();

        $this->assertTrue($pendingReports->contains($pendingReport));
        $this->assertTrue($completedReports->contains($completedReport));
        $this->assertTrue($failedReports->contains($failedReport));

        $this->assertFalse($pendingReports->contains($completedReport));
        $this->assertFalse($completedReports->contains($pendingReport));
    }

    /** @test */
    public function it_can_scope_by_type()
    {
        $assetReport = Report::factory()->create(['type' => 'asset']);
        $userReport = Report::factory()->create(['type' => 'user']);

        $assetReports = Report::type('asset')->get();
        $userReports = Report::type('user')->get();

        $this->assertTrue($assetReports->contains($assetReport));
        $this->assertTrue($userReports->contains($userReport));
        $this->assertFalse($assetReports->contains($userReport));
    }

    /** @test */
    public function it_can_check_if_report_is_completed()
    {
        $pendingReport = Report::factory()->create(['status' => 'pending']);
        $completedReport = Report::factory()->create(['status' => 'completed']);

        $this->assertFalse($pendingReport->isCompleted());
        $this->assertTrue($completedReport->isCompleted());
    }

    /** @test */
    public function it_can_check_if_report_is_failed()
    {
        $pendingReport = Report::factory()->create(['status' => 'pending']);
        $failedReport = Report::factory()->create(['status' => 'failed']);

        $this->assertFalse($pendingReport->isFailed());
        $this->assertTrue($failedReport->isFailed());
    }

    /** @test */
    public function it_can_check_if_report_is_pending()
    {
        $pendingReport = Report::factory()->create(['status' => 'pending']);
        $completedReport = Report::factory()->create(['status' => 'completed']);

        $this->assertTrue($pendingReport->isPending());
        $this->assertFalse($completedReport->isPending());
    }

    /** @test */
    public function it_can_get_download_url()
    {
        $report = Report::factory()->create([
            'file_path' => 'reports/test_report.xlsx',
        ]);

        $this->assertStringContainsString('reports/test_report.xlsx', $report->download_url);
    }

    /** @test */
    public function it_can_get_formatted_created_at()
    {
        $report = Report::factory()->create([
            'created_at' => '2023-01-01 12:00:00',
        ]);

        // Format depends on your application's locale settings
        $this->assertIsString($report->formatted_created_at);
    }

    /** @test */
    public function it_can_get_formatted_file_generated_at()
    {
        $report = Report::factory()->create([
            'file_generated_at' => '2023-01-01 12:00:00',
        ]);

        // Format depends on your application's locale settings
        $this->assertIsString($report->formatted_file_generated_at);
    }

    /** @test */
    public function it_can_get_formatted_file_size()
    {
        $report = Report::factory()->create([
            'file_size' => 1024, // 1 KB
        ]);

        $this->assertEquals('1 KB', $report->formatted_file_size);

        $report->update(['file_size' => 2048]); // 2 KB
        $this->assertEquals('2 KB', $report->fresh()->formatted_file_size);

        $report->update(['file_size' => 1048576]); // 1 MB
        $this->assertEquals('1 MB', $report->fresh()->formatted_file_size);
    }
}
