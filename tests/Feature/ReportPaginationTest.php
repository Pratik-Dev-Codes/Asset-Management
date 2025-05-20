<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $reports;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create 30 test reports
        $this->reports = Report::factory()
            ->count(30)
            ->create(['created_by' => $this->user->id]);
    }

    /** @test */
    public function it_paginates_reports()
    {
        $response = $this->get(route('reports.index'));

        $response->assertStatus(200);

        // Default per page is 15
        $response->assertViewHas('reports', function ($reports) {
            return $reports->count() === 15 &&
                   $reports->total() === 30;
        });
    }

    /** @test */
    public function it_can_change_per_page()
    {
        $response = $this->get(route('reports.index', ['per_page' => 5]));

        $response->assertStatus(200);

        $response->assertViewHas('reports', function ($reports) {
            return $reports->count() === 5 &&
                   $reports->perPage() === 5;
        });
    }

    /** @test */
    public function it_limits_max_per_page()
    {
        $response = $this->get(route('reports.index', ['per_page' => 200]));

        $response->assertStatus(200);

        // Max per page should be 100
        $response->assertViewHas('reports', function ($reports) {
            return $reports->count() === 30 && // We only have 30 records
                   $reports->perPage() === 100; // But per page is set to max 100
        });
    }

    /** @test */
    public function it_can_navigate_pages()
    {
        // First page
        $response = $this->get(route('reports.index', ['page' => 1, 'per_page' => 10]));
        $response->assertStatus(200);
        $firstPageItems = $response->original->getData()['reports']->items();

        // Second page
        $response = $this->get(route('reports.index', ['page' => 2, 'per_page' => 10]));
        $response->assertStatus(200);
        $secondPageItems = $response->original->getData()['reports']->items();

        // Third page should have the remaining items
        $response = $this->get(route('reports.index', ['page' => 3, 'per_page' => 10]));
        $response->assertStatus(200);
        $thirdPageItems = $response->original->getData()['reports']->items();

        // Assert no overlap between pages
        $allIds = array_merge(
            array_column($firstPageItems, 'id'),
            array_column($secondPageItems, 'id'),
            array_column($thirdPageItems, 'id')
        );

        $this->assertCount(30, array_unique($allIds));
    }
}
