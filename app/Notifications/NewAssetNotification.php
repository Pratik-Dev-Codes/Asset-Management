<?php

namespace App\Notifications;

use App\Models\Asset; // Import the Asset model
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAssetNotification extends Notification implements ShouldQueue // Implement ShouldQueue for background processing
{
    use Queueable;

    public Asset $asset; // Public property to hold the asset

    /**
     * Create a new notification instance.
     */
    public function __construct(Asset $asset) // Accept Asset model in constructor
    {
        $this->asset = $asset;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        $channels = ['database'];

        // Check if user wants to receive email notifications
        if (isset($notifiable->receive_email_notifications) && $notifiable->receive_email_notifications) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = url('/assets/'.$this->asset->id);

        return (new MailMessage)
            ->subject('New Asset Added: '.$this->asset->name)
            ->line('A new asset has been added to the system.')
            ->line('Asset Name: '.$this->asset->name)
            ->line('Asset Type: '.$this->asset->type)
            ->line('Serial Number: '.($this->asset->serial_number ?? 'N/A'))
            ->action('View Asset', $url)
            ->line('Thank you for using our Asset Management System!');
    }

    /**
     * Get the array representation of the notification.
     * This is used for the 'database' channel.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'asset_id' => $this->asset->id,
            'asset_name' => $this->asset->name,
            'asset_tag' => $this->asset->asset_tag ?? 'N/A', // Example: use asset_tag or default
            'message' => "A new asset '{$this->asset->name}' has been created.",
            'action_url' => url('/assets/'.$this->asset->id), // URL to view the asset
        ];
    }
}
