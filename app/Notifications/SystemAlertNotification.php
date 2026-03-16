<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class SystemAlertNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $body;
    protected $url;

    public function __construct(array $payload)
    {
        $this->title = $payload['title'] ?? 'Alerta del sistema';
        $this->body  = $payload['body'] ?? '';
        $this->url   = $payload['url'] ?? null;
    }

    public function via($notifiable)
    {
        return ['database','broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
