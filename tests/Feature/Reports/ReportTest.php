<?php

namespace Tests\Feature\Reports;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
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
    public function user_can_create_a_report()
    {
        $response = $this->post('/reports', [
            'name' => 'Test Report',
            'type' => 'assets',
            'columns' => ['id', 'name', 'status'],
            'filters' => ['status' => 'active'],
            'is_public' => false
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('reports', [
            'name' => 'Test Report',
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function report_requires_name_and_type()
    {
        $response = $this->post('/reports', []);
        $response->assertSessionHasErrors(['name', 'type']);
    }

    /** @test */
    public function user_can_view_their_own_report()
    {
        $report = Report::factory()->create(['created_by' => $this->user->id]);
        
        $response = $this->get("/reports/{$report->id}");
        $response->assertStatus(200);
    }

    /** @test */
    public function user_cannot_view_private_reports_of_others()
    {
        $otherUser = User::factory()->create();
        $report = Report::factory()->create([
            'created_by' => $otherUser->id,
            'is_public' => false
        ]);
        
        $response = $this->get("/reports/{$report->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_view_public_reports()
    {
        $otherUser = User::factory()->create();
        $report = Report::factory()->create([
            'created_by' => $otherUser->id,
            'is_public' => true
        ]);
        
        $response = $this->get("/reports/{$report->id}");
        $response->assertStatus(200);
    }
}