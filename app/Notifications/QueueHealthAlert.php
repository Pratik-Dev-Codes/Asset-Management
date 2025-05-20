<?php

namespace App\Notifications;

class QueueHealthAlert extends BaseNotification
{
    /**
     * Get the notification's subject.
     */
    protected function getSubject(): string
    {
        $type = $this->data['type'];
        $level = $type === 'worker_status' ? 'CRITICAL' : 'WARNING';

        return "[{$level}] Queue Health Alert - ".$this->getAlertTitle();
    }

    /**
     * Get the notification's level.
     */
    protected function getLevel(): string
    {
        return $this->data['type'] === 'worker_status' ? 'error' : 'warning';
    }

    /**
     * Get the notification's intro lines.
     */
    protected function getIntroLines(): array
    {
        $lines = [];

        switch ($this->data['type']) {
            case 'failed_jobs':
                $count = $this->data['count'];
                $lines[] = "There are {$count} failed jobs in the queue that require attention.";
                $lines[] = '';
                $lines[] = 'Please check the failed jobs table and take appropriate action.';
                break;

            case 'queue_size':
                $queue = $this->data['queue'];
                $size = $this->data['size'];
                $threshold = $this->data['threshold'];

                $lines[] = "The '{$queue}' queue has {$size} jobs, which exceeds the threshold of {$threshold}.";
                $lines[] = '';
                $lines[] = 'Consider adding more workers or investigating why jobs are piling up.';
                break;

            case 'worker_status':
                $lines[] = 'No queue workers are currently running!';
                $lines[] = '';
                $lines[] = 'This is a critical issue that needs immediate attention as no background jobs will be processed.';
                break;
        }

        return $lines;
    }

    /**
     * Get the notification's action text.
     */
    protected function getActionText(): ?string
    {
        return 'View Queue Dashboard';
    }

    /**
     * Get the notification's action URL.
     */
    protected function getActionUrl(): ?string
    {
        return config('app.url').'/admin/monitoring/queues';
    }

    /**
     * Get a human-readable title for the alert.
     */
    protected function getAlertTitle(): string
    {
        switch ($this->data['type']) {
            case 'failed_jobs':
                return 'Failed Jobs Detected';

            case 'queue_size':
                return 'Queue Size Exceeded Threshold';

            case 'worker_status':
                return 'No Queue Workers Running';

            default:
                return 'Queue Health Issue';
        }
    }
}
