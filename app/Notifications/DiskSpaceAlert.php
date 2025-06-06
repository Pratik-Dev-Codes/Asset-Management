<?php

namespace App\Notifications;

class DiskSpaceAlert extends BaseNotification
{
    /**
     * Get the notification's subject.
     */
    protected function getSubject(): string
    {
        $level = strtoupper($this->data['level']);

        return "[{$level}] Disk Space Alert - {$this->data['disk']} ({$this->data['percent_used']}% used)";
    }

    /**
     * Get the notification's level.
     */
    protected function getLevel(): string
    {
        return strtolower($this->data['level']) === 'critical' ? 'error' : 'warning';
    }

    /**
     * Get the notification's intro lines.
     */
    protected function getIntroLines(): array
    {
        $disk = $this->data['disk'];
        $percentUsed = $this->data['percent_used'];
        $used = $this->data['used'];
        $total = $this->data['total'];
        $free = $this->data['free'];

        return [
            "The disk '{$disk}' is currently at {$percentUsed}% capacity.",
            '',
            "- Used: {$used}",
            "- Free: {$free}",
            "- Total: {$total}",
            '',
            'Please take action to free up disk space to prevent potential issues.',
        ];
    }

    /**
     * Get the notification's action text.
     */
    protected function getActionText(): ?string
    {
        return 'View Disk Usage';
    }

    /**
     * Get the notification's action URL.
     */
    protected function getActionUrl(): ?string
    {
        return config('app.url').'/admin/monitoring/disks';
    }
}
