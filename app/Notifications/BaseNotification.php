<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The notification data.
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
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
            ->subject($this->getSubject())
            ->markdown('emails.notification', [
                'level' => $this->getLevel(),
                'introLines' => $this->getIntroLines(),
                'outroLines' => $this->getOutroLines(),
                'actionText' => $this->getActionText(),
                'actionUrl' => $this->getActionUrl(),
                'displayableActionUrl' => $this->getDisplayableActionUrl(),
            ]);
    }

    /**
     * Get the notification's subject.
     */
    abstract protected function getSubject(): string;

    /**
     * Get the notification's level.
     */
    protected function getLevel(): string
    {
        return 'info';
    }

    /**
     * Get the notification's intro lines.
     */
    protected function getIntroLines(): array
    {
        return [];
    }

    /**
     * Get the notification's outro lines.
     */
    protected function getOutroLines(): array
    {
        return [
            'If you did not expect to receive this notification, please check your application settings.',
        ];
    }

    /**
     * Get the notification's action text.
     */
    protected function getActionText(): ?string
    {
        return null;
    }

    /**
     * Get the notification's action URL.
     */
    protected function getActionUrl(): ?string
    {
        return null;
    }

    /**
     * Get the displayable version of the action URL.
     */
    protected function getDisplayableActionUrl(): string
    {
        $url = $this->getActionUrl();

        return str_replace(['http://', 'https://'], '', $url);
    }
}
