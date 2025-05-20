<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BulkOperationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The notification data.
     *
     * @var array
     */
    protected $data = [
        'title' => 'Bulk Operation',
        'message' => 'A bulk operation has been completed.',
        'type' => 'info',
        'action' => null,
        'count' => 0,
        'status' => null,
    ];

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(array $data = [])
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject($this->data['title'])
            ->line($this->data['message']);

        if ($this->data['action']) {
            $mail->action('View Details', url($this->data['action']));
        } else {
            $mail->action('View Dashboard', url('/dashboard'));
        }

        return $mail->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title' => $this->data['title'],
            'message' => $this->data['message'],
            'type' => $this->data['type'] ?? 'info',
            'action' => $this->data['action'] ?? null,
            'count' => $this->data['count'] ?? 0,
            'status' => $this->data['status'] ?? null,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
