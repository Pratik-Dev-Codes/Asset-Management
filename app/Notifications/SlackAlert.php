<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class SlackAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The message to send to Slack.
     *
     * @var string
     */
    protected $message;

    /**
     * The alert level (e.g., INFO, WARNING, CRITICAL).
     *
     * @var string
     */
    protected $level;

    /**
     * Create a new notification instance.
     *
     * @param  string  $message
     * @param  string  $level
     * @return void
     */
    public function __construct(string $message, string $level = 'INFO')
    {
        $this->message = $message;
        $this->level = strtoupper($level);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['slack'];
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\SlackMessage
     */
    public function toSlack($notifiable)
    {
        $level = $this->level;
        $message = $this->message;
        $appName = config('app.name');
        $environment = config('app.env');
        
        $slackMessage = (new SlackMessage)
            ->content("*[{$level}]* {$appName} ({$environment}): {$message}")
            ->from(config('monitoring.notifications.slack.username', 'Asset Management Monitor'))
            ->to(config('monitoring.notifications.slack.channel', '#alerts'));
            
        // Add emoji based on level
        $emoji = $this->getEmojiForLevel($level);
        if ($emoji) {
            $slackMessage->icon($emoji);
        }
        
        return $slackMessage;
    }
    
    /**
     * Get the emoji for the given alert level.
     *
     * @param  string  $level
     * @return string|null
     */
    protected function getEmojiForLevel(string $level): ?string
    {
        $emojis = [
            'INFO' => ':information_source:',
            'WARNING' => ':warning:',
            'CRITICAL' => ':rotating_light:',
            'ERROR' => ':x:',
            'SUCCESS' => ':white_check_mark:',
        ];
        
        return $emojis[$level] ?? null;
    }
}
