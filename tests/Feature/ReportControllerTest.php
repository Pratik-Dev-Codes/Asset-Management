<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_view_reports_index()
    {
        $response = $this->get(route('reports.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_create_a_report()
    {
        $response = $this->post(route('reports.store'), [
            'name' => 'Test Report',
            'type' => 'assets',
            'columns' => ['id', 'name'],
            'filters' => [],
            'sorting' => ['created_at' => 'desc'],
            'is_public' => false
        ]);

        $response->assertRedirect(route('reports.show', 1));
        $this->assertDatabaseHas('reports', ['name' => 'Test Report']);
    }

    /** @test */
    public function it_validates_report_creation()
    {
        $response = $this->post(route('reports.store'), [
            'name' => '',
            'type' => 'invalid_type',
            'columns' => []
        ]);

        $response->assertSessionHasErrors(['name', 'type', 'columns']);
    }

    /** @test */
    public function it_can_update_a_report()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);
        
        $response = $this->put(route('reports.update', $report->id), [
            'name' => 'Updated Report',
            'type' => $report->type,
            'columns' => $report->columns,
            'is_public' => true
        ]);

        $response->assertRedirect(route('reports.show', $report->id));
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'name' => 'Updated Report',
            'is_public' => true
        ]);
    }

    /** @test */
    public function it_can_delete_a_report()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);
        
        $response = $this->delete(route('reports.destroy', $report->id));
        
        $response->assertRedirect(route('reports.index'));
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }

    /** @test */
    public function it_can_export_a_report()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);
        
        $response = $this->get(route('reports.export', [$report->id, 'pdf']));
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
