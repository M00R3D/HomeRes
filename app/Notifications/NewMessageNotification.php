<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NewMessageNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $body;
    protected $url;
    protected $icon;
    protected $meta;

    public function __construct(array $payload)
    {
        $this->title = $payload['title'] ?? 'Nuevo mensaje';
        $this->body  = $payload['body'] ?? '';
        $this->url   = $payload['url'] ?? null;
        $this->icon  = $payload['icon'] ?? null;
        $this->meta  = $payload['meta'] ?? null;
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
            'icon' => $this->icon,
            'url' => $this->url,
            'metadata' => $this->meta,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
