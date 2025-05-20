<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportFilterTest extends TestCase
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
    public function it_validates_date_filters()
    {
        $report = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'assets',
            'filters' => [
                'date_range' => [
                    'from' => '2023-01-01',
                    'to' => 'invalid-date'
                ]
            ]
        ]);

        $response = $this->get(route('reports.show', $report->id));
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['filters.date_range.to']);
    }

    /** @test */
    public function it_validates_status_filters()
    {
        $report = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'assets',
            'filters' => [
                'status' => 'invalid-status'
            ]
        ]);

        $response = $this->get(route('reports.show', $report->id));
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['filters.status']);
    }

    /** @test */
    public function it_validates_columns()
    {
        $report = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'assets',
            'columns' => ['invalid_column']
        ]);

        $response = $this->get(route('reports.show', $report->id));
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['columns.0']);
    }

    /** @test */
    public function it_validates_sorting()
    {
        $report = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'assets',
            'sorting' => [
                'invalid_column' => 'asc'
            ]
        ]);

        $response = $this->get(route('reports.show', $report->id));
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sorting']);
    }
}
