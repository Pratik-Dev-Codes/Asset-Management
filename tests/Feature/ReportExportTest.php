<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\ReportFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user with admin role
        $this->user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password')
        ]);
        
        $this->actingAs($this->user);
        
        // Fake the storage disks
        Storage::fake('public');
        Storage::fake('exports');
        
        // Fake the queue
        Queue::fake();
    }

    /** @test */
    public function it_can_export_a_report_to_pdf()
    {
        $report = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'asset',
            'name' => 'Test PDF Export',
            'columns' => ['id', 'name', 'created_at'],
            'filters' => [
                ['field' => 'status', 'operator' => 'equals', 'value' => 'active']
            ]
        ]);

        // Mock the database query
        $this->mock(\Illuminate\Database\Query\Builder::class, function ($mock) {
            $mock->shouldReceive('where')->andReturnSelf();
            $mock->shouldReceive('orderBy')->andReturnSelf();
            $mock->shouldReceive('count')->andReturn(1);
            $mock->shouldReceive('get')->andReturn(collect([
                (object)['id' => 1, 'name' => 'Test Asset', 'status' => 'active']
            ]));
        });

        $response = $this->get(route('reports.export', [$report->id, 'pdf']));
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="' . $report->slug . '.pdf"');
    }

    /** @test */
    public function it_can_export_a_report_to_excel()
    {
        $report = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'asset',
            'name' => 'Test Excel Export',
            'columns' => ['id', 'name', 'status', 'created_at']
        ]);

        // Mock the database query
        $this->mock(\Illuminate\Database\Query\Builder::class, function ($mock) {
            $mock->shouldReceive('where')->andReturnSelf();
            $mock->shouldReceive('orderBy')->andReturnSelf();
            $mock->shouldReceive('count')->andReturn(2);
            $mock->shouldReceive('get')->andReturn(collect([
                (object)['id' => 1, 'name' => 'Test Asset 1', 'status' => 'active'],
                (object)['id' => 2, 'name' => 'Test Asset 2', 'status' => 'inactive']
            ]));
        });

        $response = $this->get(route('reports.export', [$report->id, 'xlsx']));
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition', 'attachment; filename="' . $report->slug . '.xlsx"');
    }

    /** @test */
    public function it_denies_export_for_unauthorized_users()
    {
        $otherUser = User::factory()->create();
        $report = Report::factory()->create([
            'is_public' => false,
            'created_by' => $otherUser->id
        ]);

        $response = $this->get(route('reports.export', [$report->id, 'pdf']));
        
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'You do not have permission to access this report.'
        ]);
    }
    
    /** @test */
    public function it_allows_export_for_authorized_users()
    {
        $report = Report::factory()->create([
            'is_public' => true,
            'created_by' => $this->user->id,
            'type' => 'asset',
            'columns' => ['id', 'name']
        ]);
        
        // Mock the database query
        $this->mock(\Illuminate\Database\Query\Builder::class, function ($mock) {
            $mock->shouldReceive('where')->andReturnSelf();
            $mock->shouldReceive('orderBy')->andReturnSelf();
            $mock->shouldReceive('count')->andReturn(1);
            $mock->shouldReceive('get')->andReturn(collect([
                (object)['id' => 1, 'name' => 'Public Asset']
            ]));
        });

        $response = $this->get(route('reports.export', [$report->id, 'pdf']));
        
        $response->assertStatus(200);
    }
    
    /** @test */
    public function it_queues_large_exports()
    {
        $report = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'asset',
            'name' => 'Large Export',
            'columns' => ['id', 'name', 'status']
        ]);
        
        // Mock the database query
        $this->mock(\Illuminate\Database\Query\Builder::class, function ($mock) {
            $mock->shouldReceive('where')->andReturnSelf();
            $mock->shouldReceive('orderBy')->andReturnSelf();
            $mock->shouldReceive('count')->andReturn(1000);
        });
        
        $response = $this->post(route('reports.export.queue', $report->id), [
            'format' => 'xlsx'
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'queued' => true
        ]);
        
        // Assert the job was dispatched
        Queue::assertPushed(\App\Jobs\GenerateReportJob::class);
    }
    
    /** @test */
    public function it_downloads_exported_file()
    {
        $report = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'asset'
        ]);
        
        // Create a test file
        $file = ReportFile::factory()->create([
            'report_id' => $report->id,
            'file_name' => 'test-export.xlsx',
            'file_path' => 'reports/1/test-export.xlsx',
            'file_type' => 'xlsx',
            'file_size' => 1024,
            'generated_by' => $this->user->id
        ]);
        
        // Create the file in storage
        Storage::disk('public')->put($file->file_path, 'Test file content');
        
        $response = $this->get(route('reports.download', $file->id));
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="test-export.xlsx"');
    }
    
    /** @test */
    public function it_prevents_download_of_expired_file()
    {
        $report = Report::factory()->create([
            'created_by' => $this->user->id
        ]);
        
        // Create an expired file
        $file = ReportFile::factory()->create([
            'report_id' => $report->id,
            'file_name' => 'expired-export.xlsx',
            'file_path' => 'reports/1/expired-export.xlsx',
            'file_type' => 'xlsx',
            'file_size' => 1024,
            'generated_by' => $this->user->id,
            'expires_at' => now()->subDay()
        ]);
        
        Storage::disk('public')->put($file->file_path, 'Expired file content');
        
        $response = $this->get(route('reports.download', $file->id));
        
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'This file has expired and is no longer available for download.'
        ]);
    }

    /** @test */
    public function it_validates_export_format()
    {
        $report = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'asset'
        ]);

        // Test invalid format
        $response = $this->get(route('reports.export', [$report->id, 'invalid']));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['format']);
        
        // Test missing format
        $response = $this->get(route('reports.export', $report->id));
        $response->assertStatus(404);
    }
    
    /** @test */
    public function it_returns_404_for_nonexistent_report()
    {
        $response = $this->get(route('reports.export', [999, 'pdf']));
        $response->assertStatus(404);
    }
    
    /** @test */
    public function it_clears_report_cache()
    {
        $report = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'asset'
        ]);
        
        // Mock the cache clear
        Cache::shouldReceive('forget')
            ->withSomeOfArgs('report_' . $report->id . '_*')
            ->once();
        
        $response = $this->post(route('reports.clear-cache', $report->id));
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Report cache cleared successfully.'
        ]);
    }
}
