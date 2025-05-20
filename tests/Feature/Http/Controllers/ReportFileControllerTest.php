<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Report;
use App\Models\ReportFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportFileControllerTest extends TestCase
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
    public function it_can_download_a_report_file()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);
        $reportFile = ReportFile::factory()->create([
            'report_id' => $report->id,
            'file_path' => 'reports/test_report.xlsx',
            'file_name' => 'test_report.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
        
        // Create a fake file in storage
        Storage::disk('reports')->put('test_report.xlsx', 'Test file content');
        
        $response = $this->get(route('report-files.download', $reportFile));
        
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
        
        // Create a fake file in storage
        Storage::disk('reports')->put('test_report.pdf', 'Test PDF content');
        
        $response = $this->get(route('report-files.preview', $reportFile));
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename=test_report.pdf');
    }

    /** @test */
    public function it_can_delete_a_report_file()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);
        $reportFile = ReportFile::factory()->create([
            'report_id' => $report->id,
            'file_path' => 'reports/test_report.xlsx',
        ]);
        
        // Create a fake file in storage
        Storage::disk('reports')->put('test_report.xlsx', 'Test file content');
        
        $response = $this->delete(route('report-files.destroy', $reportFile));
        
        $response->assertRedirect(route('reports.show', $report));
        $this->assertDatabaseMissing('report_files', ['id' => $reportFile->id]);
        $this->assertFalse(Storage::disk('reports')->exists('test_report.xlsx'));
    }

    /** @test */
    public function it_can_upload_a_report_file()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);
        
        $file = UploadedFile::fake()->create('test_report.xlsx', 1024, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        
        $response = $this->post(route('report-files.upload', $report), [
            'file' => $file,
            'description' => 'Test file upload',
        ]);
        
        $response->assertRedirect(route('reports.show', $report));
        $this->assertDatabaseHas('report_files', [
            'report_id' => $report->id,
            'file_name' => 'test_report.xlsx',
            'description' => 'Test file upload',
        ]);
        
        // Assert the file was stored
        $uploadedFile = ReportFile::where('report_id', $report->id)->first();
        $this->assertTrue(Storage::disk('reports')->exists($uploadedFile->file_path));
    }

    /** @test */
    public function it_validates_file_upload()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);
        
        // Test with no file
        $response = $this->post(route('report-files.upload', $report), [
            'description' => 'Test file upload',
        ]);
        
        $response->assertSessionHasErrors(['file']);
        
        // Test with invalid file type
        $file = UploadedFile::fake()->create('test_report.txt', 1024, 'text/plain');
        
        $response = $this->post(route('report-files.upload', $report), [
            'file' => $file,
            'description' => 'Test file upload',
        ]);
        
        $response->assertSessionHasErrors(['file']);
    }

    /** @test */
    public function it_can_update_a_report_file()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);
        $reportFile = ReportFile::factory()->create([
            'report_id' => $report->id,
            'file_path' => 'reports/test_report.xlsx',
            'description' => 'Old description',
        ]);
        
        $response = $this->put(route('report-files.update', $reportFile), [
            'description' => 'Updated description',
        ]);
        
        $response->assertRedirect(route('reports.show', $report));
        $this->assertDatabaseHas('report_files', [
            'id' => $reportFile->id,
            'description' => 'Updated description',
        ]);
    }

    /** @test */
    public function it_can_download_multiple_report_files()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);
        
        $file1 = ReportFile::factory()->create([
            'report_id' => $report->id,
            'file_path' => 'reports/test1.xlsx',
            'file_name' => 'test1.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
        
        $file2 = ReportFile::factory()->create([
            'report_id' => $report->id,
            'file_path' => 'reports/test2.xlsx',
            'file_name' => 'test2.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
        
        // Create fake files in storage
        Storage::disk('reports')->put('test1.xlsx', 'Test file 1 content');
        Storage::disk('reports')->put('test2.xlsx', 'Test file 2 content');
        
        $response = $this->post(route('report-files.download-multiple'), [
            'file_ids' => [$file1->id, $file2->id],
        ]);
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
        $response->assertHeader('Content-Disposition', 'attachment; filename=report_files.zip');
    }

    /** @test */
    public function it_requires_authentication_to_access_protected_routes()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);
        $reportFile = ReportFile::factory()->create(['report_id' => $report->id]);
        
        // Logout the user
        auth()->logout();
        
        // Test download route
        $response = $this->get(route('report-files.download', $reportFile));
        $response->assertRedirect(route('login'));
        
        // Test preview route
        $response = $this->get(route('report-files.preview', $reportFile));
        $response->assertRedirect(route('login'));
        
        // Test delete route
        $response = $this->delete(route('report-files.destroy', $reportFile));
        $response->assertRedirect(route('login'));
        
        // Test upload route
        $response = $this->post(route('report-files.upload', $report), []);
        $response->assertRedirect(route('login'));
        
        // Test update route
        $response = $this->put(route('report-files.update', $reportFile), []);
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_authorizes_actions_based_on_permissions()
    {
        // Create a user without permissions
        $unauthorizedUser = User::factory()->create();
        
        $report = Report::factory()->create(['created_by' => $this->user->id]);
        $reportFile = ReportFile::factory()->create(['report_id' => $report->id]);
        
        // Act as the unauthorized user
        $this->actingAs($unauthorizedUser);
        
        // Test download route - should be allowed for any authenticated user
        $response = $this->get(route('report-files.download', $reportFile));
        $response->assertStatus(200);
        
        // Test delete route - should be forbidden
        $response = $this->delete(route('report-files.destroy', $reportFile));
        $response->assertStatus(403); // Forbidden
        
        // Test upload route - should be forbidden
        $file = UploadedFile::fake()->create('test_report.xlsx', 1024, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response = $this->post(route('report-files.upload', $report), [
            'file' => $file,
            'description' => 'Test file upload',
        ]);
        $response->assertStatus(403); // Forbidden
    }
}
