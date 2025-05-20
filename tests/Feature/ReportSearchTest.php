<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSearchTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create test reports
        Report::factory()->create([
            'name' => 'Monthly Asset Report',
            'description' => 'Report for all assets',
            'created_by' => $this->user->id
        ]);

        Report::factory()->create([
            'name' => 'Quarterly Maintenance Report',
            'description' => 'Report for maintenance activities',
            'created_by' => $this->user->id
        ]);

        Report::factory()->create([
            'name' => 'Yearly Depreciation',
            'description' => 'Asset depreciation report',
            'created_by' => $this->user->id
        ]);
    }

    /** @test */
    public function it_can_search_reports_by_name()
    {
        $response = $this->get(route('reports.index', ['search' => 'Monthly']));
        
        $response->assertStatus(200);
        $response->assertSee('Monthly Asset Report');
        $response->assertDontSee('Quarterly Maintenance Report');
        $response->assertDontSee('Yearly Depreciation');
    }

    /** @test */
    public function it_can_search_reports_by_description()
    {
        $response = $this->get(route('reports.index', ['search' => 'maintenance']));
        
        $response->assertStatus(200);
        $response->assertDontSee('Monthly Asset Report');
        $response->assertSee('Quarterly Maintenance Report');
        $response->assertDontSee('Yearly Depreciation');
    }

    /** @test */
    public function it_returns_empty_when_no_match()
    {
        $response = $this->get(route('reports.index', ['search' => 'nonexistent']));
        
        $response->assertStatus(200);
        $response->assertDontSee('Monthly Asset Report');
        $response->assertDontSee('Quarterly Maintenance Report');
        $response->assertDontSee('Yearly Depreciation');
    }

    /** @test */
    public function it_can_search_with_multiple_terms()
    {
        $response = $this->get(route('reports.index', ['search' => 'monthly report']));
        
        $response->assertStatus(200);
        $response->assertSee('Monthly Asset Report');
        $response->assertDontSee('Quarterly Maintenance Report');
        $response->assertDontSee('Yearly Depreciation');
    }
}
