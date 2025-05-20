<?php

namespace Tests\Feature\Commands;

use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListScheduledReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_scheduled_reports()
    {
        $user = User::factory()->create();

        // Create a scheduled report
        $report = Report::factory()->create([
            'created_by' => $user->id,
            'is_scheduled' => true,
            'is_active' => true,
            'schedule_frequency' => 'daily',
            'schedule_time' => '08:00:00',
            'last_run_at' => now()->subDay(),
        ]);

        $this->artisan('reports:list-scheduled')
            ->assertExitCode(0);
    }

    public function test_it_shows_no_reports_message()
    {
        $this->artisan('reports:list-scheduled')
            ->expectsOutput('No scheduled reports found.')
            ->assertExitCode(0);
    }

    public function test_it_handles_inactive_reports()
    {
        $user = User::factory()->create();

        // Create an inactive scheduled report
        Report::factory()->create([
            'created_by' => $user->id,
            'is_scheduled' => true,
            'is_active' => false,
            'schedule_frequency' => 'daily',
            'schedule_time' => '08:00:00',
        ]);

        $this->artisan('reports:list-scheduled')
            ->expectsOutput('No scheduled reports found.')
            ->assertExitCode(0);
    }

    public function test_it_handles_weekly_schedules()
    {
        $user = User::factory()->create();

        // Create a weekly scheduled report
        $report = Report::factory()->create([
            'created_by' => $user->id,
            'is_scheduled' => true,
            'is_active' => true,
            'schedule_frequency' => 'weekly',
            'schedule_day' => 'monday',
            'schedule_time' => '09:30:00',
            'last_run_at' => now()->subWeek(),
        ]);

        $this->artisan('reports:list-scheduled')
            ->assertExitCode(0);
    }

    public function test_it_handles_monthly_schedules()
    {
        $user = User::factory()->create();

        // Create a monthly scheduled report
        $report = Report::factory()->create([
            'created_by' => $user->id,
            'is_scheduled' => true,
            'is_active' => true,
            'schedule_frequency' => 'monthly',
            'schedule_day' => 1, // 1st of the month
            'schedule_time' => '10:00:00',
            'last_run_at' => now()->subMonth(),
        ]);

        $this->artisan('reports:list-scheduled')
            ->assertExitCode(0);
    }

    public function test_it_displays_error_status()
    {
        $user = User::factory()->create();

        // Create a failed report
        $report = Report::factory()->create([
            'created_by' => $user->id,
            'is_scheduled' => true,
            'is_active' => true,
            'schedule_frequency' => 'daily',
            'schedule_time' => '08:00:00',
            'status' => 'failed',
            'error_message' => 'Something went wrong',
            'last_run_at' => now()->subDay(),
        ]);

        $this->artisan('reports:list-scheduled')
            ->assertExitCode(0);
    }
}
