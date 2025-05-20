<?php

namespace Tests\Feature\Commands;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PruneOldNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prunes_old_notifications()
    {
        // Create some test notifications
        $now = now();
        
        // Recent notification (should be kept)
        DB::table('notifications')->insert([
            'id' => '11111111-1111-1111-1111-111111111111',
            'type' => 'App\\Notifications\\TestNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => 1,
            'data' => json_encode(['message' => 'Test']),
            'created_at' => $now->copy()->subDays(5),
            'updated_at' => $now->copy()->subDays(5),
        ]);
        
        // Old notification (should be pruned)
        DB::table('notifications')->insert([
            'id' => '22222222-2222-2222-2222-222222222222',
            'type' => 'App\\Notifications\\TestNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => 1,
            'data' => json_encode(['message' => 'Old test']),
            'created_at' => $now->copy()->subDays(60),
            'updated_at' => $now->copy()->subDays(60),
        ]);
        
        $this->assertDatabaseCount('notifications', 2);
        
        $this->artisan('notifications:prune', ['--days' => 30, '--force' => true])
            ->assertExitCode(0);
            
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', ['id' => '11111111-1111-1111-1111-111111111111']);
        $this->assertDatabaseMissing('notifications', ['id' => '22222222-2222-2222-2222-222222222222']);
    }
    
    public function test_it_handles_dry_run()
    {
        // Create an old notification
        DB::table('notifications')->insert([
            'id' => '11111111-1111-1111-1111-111111111111',
            'type' => 'App\\Notifications\\TestNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => 1,
            'data' => json_encode(['message' => 'Old test']),
            'created_at' => now()->subDays(60),
            'updated_at' => now()->subDays(60),
        ]);
        
        $this->artisan('notifications:prune', [
            '--days' => 30,
            '--dry-run' => true,
        ])
        ->expectsOutput('Dry run: Would delete 1 notification(s) older than ' . now()->subDays(30)->toDateTimeString())
        ->assertExitCode(0);
        
        // Notification should still exist
        $this->assertDatabaseCount('notifications', 1);
    }
    
    public function test_it_handles_no_notifications()
    {
        $this->artisan('notifications:prune', ['--days' => 30, '--force' => true])
            ->expectsOutput('No notifications to prune.')
            ->assertExitCode(0);
    }
    
    public function test_it_requires_confirmation()
    {
        // Create an old notification
        DB::table('notifications')->insert([
            'id' => '11111111-1111-1111-1111-111111111111',
            'type' => 'App\\Notifications\\TestNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => 1,
            'data' => json_encode(['message' => 'Old test']),
            'created_at' => now()->subDays(60),
            'updated_at' => now()->subDays(60),
        ]);
        
        $this->artisan('notifications:prune', ['--days' => 30])
            ->expectsQuestion('Are you sure you want to delete 1 notification(s)?', 'no')
            ->expectsOutput('Pruning cancelled.')
            ->assertExitCode(0);
            
        // Notification should still exist
        $this->assertDatabaseCount('notifications', 1);
    }
    
    public function test_it_handles_invalid_days()
    {
        $this->artisan('notifications:prune', ['--days' => 0, '--force' => true])
            ->expectsOutput('Days must be greater than 0')
            ->assertExitCode(1);
    }
}
