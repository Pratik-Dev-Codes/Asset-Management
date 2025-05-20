<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Send a notification to a user.
     *
     * @return void
     */
    public function notifyUser(User $user, Notification $notification)
    {
        try {
            // Check if notification should be queued
            if (in_array(\Illuminate\Bus\Queueable::class, class_uses_recursive($notification))) {
                $user->notify($notification);
            } else {
                $user->notifyNow($notification);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send notification: '.$e->getMessage(), [
                'user_id' => $user->id,
                'notification' => get_class($notification),
                'exception' => $e,
            ]);

            // Fallback to email if database notification fails
            if (method_exists($notification, 'toMail')) {
                $this->sendEmailNotification($user, $notification);
            }
        }
    }

    /**
     * Send an email notification as a fallback.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    protected function sendEmailNotification(User $user, $notification)
    {
        try {
            if (method_exists($notification, 'toMail')) {
                $mailable = $notification->toMail($user);
                if ($mailable) {
                    Mail::to($user->email)->send($mailable);
                }
            } else {
                Log::warning('Notification does not have a toMail method', [
                    'user_id' => $user->id,
                    'notification' => get_class($notification),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send fallback email notification: '.$e->getMessage(), [
                'user_id' => $user->id,
                'notification' => get_class($notification),
                'exception' => $e,
            ]);
        }
    }

    /**
     * Mark all unread notifications as read for a user.
     *
     * @return void
     */
    public function markAllAsRead(User $user)
    {
        $user->unreadNotifications->markAsRead();
    }

    /**
     * Get the user's unread notifications.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnreadNotifications(User $user, $limit = null)
    {
        $query = $user->unreadNotifications();

        if ($limit) {
            $query->take($limit);
        }

        return $query->latest()->get();
    }

    /**
     * Get the user's recent notifications.
     *
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentNotifications(User $user, $limit = 10)
    {
        return $user->notifications()
            ->take($limit)
            ->latest()
            ->get();
    }

    /**
     * Clear old notifications from the database.
     *
     * @param  int  $days
     * @return int
     */
    public function clearOldNotifications($days = 30)
    {
        $cutoffDate = now()->subDays($days);

        return \DB::table('notifications')
            ->where('created_at', '<', $cutoffDate)
            ->delete();
    }
}
