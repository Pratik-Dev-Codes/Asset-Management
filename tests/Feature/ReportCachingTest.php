<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ReportCachingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $report;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->report = Report::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'assets',
            'columns' => ['id', 'name', 'created_at']
        ]);
    }

    /** @test */
    public function it_caches_report_show_requests()
    {
        // Clear cache first
        Cache::tags(["user.{$this->user->id}.reports"])->clear();

        // First request - should be a cache miss
        $response1 = $this->get(route('reports.show', $this->report->id));
        $response1->assertStatus(200);

        // Second request - should be a cache hit
        $response2 = $this->get(route('reports.show', $this->report->id));
        $response2->assertStatus(200);

        // Verify cache was used
        $cacheKey = "report.{$this->report->id}";
        $this->assertTrue(Cache::tags(["user.{$this->user->id}.reports"])->has($cacheKey));
    }

    /** @test */
    public function it_busts_cache_when_report_is_updated()
    {
        // First, cache the report
        $response = $this->get(route('reports.show', $this->report->id));
        $response->assertStatus(200);

        // Update the report
        $updateResponse = $this->put(route('reports.update', $this->report->id), [
            'name' => 'Updated Report Name',
            'type' => $this->report->type,
            'columns' => $this->report->columns,
            'is_public' => true
        ]);

        $updateResponse->assertRedirect(route('reports.show', $this->report->id));

        // Cache should be busted
        $cacheKey = "report.{$this->report->id}";
        $this->assertFalse(Cache::tags(["user.{$this->user->id}.reports"])->has($cacheKey));
    }

    /** @test */
    public function it_busts_cache_when_report_is_deleted()
    {
        // First, cache the report
        $response = $this->get(route('reports.show', $this->report->id));
        $response->assertStatus(200);

        // Delete the report
        $deleteResponse = $this->delete(route('reports.destroy', $this->report->id));
        $deleteResponse->assertRedirect(route('reports.index'));

        // Cache should be busted
        $cacheKey = "report.{$this->report->id}";
        $this->assertFalse(Cache::tags(["user.{$this->user->id}.reports"])->has($cacheKey));
    }

    /** @test */
    public function it_uses_different_cache_keys_for_different_users()
    {
        // First user
        $response1 = $this->get(route('reports.show', $this->report->id));
        $response1->assertStatus(200);

        // Second user
        $user2 = User::factory()->create();
        $this->actingAs($user2);
        
        $response2 = $this->get(route('reports.show', $this->report->id));
        $response2->assertStatus(200);

        // Both should have their own cache entries
        $cacheKey1 = "report.{$this->report->id}";
        $cacheKey2 = "report.{$this->report->id}";
        
        $this->assertTrue(Cache::tags(["user.{$this->user->id}.reports"])->has($cacheKey1));
        $this->assertTrue(Cache::tags(["user.{$user2->id}.reports"])->has($cacheKey2));
    }
}
