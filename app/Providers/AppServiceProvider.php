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

        // Enforce server-side session expiry (set at login in `auth_expires_at`)
        try {
            \Illuminate\Support\Facades\Event::listen(\Illuminate\Routing\Events\RouteMatched::class, function ($event) {
                try {
                    $request = $event->request;
                    if (auth()->check()) {
                        $expires = session('auth_expires_at');
                        if (! empty($expires) && time() > (int) $expires) {
                            auth()->logout();
                            $request->session()->invalidate();
                            $request->session()->regenerateToken();
                            if (! $request->expectsJson()) {
                                \redirect()->route('login')->send();
                            }
                        }
                    }
                } catch (\Throwable $_e) {
                }
            });
        } catch (\Throwable $e) {
            // non-fatal
        }
    }
}
