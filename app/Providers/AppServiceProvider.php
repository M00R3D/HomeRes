<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Notifications\DatabaseNotification;

// Persist notification `data.link` into the `link` column when DB notification is created

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Persist `data.link` into `notifications.link` when a DB notification is created
        try {
            DatabaseNotification::created(function ($model) {
                try {
                    if (empty($model->link) && is_array($model->data) && ! empty($model->data['link'])) {
                        $model->link = $model->data['link'];
                        $model->saveQuietly();
                    }
                } catch (\Throwable $e) {
                    // swallow errors to avoid breaking the app
                }
            });
        } catch (\Throwable $e) {
            // some environments may not have the notifications table yet during boot
        }
    }
}
