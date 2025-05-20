<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportGenerated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The report instance.
     *
     * @var \App\Models\Report
     */
    public $report;

    /**
     * The download URL for the report.
     *
     * @var string
     */
    public $downloadUrl;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Report $report, string $downloadUrl)
    {
        $this->report = $report;
        $this->downloadUrl = $downloadUrl;
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
            ->subject('Your Report is Ready: '.$this->report->name)
            ->line('Your report has been successfully generated and is ready for download.')
            ->line('**Report:** '.$this->report->name)
            ->when($this->report->description, function ($message) {
                $message->line('**Description:** '.$this->report->description);
            })
            ->action('Download Report', $this->downloadUrl)
            ->line('This link will expire in 7 days.')
            ->line('Thank you for using our application!');
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
            'type' => 'report.generated',
            'report_id' => $this->report->id,
            'report_name' => $this->report->name,
            'download_url' => $this->downloadUrl,
            'message' => 'Your report "'.$this->report->name.'" is ready for download.',
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'report-generated';
    }
}
