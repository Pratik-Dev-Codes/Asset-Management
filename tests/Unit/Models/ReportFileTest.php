<?php

namespace Tests\Unit\Models;

use App\Models\Report;
use App\Models\ReportFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportFileTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_report_file()
    {
        $reportFile = ReportFile::factory()->create([
            'file_name' => 'test_report.xlsx',
            'file_path' => 'reports/test_report.xlsx',
            'file_size' => 1024,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'download_count' => 0,
        ]);

        $this->assertInstanceOf(ReportFile::class, $reportFile);
        $this->assertEquals('test_report.xlsx', $reportFile->file_name);
        $this->assertEquals('reports/test_report.xlsx', $reportFile->file_path);
        $this->assertEquals(1024, $reportFile->file_size);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $reportFile->mime_type);
        $this->assertEquals(0, $reportFile->download_count);
    }

    /** @test */
    public function it_belongs_to_a_report()
    {
        $report = Report::factory()->create();
        $reportFile = ReportFile::factory()->create(['report_id' => $report->id]);

        $this->assertInstanceOf(Report::class, $reportFile->report);
        $this->assertEquals($report->id, $reportFile->report->id);
    }

    /** @test */
    public function it_has_a_generator()
    {
        $user = User::factory()->create();
        $reportFile = ReportFile::factory()->create(['generated_by' => $user->id]);

        $this->assertInstanceOf(User::class, $reportFile->generator);
        $this->assertEquals($user->id, $reportFile->generator->id);
    }

    /** @test */
    public function it_can_check_if_file_is_expired()
    {
        // Not expired file
        $reportFile = ReportFile::factory()->create([
            'expires_at' => now()->addDay()
        ]);
        $this->assertFalse($reportFile->isExpired());

        // Expired file
        $expiredFile = ReportFile::factory()->create([
            'expires_at' => now()->subDay()
        ]);
        $this->assertTrue($expiredFile->isExpired());

        // File with no expiration
        $noExpirationFile = ReportFile::factory()->create([
            'expires_at' => null
        ]);
        $this->assertFalse($noExpirationFile->isExpired());
    }

    /** @test */
    public function it_can_get_formatted_file_size()
    {
        $reportFile = ReportFile::factory()->create(['file_size' => 1024]); // 1 KB
        $this->assertEquals('1 KB', $reportFile->formatted_file_size);

        $reportFile->update(['file_size' => 2048]); // 2 KB
        $this->assertEquals('2 KB', $reportFile->fresh()->formatted_file_size);

        $reportFile->update(['file_size' => 1048576]); // 1 MB
        $this->assertEquals('1 MB', $reportFile->fresh()->formatted_file_size);
    }

    /** @test */
    public function it_can_get_download_url()
    {
        $reportFile = ReportFile::factory()->create([
            'file_path' => 'reports/test_report.xlsx'
        ]);

        // The download URL should be a signed URL that points to the file
        $this->assertStringContainsString('reports/test_report.xlsx', $reportFile->download_url);
        $this->assertStringContainsString('signature=', $reportFile->download_url);
    }

    /** @test */
    public function it_can_get_metadata()
    {
        $metadata = [
            'generated_at' => now()->toDateTimeString(),
            'format' => 'xlsx',
            'filters' => [
                'status' => 'active',
                'date_from' => '2023-01-01',
                'date_to' => '2023-12-31',
            ]
        ];

        $reportFile = ReportFile::factory()->create([
            'metadata' => $metadata
        ]);

        $this->assertEquals($metadata, $reportFile->metadata);
        $this->assertEquals('xlsx', $reportFile->metadata['format']);
        $this->assertEquals('active', $reportFile->metadata['filters']['status']);
    }

    /** @test */
    public function it_can_increment_download_count()
    {
        $reportFile = ReportFile::factory()->create(['download_count' => 0]);
        
        $this->assertEquals(0, $reportFile->download_count);
        
        $reportFile->incrementDownloadCount();
        $this->assertEquals(1, $reportFile->fresh()->download_count);
        
        $reportFile->incrementDownloadCount();
        $this->assertEquals(2, $reportFile->fresh()->download_count);
    }

    /** @test */
    public function it_can_get_file_extension()
    {
        $reportFile = ReportFile::factory()->create(['file_name' => 'report_123.xlsx']);
        $this->assertEquals('xlsx', $reportFile->file_extension);

        $reportFile->update(['file_name' => 'report_123.csv']);
        $this->assertEquals('csv', $reportFile->fresh()->file_extension);

        $reportFile->update(['file_name' => 'report_123.pdf']);
        $this->assertEquals('pdf', $reportFile->fresh()->file_extension);
    }

    /** @test */
    public function it_can_scope_expired()
    {
        // Create some test files with different expiration dates
        $expiredFile = ReportFile::factory()->create(['expires_at' => now()->subDay()]);
        $notExpiredFile = ReportFile::factory()->create(['expires_at' => now()->addDay()]);
        $noExpirationFile = ReportFile::factory()->create(['expires_at' => null]);

        // Get expired files
        $expiredFiles = ReportFile::expired()->get();

        // Assertions
        $this->assertTrue($expiredFiles->contains($expiredFile));
        $this->assertFalse($expiredFiles->contains($notExpiredFile));
        $this->assertFalse($expiredFiles->contains($noExpirationFile));
    }

    /** @test */
    public function it_can_scope_by_mime_type()
    {
        $excelFile = ReportFile::factory()->create([
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
        $csvFile = ReportFile::factory()->create([
            'mime_type' => 'text/csv'
        ]);

        $excelFiles = ReportFile::byMimeType('excel')->get();
        $csvFiles = ReportFile::byMimeType('csv')->get();

        $this->assertTrue($excelFiles->contains($excelFile));
        $this->assertFalse($excelFiles->contains($csvFile));
        
        $this->assertTrue($csvFiles->contains($csvFile));
        $this->assertFalse($csvFiles->contains($excelFile));
    }

    /** @test */
    public function it_can_check_if_file_is_downloadable()
    {
        // Valid, not expired file
        $validFile = ReportFile::factory()->create([
            'expires_at' => now()->addDay()
        ]);
        $this->assertTrue($validFile->isDownloadable());

        // Expired file
        $expiredFile = ReportFile::factory()->create([
            'expires_at' => now()->subDay()
        ]);
        $this->assertFalse($expiredFile->isDownloadable());

        // File with no expiration
        $noExpirationFile = ReportFile::factory()->create([
            'expires_at' => null
        ]);
        $this->assertTrue($noExpirationFile->isDownloadable());
    }
}
