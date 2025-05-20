<?php

namespace Tests\Feature;

use App\Jobs\ProcessReportJob;
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

class ReportTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake storage for testing
        Storage::fake('reports');
        
        // Fake the queue
        Queue::fake();
        Bus::fake();
    }
    
    /** @test */
    public function it_can_generate_a_report()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reports', [
                'report_type' => 'asset',
                'format' => 'xlsx',
                'columns' => ['id', 'name', 'serial'],
                'filters' => [
                    'status_id' => 1,
                ],
            ]);
            
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'report_id',
                'status_url'
            ]);
            
        $this->assertDatabaseHas('reports', [
            'type' => 'asset',
            'status' => 'pending',
            'created_by' => $user->id,
        ]);
        
        // Assert the job was dispatched
        Bus::assertDispatched(ProcessReportJob::class);
    }
    
    /** @test */
    public function it_validates_report_requests()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reports', [
                'report_type' => 'invalid_type',
                'format' => 'invalid_format',
                'columns' => [],
            ]);
            
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'report_type',
                'format',
                'columns',
            ]);
    }
    
    /** @test */
    public function it_checks_report_status()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create([
            'created_by' => $user->id,
            'status' => 'processing',
            'progress' => 50,
        ]);
        
        $response = $this->actingAs($user, 'api')
            ->getJson("/api/v1/reports/{$report->id}/status");
            
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'processing',
                'progress' => 50,
            ]);
    }
    
    /** @test */
    public function it_downloads_completed_reports()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create([
            'created_by' => $user->id,
            'status' => 'completed',
        ]);
        
        // Create a test file
        $file = UploadedFile::fake()->create('test.xlsx', 1024);
        $path = 'reports/' . $report->id . '/' . $file->getClientOriginalName();
        Storage::put($path, file_get_contents($file));
        
        // Create a report file record
        $reportFile = ReportFile::create([
            'report_id' => $report->id,
            'filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'generated_by' => $user->id,
        ]);
        
        $response = $this->actingAs($user, 'api')
            ->get("/api/v1/reports/{$report->id}/download");
            
        $response->assertStatus(200)
            ->assertHeader('Content-Type', $file->getMimeType())
            ->assertHeader('Content-Disposition', 'attachment; filename=' . $file->getClientOriginalName());
    }
    
    /** @test */
    public function it_prevents_unauthorized_access()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $report = Report::factory()->create([
            'created_by' => $user1->id,
            'is_public' => false,
        ]);
        
        // User 2 tries to access user 1's private report
        $response = $this->actingAs($user2, 'api')
            ->getJson("/api/v1/reports/{$report->id}/status");
            
        $response->assertStatus(403);
    }
    
    /** @test */
    public function it_allows_access_to_public_reports()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $report = Report::factory()->create([
            'created_by' => $user1->id,
            'is_public' => true,
            'status' => 'completed',
        ]);
        
        // User 2 can access user 1's public report
        $response = $this->actingAs($user2, 'api')
            ->getJson("/api/v1/reports/{$report->id}/status");
            
        $response->assertStatus(200);
    }
}
