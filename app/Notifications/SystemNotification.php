<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    use Queueable;

    public function __construct(private array $payload)
    {
    }

    public function via($notifiable): array
    {
        try {
            $prefs = \DB::table('notification_preferences')->where('user_id', $notifiable->id)->first();
            $channels = ['database'];
            if ($prefs && !empty($prefs->receive_push)) {
                $channels[] = 'broadcast';
            }
            return $channels;
        } catch (\Throwable $e) {
            return ['database'];
        }
    }

    public function toDatabase($notifiable): array
    {
        $type = strtolower((string)($this->payload['type'] ?? 'info'));

        $iconMap = [
            'success' => 'check-circle',
            'error' => 'x-circle',
            'warning' => 'alert-triangle',
            'info' => 'info',
        ];

        $colorMap = [
            'success' => '#10b981',
            'error' => '#ef4444',
            'warning' => '#f59e0b',
            'info' => '#3b82f6',
        ];

        return [
            'type' => $type,
            'title' => $this->payload['title'] ?? 'Notificacion del sistema',
            'body' => $this->payload['message'] ?? '',
            'message' => $this->payload['message'] ?? '',
            'icon' => $this->payload['icon'] ?? ($iconMap[$type] ?? 'info'),
            'color' => $this->payload['color'] ?? ($colorMap[$type] ?? '#3b82f6'),
            'link' => $this->payload['link'] ?? null,
            'related' => $this->payload['related'] ?? null,
            'meta' => $this->payload['meta'] ?? null,
            'timestamp' => $this->payload['timestamp'] ?? now()->toDateTimeString(),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
