<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportGenerationFailed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The report instance.
     *
     * @var \App\Models\Report
     */
    public $report;

    /**
     * The error message.
     *
     * @var string
     */
    public $errorMessage;

    /**
     * Create a new notification instance.
     *
     * @param  \App\Models\Report  $report
     * @param  string  $errorMessage
     * @return void
     */
    public function __construct(Report $report, string $errorMessage)
    {
        $this->report = $report;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->error()
            ->subject('Report Generation Failed: ' . $this->report->name)
            ->line('We encountered an error while generating your report.')
            ->line('**Report:** ' . $this->report->name)
            ->when($this->report->description, function ($message) {
                $message->line('**Description:** ' . $this->report->description);
            })
            ->line('**Error:** ' . $this->errorMessage)
            ->action('View Report', route('reports.show', $this->report))
            ->line('Our team has been notified about this issue. Please try again later or contact support if the problem persists.');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'report.failed',
            'report_id' => $this->report->id,
            'report_name' => $this->report->name,
            'error_message' => $this->errorMessage,
            'message' => 'Failed to generate report: ' . $this->report->name,
        ];
    }

    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'report-failed';
    }
}
