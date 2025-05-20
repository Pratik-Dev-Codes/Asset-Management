<?php

namespace App\Listeners;

use App\Events\NotificationSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'notifications';

    /**
     * The time (seconds) before the job should be processed.
     *
     * @var int
     */
    public $delay = 0;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(NotificationSent $event)
    {
        try {
            // The notification is already sent via the event
            // This listener is here if you need to perform additional actions
            // when a notification is sent, such as logging or triggering other events

            Log::info('Notification sent', [
                'user_id' => $event->user->id,
                'notification_type' => get_class($event->notification),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process notification: '.$e->getMessage(), [
                'exception' => $e,
                'user_id' => $event->user->id ?? null,
                'notification_type' => $event->notification ? get_class($event->notification) : null,
            ]);

            // Re-throw the exception to mark the job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(NotificationSent $event, $exception)
    {
        Log::error('Notification job failed', [
            'exception' => $exception->getMessage(),
            'user_id' => $event->user->id ?? null,
            'notification_type' => $event->notification ? get_class($event->notification) : null,
        ]);
    }
}
