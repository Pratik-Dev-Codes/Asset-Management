<?php

namespace App\Notifications;

class ScheduledTaskAlert extends BaseNotification
{
    /**
     * Get the notification's subject.
     */
    protected function getSubject(): string
    {
        return "[WARNING] Scheduled Task Alert - {$this->data['task']} is late";
    }

    /**
     * Get the notification's level.
     */
    protected function getLevel(): string
    {
        return 'warning';
    }

    /**
     * Get the notification's intro lines.
     */
    protected function getIntroLines(): array
    {
        $task = $this->data['task'];
        $minutesLate = $this->data['minutes_late'];
        $lastRun = $this->data['last_run'];
        $expectedInterval = $this->data['expected_interval'];

        return [
            "The scheduled task '{$task}' is currently {$minutesLate} minutes late.",
            '',
            "- Last run: {$lastRun}",
            "- Expected interval: {$expectedInterval}",
            '',
            'Please check the task scheduler and logs for any issues.',
        ];
    }

    /**
     * Get the notification's action text.
     */
    protected function getActionText(): ?string
    {
        return 'View Scheduled Tasks';
    }

    /**
     * Get the notification's action URL.
     */
    protected function getActionUrl(): ?string
    {
        return config('app.url').'/admin/monitoring/scheduled-tasks';
    }
}
