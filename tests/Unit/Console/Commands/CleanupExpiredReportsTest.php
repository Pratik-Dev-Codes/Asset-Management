<?php

namespace Tests\Unit\Console\Commands;

use App\Models\Report;
use App\Models\ReportFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanupExpiredReportsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_deletes_expired_report_files()
    {
        // Fake the storage
        Storage::fake('public');
        
        // Create a test user
        $user = User::factory()->create();
        
        // Create a test report
        $report = Report::factory()->create(['created_by' => $user->id]);
        
        // Create some test report files - some expired, some not
        $expiredFiles = [];
        $validFiles = [];
        
        // Create 3 expired files
        for ($i = 0; $i < 3; $i++) {
            $file = ReportFile::factory()->create([
                'report_id' => $report->id,
                'file_name' => "expired_{$i}.pdf",
                'file_path' => "reports/{$report->id}/expired_{$i}.pdf",
                'file_type' => 'pdf',
                'file_size' => 1024,
                'generated_by' => $user->id,
                'expires_at' => now()->subDays(1), // Expired yesterday
            ]);
            
            // Create the file in storage
            Storage::disk('public')->put($file->file_path, 'test content');
            $expiredFiles[] = $file;
        }
        
        // Create 2 valid files
        for ($i = 0; $i < 2; $i++) {
            $file = ReportFile::factory()->create([
                'report_id' => $report->id,
                'file_name' => "valid_{$i}.pdf",
                'file_path' => "reports/{$report->id}/valid_{$i}.pdf",
                'file_type' => 'pdf',
                'file_size' => 1024,
                'generated_by' => $user->id,
                'expires_at' => now()->addDays(1), // Expires tomorrow
            ]);
            
            // Create the file in storage
            Storage::disk('public')->put($file->file_path, 'test content');
            $validFiles[] = $file;
        }
        
        // Run the command
        Artisan::call('reports:cleanup');
        
        // Assert the command output
        $output = Artisan::output();
        $this->assertStringContainsString('Starting cleanup of expired report files...', $output);
        $this->assertStringContainsString('Successfully deleted 3 expired report files.', $output);
        
        // Assert the expired files were deleted from the database
        foreach ($expiredFiles as $file) {
            $this->assertDatabaseMissing('report_files', ['id' => $file->id]);
            Storage::disk('public')->assertMissing($file->file_path);
        }
        
        // Assert the valid files still exist
        foreach ($validFiles as $file) {
            $this->assertDatabaseHas('report_files', ['id' => $file->id]);
            Storage::disk('public')->assertExists($file->file_path);
        }
    }
    
    /** @test */
    public function it_handles_no_expired_files()
    {
        // Run the command when there are no expired files
        Artisan::call('reports:cleanup');
        
        // Assert the command output
        $output = Artisan::output();
        $this->assertStringContainsString('No expired report files to clean up.', $output);
    }
    
    /** @test */
    public function it_handles_errors_gracefully()
    {
        // Mock the ReportFile model to throw an exception
        $this->mock(ReportFile::class, function ($mock) {
            $mock->shouldReceive('where->get')->andThrow(new \RuntimeException('Test error'));
        });
        
        // Run the command and expect an error
        $exitCode = Artisan::call('reports:cleanup');
        
        // Assert the command failed
        $this->assertEquals(1, $exitCode);
        
        // Assert the error was logged
        $output = Artisan::output();
        $this->assertStringContainsString('Failed to clean up expired report files', $output);
    }
}
