<?php

namespace Tests\Unit\Traits;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_allows_public_access_to_public_reports()
    {
        $report = Report::factory()->public()->create();

        $this->assertTrue($report->isAccessibleBy(null));
    }

    /** @test */
    public function it_denies_access_to_private_reports_for_guests()
    {
        $report = Report::factory()->private()->create();

        $this->assertFalse($report->isAccessibleBy(null));
    }

    /** @test */
    public function it_allows_creator_access_to_private_reports()
    {
        $user = User::factory()->create();
        $report = Report::factory()->private()->create(['created_by' => $user->id]);

        $this->assertTrue($report->isAccessibleBy($user));
    }

    /** @test */
    public function it_allows_admin_access_to_private_reports()
    {
        // Create a mock user with hasRole method
        $user = $this->createMock(User::class);
        $user->method('hasRole')->willReturn(true);

        $report = Report::factory()->private()->create();

        $this->assertTrue($report->isAccessibleBy($user));
    }

    /** @test */
    public function it_allows_users_with_view_all_permission()
    {
        // Create a mock user with hasPermissionTo method
        $user = $this->createMock(User::class);
        $user->method('hasPermissionTo')
            ->with('view all reports')
            ->willReturn(true);

        $report = Report::factory()->private()->create();

        $this->assertTrue($report->isAccessibleBy($user));
    }
}
