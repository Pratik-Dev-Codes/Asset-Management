<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TestNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendTestNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:test {email? : The email of the user to notify} {--all : Send to all users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test notification to a user or all users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            $users = User::all();
            $count = $users->count();
            
            if ($this->confirm("This will send a test notification to all {$count} users. Are you sure?")) {
                $bar = $this->output->createProgressBar($count);
                
                $users->each(function ($user) use ($bar) {
                    $user->notify(new TestNotification());
                    $bar->advance();
                });
                
                $bar->finish();
                $this->newLine(2);
                $this->info("✅ Test notification sent to all {$count} users!");
            }
            
            return 0;
        }
        
        $email = $this->argument('email') ?? $this->ask('Enter the email of the user to notify');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email '{$email}' not found!");
            return 1;
        }
        
        $this->info("Sending test notification to: {$user->name} <{$user->email}>");
        
        try {
            $user->notify(new TestNotification());
            $this->info('✅ Notification sent successfully!');
            $this->line('Check the database notifications table or the user\'s email to verify.');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to send notification: ' . $e->getMessage());
            return 1;
        }
    }
}
