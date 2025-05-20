<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Import Auth facade

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            // Handle unauthenticated user, e.g., redirect to login
            return redirect()->route('login')->with('error', 'Please login to view notifications.');
        }

        // Fetch all notifications for the user, you can also use $user->unreadNotifications
        $notifications = $user->notifications()->paginate(15); // Paginate for better display

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark a specific notification as read.
     *
     * @param  string $id The ID of the notification
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAsRead(string $id)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login.');
        }

        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
            // Optionally, redirect back with success or to the notification's action URL
            // If the notification data has an 'action_url', redirect there
            if (isset($notification->data['action_url'])) {
                return redirect($notification->data['action_url']);
            }
        }
        return redirect()->route('notifications.index')->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all unread notifications as read.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login.');
        }

        $user->unreadNotifications->markAsRead();
        return redirect()->route('notifications.index')->with('success', 'All notifications marked as read.');
    }
}
