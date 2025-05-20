<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ReportErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        // Mock the logger
        Log::shouldReceive('error')
            ->andReturnNull();
    }

    /** @test */
    public function it_handles_invalid_report_id()
    {
        $response = $this->get(route('reports.show', 999));
        $response->assertStatus(404);
    }

    /** @test */
    public function it_handles_unauthorized_access()
    {
        $otherUser = User::factory()->create();
        $report = Report::factory()->private()->create([
            'created_by' => $otherUser->id
        ]);

        $response = $this->get(route('reports.show', $report->id));
        $response->assertStatus(403);
    }

    /** @test */
    public function it_handles_invalid_report_type()
    {
        $response = $this->post(route('reports.store'), [
            'name' => 'Invalid Report',
            'type' => 'invalid_type',
            'columns' => ['id', 'name']
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function it_handles_database_errors()
    {
        // Mock a database exception
        $mock = $this->mock(Report::class);
        $mock->shouldReceive('findOrFail')
            ->andThrow(new \Illuminate\Database\QueryException(
                'test', [], new \Exception)
            );
        
        $this->app->instance(Report::class, $mock);
        
        $response = $this->get(route('reports.show', 1));
        $response->assertStatus(500);
    }

    /** @test */
    public function it_handles_validation_errors_on_create()
    {
        $response = $this->post(route('reports.store'), [
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'type', 'columns']);
    }

    /** @test */
    public function it_handles_validation_errors_on_update()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);
        
        $response = $this->put(route('reports.update', $report->id), [
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'type', 'columns']);
    }

    /** @test */
    public function it_handles_invalid_export_format()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);
        
        $response = $this->get(route('reports.export', [$report->id, 'invalid_format']));
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['format']);
    }

    /** @test */
    public function it_handles_export_errors()
    {
        $report = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'assets',
            'columns' => ['id', 'name']
        ]);

        // Mock an exception during export
        $mock = $this->mock(\App\Exports\ReportExport::class);
        $mock->shouldReceive('download')
            ->andThrow(new \Exception('Export failed'));
        
        $this->app->instance(\App\Exports\ReportExport::class, $mock);
        
        $response = $this->get(route('reports.export', [$report->id, 'pdf']));
        
        $response->assertStatus(500);
    }
}
