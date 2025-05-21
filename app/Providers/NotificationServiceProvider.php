<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register the custom broadcast channel as a singleton
        $this->app->singleton('custom.broadcast.channel', function ($app) {
            return new \App\Broadcasting\BroadcastNotificationChannel;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Register the broadcast channel for notifications
        Broadcast::channel('notifications.{userId}', function ($user, $userId) {
            return (int) $user->id === (int) $userId;
        });

        // Extend the notification channels after the service is registered
        $this->app->booted(function () {
            $this->app->extend('notifications', function ($service, $app) {
                $service->extend('broadcast', function () use ($app) {
                    return $app->make('custom.broadcast.channel');
                });
                return $service;
            });
        });
    }
}
