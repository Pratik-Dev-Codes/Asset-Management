<?php

namespace Tests\Feature\Commands;

use App\Jobs\ProcessReportJob;
use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunScheduledReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake the queue
        Bus::fake();
        Queue::fake();
    }

    public function test_it_runs_due_daily_reports()
    {
        $user = User::factory()->create();

        // Create a daily scheduled report that should run
        $report = Report::factory()->create([
            'created_by' => $user->id,
            'is_scheduled' => true,
            'is_active' => true,
            'schedule_frequency' => 'daily',
            'schedule_time' => now()->subHour()->format('H:i:s'), // Should be due
            'last_run_at' => now()->subDay(),
        ]);

        $this->artisan('reports:run-scheduled')
            ->assertExitCode(0);

        // Assert the job was dispatched
        Bus::assertDispatched(ProcessReportJob::class, function ($job) use ($report) {
            return $job->report->id === $report->id;
        });

        // Assert the report was updated
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'processing',
        ]);
    }

    public function test_it_skips_reports_not_due()
    {
        $user = User::factory()->create();

        // Create a daily scheduled report that should NOT run yet
        $report = Report::factory()->create([
            'created_by' => $user->id,
            'is_scheduled' => true,
            'is_active' => true,
            'schedule_frequency' => 'daily',
            'schedule_time' => now()->addHour()->format('H:i:s'), // Not due yet
            'last_run_at' => now()->subHour(),
        ]);

        $this->artisan('reports:run-scheduled')
            ->expectsOutput('No scheduled reports to run at this time.')
            ->assertExitCode(0);

        // Assert no jobs were dispatched
        Bus::assertNotDispatched(ProcessReportJob::class);
    }

    public function test_it_handles_weekly_reports()
    {
        $user = User::factory()->create();

        // Set the current day to Monday for testing
        $monday = Carbon::parse('monday this week');
        $this->travelTo($monday);

        // Create a weekly scheduled report for Monday
        $report = Report::factory()->create([
            'created_by' => $user->id,
            'is_scheduled' => true,
            'is_active' => true,
            'schedule_frequency' => 'weekly',
            'schedule_day' => 'monday',
            'schedule_time' => now()->subHour()->format('H:i:s'),
            'last_run_at' => $monday->copy()->subWeek(),
        ]);

        $this->artisan('reports:run-scheduled')
            ->assertExitCode(0);

        // Assert the job was dispatched
        Bus::assertDispatched(ProcessReportJob::class);

        // Travel back to now
        $this->travelBack();
    }

    public function test_it_handles_monthly_reports()
    {
        $user = User::factory()->create();

        // Set the current date to the 1st of the month for testing
        $firstOfMonth = now()->startOfMonth();
        $this->travelTo($firstOfMonth);

        // Create a monthly scheduled report for the 1st of the month
        $report = Report::factory()->create([
            'created_by' => $user->id,
            'is_scheduled' => true,
            'is_active' => true,
            'schedule_frequency' => 'monthly',
            'schedule_day' => 1, // 1st of the month
            'schedule_time' => now()->subHour()->format('H:i:s'),
            'last_run_at' => $firstOfMonth->copy()->subMonth(),
        ]);

        $this->artisan('reports:run-scheduled')
            ->assertExitCode(0);

        // Assert the job was dispatched
        Bus::assertDispatched(ProcessReportJob::class);

        // Travel back to now
        $this->travelBack();
    }

    public function test_it_handles_failed_reports()
    {
        $user = User::factory()->create();

        // Create a report that will fail processing
        $report = Report::factory()->create([
            'created_by' => $user->id,
            'is_scheduled' => true,
            'is_active' => true,
            'schedule_frequency' => 'daily',
            'schedule_time' => now()->subHour()->format('H:i:s'),
            'last_run_at' => now()->subDay(),
        ]);

        // Mock the ProcessReportJob to throw an exception
        Bus::fake([
            ProcessReportJob::class => function ($job) {
                throw new \Exception('Processing failed');
            },
        ]);

        $this->artisan('reports:run-scheduled')
            ->assertExitCode(0);

        // Assert the report was marked as failed
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'failed',
            'error_message' => 'Processing failed',
        ]);
    }

    public function test_it_handles_end_of_month_correctly()
    {
        $user = User::factory()->create();

        // Set the current date to the last day of February (a month with 28/29 days)
        $lastDayOfFeb = Carbon::create(2023, 2, 28); // Not a leap year
        $this->travelTo($lastDayOfFeb);

        // Create a monthly scheduled report for the 31st of the month
        $report = Report::factory()->create([
            'created_by' => $user->id,
            'is_scheduled' => true,
            'is_active' => true,
            'schedule_frequency' => 'monthly',
            'schedule_day' => 31, // Will be adjusted to the last day of February
            'schedule_time' => '00:00:00',
            'last_run_at' => $lastDayOfFeb->copy()->subMonth(),
        ]);

        $this->artisan('reports:run-scheduled')
            ->assertExitCode(0);

        // Assert the job was dispatched (even though Feb 31st doesn't exist)
        Bus::assertDispatched(ProcessReportJob::class);

        // Travel back to now
        $this->travelBack();
    }
}
