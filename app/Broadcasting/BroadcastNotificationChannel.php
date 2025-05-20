<?php

namespace App\Broadcasting;

use Illuminate\Notifications\Channels\BroadcastChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Class BroadcastNotificationChannel
 * 
 * This class extends the default BroadcastChannel to provide custom notification
 * broadcasting functionality with enhanced error handling and data retrieval.
 */
class BroadcastNotificationChannel extends BroadcastChannel implements ShouldBroadcast, ShouldBroadcastNow
{
    /**
     * The notification instance.
     *
     * @var \Illuminate\Notifications\Notification|null
     */
    protected $notification = null;

    /**
     * The user instance.
     *
     * @var mixed|null
     */
    protected $user = null;
    
    /**
     * The broadcast event name.
     *
     * @var string
     */
    protected $broadcastEvent = 'notification.created';
    
    /**
     * The broadcast connection name.
     *
     * @var string|null
     */
    protected $connection;
    
    /**
     * The broadcast queue name.
     *
     * @var string|null
     */
    protected $queue;
    
    /**
     * The broadcast delay in seconds.
     *
     * @var int
     */
    protected $delay = 0;
    
    /**
     * The broadcast data.
     *
     * @var array
     */
    protected $data = [];
    
    /**
     * The broadcast channels.
     *
     * @var array
     */
    protected $channels = [];

    /**
     * Create a new channel instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array
     * @throws \Exception If the notification cannot be sent
     */
    public function send($notifiable, Notification $notification)
    {
        try {
            // Store the notifiable and notification for later use
            $this->user = $notifiable;
            $this->notification = $notification;
            
            // Get the notification data
            $data = $this->getData($notifiable, $notification);
            
            // Log the notification being sent
            if (function_exists('info')) {
                info('Sending notification', [
                    'notification_id' => $data['id'] ?? 'unknown',
                    'type' => $data['type'] ?? 'unknown',
                    'user_id' => $notifiable->id ?? 'unknown',
                ]);
            }
            
            // Broadcast the notification
            $this->broadcast($notifiable, $notification, $data);
            
            // Log successful sending
            if (function_exists('info')) {
                info('Notification sent successfully', [
                    'notification_id' => $data['id'] ?? 'unknown',
                ]);
            }
            
            return $data;
        } catch (\Exception $e) {
            // Log the error
            Log::error('Failed to send notification: ' . $e->getMessage(), [
                'exception' => $e,
                'notification' => get_class($notification),
                'user_id' => $notifiable->id ?? 'unknown',
            ]);
            
            // Re-throw the exception to be handled by Laravel
            throw $e;
        }
    }

    /**
     * Get the data for the notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array
     */
    protected function getData($notifiable, $notification)
    {
        // Initialize data array
        $data = [];
        
        // Check if notification is an object
        if (!is_object($notification)) {
            return $this->getDefaultNotificationData();
        }
        
        // Try to get data using direct property access
        $data = $this->getDataFromNotification($notification);
        
        // If no data found, try to get it using toArray method
        if (empty($data) && method_exists($notification, 'toArray')) {
            $data = $this->getDataFromToArrayMethod($notification, $notifiable);
        }
        
        // If still no data, try to get it using toBroadcast method
        if (empty($data) && method_exists($notification, 'toBroadcast')) {
            $broadcastData = $this->getDataFromToBroadcastMethod($notification, $notifiable);
            if (!empty($broadcastData)) {
                $data = $broadcastData;
            }
        }
        
        // Ensure we have the required fields
        return $this->formatNotificationData($notification, $data);
    }
    
    /**
     * Get default notification data structure.
     *
     * @return array
     */
    protected function getDefaultNotificationData()
    {
        return [
            'id' => Str::uuid()->toString(),
            'type' => 'unknown',
            'data' => [],
            'read_at' => null,
            'created_at' => now()->toDateTimeString(),
        ];
    }
    
    /**
     * Get data from notification object.
     *
     * @param  object  $notification
     * @return array
     */
    protected function getDataFromNotification($notification)
    {
        $data = [];
        
        if (property_exists($notification, 'data')) {
            $notificationData = $notification->data;
            if (is_array($notificationData)) {
                $data = $notificationData;
            } elseif (is_object($notificationData) && method_exists($notificationData, 'toArray')) {
                $data = $notificationData->toArray();
            }
        }
        
        return $data;
    }
    
