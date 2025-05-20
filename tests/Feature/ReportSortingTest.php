<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSortingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $reports;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create test reports with different created_at dates
        $this->reports = [
            Report::factory()->create([
                'name' => 'Z Report',
                'type' => 'assets',
                'created_at' => Carbon::now()->subDays(2),
                'created_by' => $this->user->id
            ]),
            Report::factory()->create([
                'name' => 'A Report',
                'type' => 'maintenance',
                'created_at' => Carbon::now()->subDay(),
                'created_by' => $this->user->id
            ]),
            Report::factory()->create([
                'name' => 'M Report',
                'type' => 'depreciation',
                'created_at' => Carbon::now(),
                'created_by' => $this->user->id
            ]),
        ];
    }

    /** @test */
    public function it_sorts_reports_by_name_ascending()
    {
        $response = $this->get(route('reports.index', [
            'sort_by' => 'name',
            'sort_direction' => 'asc'
        ]));
        
        $response->assertStatus(200);
        
        $reports = $response->original->getData()['reports']->items();
        $this->assertEquals('A Report', $reports[0]->name);
        $this->assertEquals('M Report', $reports[1]->name);
        $this->assertEquals('Z Report', $reports[2]->name);
    }

    /** @test */
    public function it_sorts_reports_by_name_descending()
    {
        $response = $this->get(route('reports.index', [
            'sort_by' => 'name',
            'sort_direction' => 'desc'
        ]));
        
        $response->assertStatus(200);
        
        $reports = $response->original->getData()['reports']->items();
        $this->assertEquals('Z Report', $reports[0]->name);
        $this->assertEquals('M Report', $reports[1]->name);
        $this->assertEquals('A Report', $reports[2]->name);
    }

    /** @test */
    public function it_sorts_reports_by_created_at_ascending()
    {
        $response = $this->get(route('reports.index', [
            'sort_by' => 'created_at',
            'sort_direction' => 'asc'
        ]));
        
        $response->assertStatus(200);
        
        $reports = $response->original->getData()['reports']->items();
        $this->assertEquals('Z Report', $reports[0]->name); // Oldest
        $this->assertEquals('A Report', $reports[1]->name);
        $this->assertEquals('M Report', $reports[2]->name); // Newest
    }

    /** @test */
    public function it_sorts_reports_by_created_at_descending_by_default()
    {
        $response = $this->get(route('reports.index'));
        
        $response->assertStatus(200);
        
        $reports = $response->original->getData()['reports']->items();
        $this->assertEquals('M Report', $reports[0]->name); // Newest
        $this->assertEquals('A Report', $reports[1]->name);
        $this->assertEquals('Z Report', $reports[2]->name); // Oldest
    }

    /** @test */
    public function it_sorts_reports_by_type()
    {
        $response = $this->get(route('reports.index', [
            'sort_by' => 'type',
            'sort_direction' => 'asc'
        ]));
        
        $response->assertStatus(200);
        
        $reports = $response->original->getData()['reports']->items();
        $this->assertEquals('assets', $reports[0]->type);
        $this->assertEquals('depreciation', $reports[1]->type);
        $this->assertEquals('maintenance', $reports[2]->type);
    }

    /** @test */
    public function it_ignores_invalid_sort_columns()
    {
        $response = $this->get(route('reports.index', [
            'sort_by' => 'invalid_column',
            'sort_direction' => 'asc'
        ]));
        
        $response->assertStatus(200);
        
        // Should fall back to default sorting (created_at desc)
        $reports = $response->original->getData()['reports']->items();
        $this->assertEquals('M Report', $reports[0]->name); // Newest
        $this->assertEquals('A Report', $reports[1]->name);
        $this->assertEquals('Z Report', $reports[2]->name); // Oldest
    }
}