    /**
     * Get data using the toArray method.
     *
     * @param  object  $notification
     * @param  mixed  $notifiable
     * @return array
     */
    protected function getDataFromToArrayMethod($notification, $notifiable)
    {
        try {
            $arrayData = $notification->toArray($notifiable);
            return is_array($arrayData) ? $arrayData : [];
        } catch (\Exception $e) {
            Log::error('Error in toArray method: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get data using the toBroadcast method.
     *
     * @param  object  $notification
     * @param  mixed  $notifiable
     * @return array
     */
    protected function getDataFromToBroadcastMethod($notification, $notifiable)
    {
        try {
            $broadcastData = $notification->toBroadcast($notifiable);
            if (is_object($broadcastData) && isset($broadcastData->data)) {
                return (array) $broadcastData->data;
            }
        } catch (\Exception $e) {
            Log::error('Error in toBroadcast method: ' . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * Format the notification data with required fields.
     *
     * @param  object  $notification
     * @param  array  $data
     * @return array
     */
    protected function formatNotificationData($notification, array $data)
    {
        $notificationId = property_exists($notification, 'id') ? $notification->id : Str::uuid()->toString();
        
        return [
            'id' => $notificationId,
            'type' => get_class($notification),
            'data' => $data,
            'read_at' => null,
            'created_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Broadcast the notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @param  array  $data
     * @return void
     */
    protected function broadcast($notifiable, $notification, $data)
    {
        try {
            // Store the notifiable and notification for later use in broadcastOn
            $this->user = $notifiable;
            $this->notification = $notification;
            
            // Create a new broadcast event
            $event = new \Illuminate\Notifications\Events\BroadcastNotificationCreated(
                $notifiable, 
                $notification, 
                $data
            );
            
            // Dispatch the event
            event($event);
            
            // Log the broadcast
            if (function_exists('info')) {
                info('Broadcasted notification', [
                    'notification_id' => $data['id'] ?? 'unknown',
                    'type' => $data['type'] ?? 'unknown',
                    'user_id' => $notifiable->id ?? 'unknown',
                ]);
            }
        } catch (\Exception $e) {
            // Log any errors that occur during broadcasting
            Log::error('Failed to broadcast notification: ' . $e->getMessage(), [
                'exception' => $e,
                'notification_id' => $data['id'] ?? 'unknown',
                'user_id' => $notifiable->id ?? 'unknown',
            ]);
        }
    }
    
    /**
     * Get the channels the notification should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        try {
            // If custom channels are set, use them
            if (!empty($this->channels)) {
                return $this->channels;
            }
            
            // If no user is set, return an empty array
            if (!isset($this->user) || !$this->user) {
                Log::warning('No user set for broadcast notification');
                return [];
            }
            
            // Get the user ID safely
            $userId = is_object($this->user) && isset($this->user->id) 
                ? $this->user->id 
                : (string) $this->user;
                
            // Create a private channel for the user
            $channel = 'user.' . $userId;
            
            // Log the channel being used
            if (function_exists('info')) {
                info('Broadcasting to channel: ' . $channel, [
                    'user_id' => $userId,
                    'notification_type' => $this->notification ? get_class($this->notification) : 'unknown',
                ]);
            }
            
            return [new \Illuminate\Broadcasting\PrivateChannel($channel)];
        } catch (\Exception $e) {
            // Log any errors that occur
            Log::error('Error in broadcastOn: ' . $e->getMessage(), [
                'exception' => $e,
                'user' => $this->user ?? null,
                'notification' => $this->notification ? get_class($this->notification) : 'unknown',
            ]);
            
            return [];
        }
    }
    
    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        if (!empty($this->data)) {
            return $this->data;
        }
        
        if (!$this->notification || !$this->user) {
            return [];
        }
        
        return $this->getData($this->user, $this->notification);
    }
    
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return $this->broadcastEvent;
    }
    
    /**
     * The name of the queue to use when broadcasting the event.
     *
     * @return string
     */
    public function broadcastQueue()
    {
        return $this->queue ?? 'notifications';
    }
    
    /**
     * Set the broadcast event name.
     *
     * @param  string  $event
     * @return $this
     */
    public function setBroadcastEvent($event)
    {
        $this->broadcastEvent = $event;
        return $this;
    }
    
    /**
     * Determine if this event should be broadcast.
     *
     * @return bool
     */
    public function broadcastWhen()
    {
        return $this->user !== null && $this->notification !== null;
    }
    
    /**
     * The number of seconds before the job should be made available.
     *
     * @return \DateTime|\DateInterval|int|null
     */
    public function broadcastWithDelay()
    {
        return $this->delay;
    }
    
    /**
     * Determine if the broadcast should be queued.
     *
     * @return bool
     */
    public function broadcastOnDemand()
    {
        // Always broadcast immediately (not queued) since we implement ShouldBroadcastNow
        return false;
    }
    
    /**
     * Get the broadcast connection name.
     *
     * @return string|null
     */
    public function broadcastConnection()
    {
        return $this->connection ?? config('broadcasting.default');
    }
    
    /**
     * Set the broadcast connection name.
     *
     * @param  string  $connection
     * @return $this
     */
    public function setBroadcastConnection($connection)
    {
        $this->connection = $connection;
        return $this;
    }
    
    /**
     * Set the broadcast queue name.
     *
     * @param  string  $queue
     * @return $this
     */
    public function setBroadcastQueue($queue)
    {
        $this->queue = $queue;
        return $this;
    }
    
    /**
     * Set the broadcast delay in seconds.
     *
     * @param  int  $delay
     * @return $this
     */
    public function setBroadcastDelay($delay)
    {
        $this->delay = $delay;
        return $this;
    }
    
    /**
     * Set the broadcast event name.
     *
     * @param  string  $event
     * @return $this
     */
    public function setBroadcastEventName($event)
    {
        $this->broadcastEvent = $event;
        return $this;
    }
    
    /**
     * Set the broadcast data.
     *
     * @param  array  $data
     * @return $this
     */
    public function setBroadcastData(array $data)
    {
        $this->data = $data;
        return $this;
    }
    
    /**
     * Set the broadcast channels.
     *
     * @param  array  $channels
     * @return $this
     */
    public function setBroadcastChannels(array $channels)
    {
        $this->channels = $channels;
        return $this;
    }
}
